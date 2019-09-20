<?php

namespace App\Command;

use andreskrey\Readability\Readability;
use App\Entity\FeedItem;
use App\Repository\FeedItemRepository;
use App\Repository\FeedRepository;
use FeedIo\FeedIo;
use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FeedFetchAllCommand extends Command
{
    /** @var \FeedIo\FeedIo */
    private $feedIo;
    /**
     * @var \App\Repository\FeedRepository
     */
    private $feedRepository;
    /**
     * @var \andreskrey\Readability\Readability
     */
    private $readability;
    /**
     * @var \GuzzleHttp\Client
     */
    private $guzzle;
    /**
     * @var \App\Repository\FeedItemRepository
     */
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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $feeds    = $this->feedRepository->findAll();
        $numFeeds = count($feeds);

        foreach ($feeds as $key => $feed) {
            $output->writeln(sprintf('Processing feed %s of %s: %s', ($key + 1), $numFeeds, $feed->getFeedUrl()));
            $feedContents = $this->feedIo->read($feed->getFeedUrl())->getFeed();

            // Update feed info
            $feed
                ->setTitle($feedContents->getTitle())
                ->setDescription($feedContents->getDescription());

            $this->feedRepository->save($feed);

            $numItems = count($feedContents);
            $output->writeln(sprintf('Found %s feed items', $numItems));

            foreach ($feedContents as $feedItemKey => $rawFeedItem) {
                /** @var \FeedIo\Feed\ItemInterface $rawFeedItem */
                $output->writeln(sprintf(
                    'Processing %s of %s: %s',
                    ($feedItemKey + 1),
                    $numItems,
                    $rawFeedItem->getTitle()
                ));

                $rawContents = $this->guzzle->get($rawFeedItem->getLink())->getBody()->getContents();

                $feedItem = $this->feedItemRepository->findOneBy(['link' => $rawFeedItem->getLink()]);
                if ($feedItem === null) {
                    $feedItem = new FeedItem();
                }

                $feedItem
                    ->setFeed($feed)
                    ->setTitle($rawFeedItem->getTitle())
                    ->setDescription($rawContents)
                    ->setLink($rawFeedItem->getLink())
                    ->setLastModified($rawFeedItem->getLastModified());

                $this->feedItemRepository->save($feedItem);
            }
        }

        $output->writeln('Finished.');
    }
}
