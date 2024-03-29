<?php
declare(strict_types=1);

namespace App\Command;

use App\Repository\FeedItemRepository;
use DateTime;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Command to delete old feed items.
 */
class FeedDeleteOldCommand extends Command
{
    protected static $defaultName = 'feed:delete-old';

    public function __construct(private FeedItemRepository $feedItemRepository, private LoggerInterface $logger)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Deletes old feed items')
            ->addOption('date', 'd', InputOption::VALUE_REQUIRED,
                'This is pretty flexible: a date such as "2018-01-01 00:13" or "-3 months"');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('date') === null) {
            $this->logger->error('No date given');

            return 1;
        }

        try {
            $date = new DateTime($input->getOption('date'));
        } catch (Throwable $ex) {
            $this->logger->error('Not a valid date value', ['date' => $input->getOption('date')]);

            return 1;
        }

        $numDeleted = $this->feedItemRepository->deleteOlderThan($date);

        $this->logger->info(sprintf('Correctly removed %s feed items', $numDeleted));

        return 0;
    }
}
