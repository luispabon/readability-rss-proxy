<?php

namespace App\Command;

use andreskrey\Readability\Readability;
use App\Entity\Feed;
use App\Entity\FeedItem;
use App\Repository\FeedItemRepository;
use App\Repository\FeedRepository;
use FeedIo\Feed\ItemInterface as RawFeedItem;
use FeedIo\FeedIo;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Promise;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface as CommandOutput;
use function GuzzleHttp\Promise\settle;

class FeedFetchAllCommand extends Command
{
    private const FEED_ITEM_BATCH_SIZE = 20;

    /** @var \FeedIo\FeedIo */
    private $feedIo;

    /** @var \App\Repository\FeedRepository */
    private $feedRepository;

    /** @var \andreskrey\Readability\Readability */
    private $readability;

    /** @var \GuzzleHttp\Client */
    private $guzzle;

    /** @var \App\Repository\FeedItemRepository */
    private $feedItemRepository;

    public function __construct(
        FeedIo $feedIo,
        FeedRepository $feedRepository,
        FeedItemRepository $feedItemRepository,
        Readability $readability,
        Client $guzzle
    ) {
        $this->feedIo             = $feedIo;
        $this->feedRepository     = $feedRepository;
        $this->feedItemRepository = $feedItemRepository;
        $this->readability        = $readability;
        $this->guzzle             = $guzzle;

        parent::__construct();
    }

    protected static $defaultName = 'feed:fetch-all';

    protected function configure()
    {
        $this
            ->setDescription('Cycles through all the feeds and fetches them')
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description');
    }

    protected function execute(InputInterface $input, CommandOutput $output)
    {
        $feeds    = $this->feedRepository->findAll();
        $numFeeds = count($feeds);

        foreach ($feeds as $key => $feed) {
            $output->writeln(sprintf('Processing feed %s of %s: %s', ($key + 1), $numFeeds, $feed->getFeedUrl()));

            /** @var \FeedIo\Feed|\FeedIo\FeedInterface $feedContents */
            $feedContents = $this->feedIo->read($feed->getFeedUrl())->getFeed();

            // Update feed info
            $feed
                ->setTitle($feedContents->getTitle())
                ->setDescription($feedContents->getDescription())
                ->setIcon($this->getSiteFaviconUrl($feedContents));

            $this->feedRepository->save($feed);

            $numItems = count($feedContents);
            $output->writeln(sprintf('Found %s feed items.', $numItems));

            $promises     = [];
            $rawFeedItems = [];

            foreach ($feedContents as $feedItemKey => $rawFeedItem) {
                $currentItemNumber = $feedItemKey + 1;

                /** @var RawFeedItem $rawFeedItem */
                $output->writeln(sprintf(
                    'Acquiring %s of %s: %s',
                    $currentItemNumber,
                    $numItems,
                    $rawFeedItem->getTitle()
                ));

                $promises[$rawFeedItem->getLink()]     = $this->guzzle->getAsync($rawFeedItem->getLink());
                $rawFeedItems[$rawFeedItem->getLink()] = $rawFeedItem;

                if (($currentItemNumber % self::FEED_ITEM_BATCH_SIZE === 0) || $currentItemNumber === $numItems) {
                    $this->processBatch($promises, $rawFeedItems, $feed, $output);

                    $promises     = [];
                    $rawFeedItems = [];
                }
            }
        }

        $output->writeln('Finished.');
    }

    /**
     * @param Promise[]     $promises
     * @param RawFeedItem[] $rawFeedItems
     * @param Feed          $feed
     * @param CommandOutput $output
     */
    private function processBatch(array $promises, array $rawFeedItems, Feed $feed, CommandOutput $output): void
    {
        $numRawFeedItems = count($rawFeedItems);

        $output->writeln(sprintf('Finalising acquisition of %s feed items', $numRawFeedItems));

        // This ignores any errors fetching the item
        $fetchedSuccessfully = settle($promises)->wait();

        $counter = 1;
        foreach ($fetchedSuccessfully as $link => $promiseResult) {
            /** @var RawFeedItem $rawFeedItem */
            $rawFeedItem = $rawFeedItems[$link];

            $output->writeln(sprintf('Processing %s of %s: %s', $counter, $numRawFeedItems, $rawFeedItem->getTitle()));

            /** @var ResponseInterface $response */
            $response = $promiseResult['value'];

            $rawContents = $response->getBody()->getContents();

            $feedItem = $this->feedItemRepository->findOneBy(['link' => $rawFeedItem->getLink()]);
            if ($feedItem === null) {
                $feedItem = new FeedItem();
            }

            $feedItem
                ->setFeed($feed)
                ->setTitle($rawFeedItem->getTitle())
                ->setDescription($this->getReadableContent($rawContents))
                ->setLink($rawFeedItem->getLink())
                ->setLastModified($rawFeedItem->getLastModified());
            
            $this->feedItemRepository->save($feedItem);

            $counter++;
        }

        $output->writeln('Batch finalised.');
    }

    /**
     * Tries to work out the feed's icon.
     */
    private function getSiteFaviconUrl(\FeedIo\Feed $feed): ?string
    {
        foreach ($feed->getAllElements() as $element) {
            /** @var \FeedIo\Feed\Node\Element $element */
            if ($element->getName() === 'image') {
                foreach ($element->getAllElements() as $subElement) {
                    /** @var \FeedIo\Feed\Node\Element $subElement */
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
