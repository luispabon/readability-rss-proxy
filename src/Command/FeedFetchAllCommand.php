<?php

namespace App\Command;

use andreskrey\Readability\Readability;
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

    public function __construct(
        FeedIo $feedIo,
        FeedRepository $feedRepository,
        Readability $readability,
        Client $guzzle
    ) {
        $this->feedIo = $feedIo;
        $this->feedRepository = $feedRepository;
        $this->readability = $readability;
        $this->guzzle = $guzzle;

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

//            dd($result->getFeed());
            foreach ($result->getFeed() as $feedItem) {

                /** @var \FeedIo\Feed\ItemInterface $feedItem */
                dd($feedItem);
//                dd($re)

                $rawContents = $this->guzzle->get($feedItem->getLink())->getBody()->getContents();
                
                echo $rawContents;
                die;
                $this->readability->parse($rawContents);
                $readable = (string) $this->readability;

                echo $readable;
                die;
            }

            $doc = $result->getFeed();

            dd($doc);
        }
//        dump($feeds);

    }
}
