<?php
declare(strict_types=1);

namespace App\Feed;

use App\Entity\Feed;
use App\Entity\FeedItem;
use App\Repository\FeedItemRepository;
use App\Repository\FeedRepository;
use DateInterval;
use DateTime;
use FeedIo\Feed as FeedIoFeed;
use FeedIo\Feed\ItemInterface as RawFeedItem;
use FeedIo\Feed\Node\Element as NodeElement;
use FeedIo\FeedInterface;
use FeedIo\FeedIo;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\Promise;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use function GuzzleHttp\Promise\settle;

/**
 * Each feed item is persisted into our data store, with a twist: we overwrite whatever small description the feed
 * comes from with the actual content of the page the feed item links to, run through a Readability analog.
 *
 * So any app or whatever that downloads the feed automagically gets a pseudo offline mode.
 */
class Processor
{
    private const FEED_ITEM_BATCH_SIZE = 5;

    /** @var FeedIo */
    private $feedIo;

    /** @var FeedRepository */
    private $feedRepository;

    /** @var ClientInterface */
    private $guzzle;

    /** @var FeedItemRepository */
    private $feedItemRepository;

    /** @var LoggerInterface */
    private $logger;

    /** @var string */
    private $readabilityEndpoint;

    public function __construct(
        FeedIo $feedIo,
        FeedRepository $feedRepository,
        FeedItemRepository $feedItemRepository,
        ClientInterface $guzzle,
        string $readabilityEndpoint,
        LoggerInterface $logger
    ) {
        $this->feedIo              = $feedIo;
        $this->feedRepository      = $feedRepository;
        $this->feedItemRepository  = $feedItemRepository;
        $this->guzzle              = $guzzle;
        $this->logger              = $logger;
        $this->readabilityEndpoint = $readabilityEndpoint;
    }

    /**
     * The meat of the matter. Feeds are fetched sequentially, and items are fetched asynchronously in batches.
     *
     * @param array|Feed[] $feeds
     * @param bool         $bypassLastModified
     *
     * @throws \Exception
     */
    public function fetchFeeds(array $feeds, bool $bypassLastModified): void
    {
        $numFeeds = count($feeds);
        foreach ($feeds as $key => $feed) {
            /** @var FeedIoFeed|FeedInterface $feedContents */

            $this->logger->info(sprintf('Processing feed %s of %s: %s', ($key + 1),
                $numFeeds,
                $feed->getFeedUrl()));

            // Take a couple of hours from last modified to fetch feeds from to have some overlap
            $updateFrom = $bypassLastModified === false && $feed->getLastModified() instanceof DateTime
                ? clone $feed->getLastModified()
                : null;

            if ($updateFrom instanceof DateTime) {
                $updateFrom->sub(new DateInterval('PT2H'));
            }

            $feedContents = $this->feedIo->read($feed->getFeedUrl(), null, $updateFrom)->getFeed();

            // Last modified can come sometimes as the epoch - correct that shit
            $lastMod = $feedContents
                ->getLastModified()
                ->getTimestamp() <= 0 ? new DateTime() : $feedContents->getLastModified();

            // Update feed info
            $feed
                ->setTitle($feedContents->getTitle())
                ->setDescription($feedContents->getDescription())
                ->setLastModified($lastMod)
                ->setIcon($this->getSiteFaviconUrl($feedContents));

            $this->feedRepository->save($feed);

            $numItems = count($feedContents);
            $this->logger->info(sprintf('Found %s feed items.', $numItems));

            $promises     = [];
            $rawFeedItems = [];

            foreach ($feedContents as $feedItemKey => $rawFeedItem) {
                /** @var RawFeedItem $rawFeedItem */
                $currentItemNumber = $feedItemKey + 1;
                $feedLink          = $rawFeedItem->getLink();

                if ($this->feedItemRepository->haveFeedItem($feed, $feedLink) === true) {
                    $this->logger->info(sprintf('Skipping %s', $rawFeedItem->getTitle()));
                    continue;
                }

                $this->logger->info(sprintf(
                    'Acquiring %s of %s: %s',
                    $currentItemNumber,
                    $numItems,
                    $rawFeedItem->getTitle()
                ));

                // Send link to readability-js-server asynchronously to get readable content
                $promises[$feedLink] = $this->guzzle->requestAsync('POST', $this->readabilityEndpoint, [
                    'headers' => [
                        'content-type' => 'application/json',
                    ],
                    'json'    => [
                        'url' => $feedLink,
                    ],
                ]);

                $rawFeedItems[$feedLink] = $rawFeedItem;

                if (($currentItemNumber % self::FEED_ITEM_BATCH_SIZE === 0) || $currentItemNumber === $numItems) {
                    $this->processBatch($promises, $rawFeedItems, $feed);

                    $promises     = [];
                    $rawFeedItems = [];
                }
            }
        }

        $this->logger->info('Finished.');
    }

    /**
     * @param Promise[]     $promises
     * @param RawFeedItem[] $rawFeedItems
     * @param Feed          $feed
     */
    private function processBatch(array $promises, array $rawFeedItems, Feed $feed): void
    {
        $numRawFeedItems = count($rawFeedItems);

        $this->logger->info(sprintf('Finalising acquisition of %s feed items', $numRawFeedItems));

        // This ignores any errors fetching the item
        $fetchedSuccessfully = settle($promises)->wait();

        $counter = 1;
        foreach ($fetchedSuccessfully as $link => $promiseResult) {
            /** @var RawFeedItem $rawFeedItem */
            $rawFeedItem = $rawFeedItems[$link];

            $this->logger->info(sprintf(
                'Processing %s of %s: %s',
                $counter,
                $numRawFeedItems,
                $rawFeedItem->getTitle()
            ));

            // We might have been throttled or something. We'll catch ya next time
            if (array_key_exists('value', $promiseResult) === false) {
                $this->logger->info(sprintf('<error>Could not acquire %s [%s]</error>',
                    $rawFeedItem->getTitle(),
                    $rawFeedItem->getLink()
                ),
                    [
                        'promiseResult' => $promiseResult,
                    ]
                );

                continue;
            }

            /** @var ResponseInterface $response */
            $response = $promiseResult['value'];

            $rawContents = $response->getBody()->getContents();
            $decoded     = json_decode($rawContents);

            $content = $decoded->content ?? '';
            $excerpt = $decoded->excerpt ?? $rawFeedItem->getDescription();

            if ($content === '') {
                $this->logger->warning(sprintf('Empty readability response for %s', $rawFeedItem->getLink()), [
                    'promiseResult' => $promiseResult,
                ]);

                continue;
            }

            // We're depending on this item not to exist already
            $feedItem = (new FeedItem())
                ->setFeed($feed)
                ->setTitle($rawFeedItem->getTitle())
                ->setExcerpt($excerpt)
                ->setDescription($content)
                ->setImage($this->getFirstImageFromStringsFilter($content))
                ->setLink($rawFeedItem->getLink())
                ->setLastModified($rawFeedItem->getLastModified())
                ->setCreatedAt(new DateTime());

            $this->feedItemRepository->save($feedItem);

            $counter++;
        }

        $this->logger->info('Batch finalised.');
    }

    /**
     * Tries to work out the feed's icon.
     */
    private function getSiteFaviconUrl(FeedIoFeed $feed): ?string
    {
        foreach ($feed->getAllElements() as $element) {
            /** @var NodeElement $element */
            if ($element->getName() === 'image') {
                foreach ($element->getAllElements() as $subElement) {
                    /** @var NodeElement $subElement */
                    if ($subElement->getName() === 'url') {
                        return $subElement->getValue();
                    }
                }
            }
        }

        return null;
    }

    /**
     * Finds and returns the first image link found on a list of strings. If any, that is
     */
    public function getFirstImageFromStringsFilter(string $content): ?string
    {
        $regexp  = '/(http(s?):)([\/|.|\w|\s|-])*\.(?:jpg|gif|png)/i';
        $matches = [];
        preg_match($regexp, $content, $matches);

        return $matches[0] ?? null;
    }
}
