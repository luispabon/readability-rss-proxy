<?php
declare(strict_types=1);

namespace App\Command;

use App\Repository\FeedItemRepository;
use DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

/**
 * Command to delete old feed items.
 */
class FeedDeleteOldCommand extends Command
{
    protected static $defaultName = 'feed:delete-old';

    /** @var FeedItemRepository */
    private $feedItemRepository;

    public function __construct(FeedItemRepository $feedItemRepository)
    {
        parent::__construct();
        $this->feedItemRepository = $feedItemRepository;
    }

    protected function configure()
    {
        $this
            ->setDescription('Deletes old feed items')
            ->addOption('date', 'd', InputOption::VALUE_REQUIRED,
                'This is pretty flexible: a date such as "2018-01-01 00:13" or "-3 months"');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('date') === null) {
            $io->error('No date passed');

            return 1;
        }

        try {
            $date = new DateTime($input->getOption('date'));
        } catch (Throwable $ex) {
            $io->error('Not a valid date value');

            return 1;
        }

        $numDeleted = $this->feedItemRepository->deleteOlderThan($date);

        $io->success(sprintf('Correctly removed %s feed items', $numDeleted));

        return 0;
    }
}
