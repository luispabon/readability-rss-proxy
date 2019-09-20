<?php

namespace App\Command;

use App\Repository\FeedRepository;
use FeedIo\FeedIo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class FeedFetchAllCommand extends Command
{
    /** @var \FeedIo\FeedIo */
    private $feedIo;
    /**
     * @var \App\Repository\FeedRepository
     */
    private $feedRepository;

    public function __construct(FeedIo $feedIo, FeedRepository $feedRepository)
    {
        $this->feedIo = $feedIo;
        $this->feedRepository = $feedRepository;

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
        $feeds = $this->feedRepository->findAll();

        foreach ($feeds as $feed) {
            $result = $this->feedIo->read($feed->getUrl());
            foreach ($result->getFeed() as $feedItem) {
                dd($feedItem);
            }
            
            $doc = $result->getFeed();


            dd($doc);
        }
        
//        dump($feeds);

    }
}
