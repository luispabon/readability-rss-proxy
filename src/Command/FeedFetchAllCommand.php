<?php
declare(strict_types=1);

namespace App\Command;

use App\Feed\Processor as FeedProcessor;
use App\Repository\FeedRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface as CommandOutput;

/**
 * Command to sync all available feeds.
 *
 * Each feed item is persisted into our data store, with a twist: we overwrite whatever small description the feed
 * comes from with the actual content of the page the feed item links to, run through a Readability analog.
 *
 * So any app or whatever that downloads the feed automagically gets a pseudo offline mode.
 */
class FeedFetchAllCommand extends Command
{
    public function __construct(private FeedRepository $feedRepository, private FeedProcessor $processor)
    {
        parent::__construct();
    }

    protected static $defaultName = 'feed:fetch-all';

    protected function configure(): void
    {
        $this
            ->setDescription('Cycles through all the feeds and fetches them')
            ->addOption(
                'no-time-constraint',
                null,
                InputOption::VALUE_NONE,
                'Bypass last modified times on feeds and retrieve all available'
            );
    }

    protected function execute(InputInterface $input, CommandOutput $output): int
    {
        $feeds = $this->feedRepository->findAll();

        $bypassLastModified = $input->getOption('no-time-constraint');

        $output->writeln(sprintf('Processing %s feeds.', count($feeds)));

        $this->processor->fetchFeeds($feeds, $bypassLastModified);

        $output->writeln('Finished.');

        return 0;
    }
}
