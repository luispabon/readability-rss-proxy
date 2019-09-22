<?php
declare(strict_types=1);

namespace App\Feed;

use andreskrey\Readability\Readability;
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
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Promise;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use function GuzzleHttp\Promise\settle;

class Processor
{
    private const FEED_ITEM_BATCH_SIZE = 5;
    private const USER_AGENT_HEADER    = 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:70.0) Gecko/20100101 Firefox/70.0';

    /** @var FeedIo */
    private $feedIo;

    /** @var FeedRepository */
    private $feedRepository;

    /** @var Readability */
    private $readability;

    /** @var Client */
    private $guzzle;

    /** @var FeedItemRepository */
    private $feedItemRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        FeedIo $feedIo,
        FeedRepository $feedRepository,
        FeedItemRepository $feedItemRepository,
        Readability $readability,
        Client $guzzle,
        LoggerInterface $logger
    ) {
        $this->feedIo             = $feedIo;
        $this->feedRepository     = $feedRepository;
        $this->feedItemRepository = $feedItemRepository;
        $this->readability        = $readability;
        $this->guzzle             = $guzzle;
        $this->logger             = $logger;
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
                $currentItemNumber = $feedItemKey + 1;

                /** @var RawFeedItem $rawFeedItem */

                if ($this->feedItemRepository->findOneBy(['link' => $rawFeedItem->getLink()]) !== null) {
                    $this->logger->info(sprintf('Skipping %s', $rawFeedItem->getTitle()));
                    continue;
                }

                $this->logger->info(sprintf(
                    'Acquiring %s of %s: %s',
                    $currentItemNumber,
                    $numItems,
                    $rawFeedItem->getTitle()
                ));

                // Some sites are hostile to weird user agents, so use firefox's
                $promises[$rawFeedItem->getLink()] = $this->guzzle->getAsync($rawFeedItem->getLink(), [
                        'headers' => [
                            'User-Agent' => self::USER_AGENT_HEADER,
                            'Accept'     => 'text/html',
                        ],
                    ]
                );

                $rawFeedItems[$rawFeedItem->getLink()] = $rawFeedItem;

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

            $this->logger->info(sprintf('Processing %s of %s: %s', $counter, $numRawFeedItems,
                $rawFeedItem->getTitle()));

            // We might have been throttled or something. We'll catch ya next time
            if (array_key_exists('value', $promiseResult) === false) {
                $this->logger->info(sprintf(
                    '<error>Could not acquire %s [%s]</error>',
                    $rawFeedItem->getTitle(),
                    $rawFeedItem->getLink()
                ));
                dump($promiseResult);
                continue;
            }

            /** @var ResponseInterface $response */
            $response = $promiseResult['value'];

            $rawContents = $response->getBody()->getContents();

            // We're depending on this item not to exist already
            $feedItem = (new FeedItem())
                ->setFeed($feed)
                ->setTitle($rawFeedItem->getTitle())
                ->setDescription($this->getReadableContent($rawContents))
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

    private function getReadableContent(string $rawContent): string
    {
        if ($this->readability->parse($rawContent) === true) {
            return $this->readability->getContent();
        }

        return $rawContent;
    }
}
