<?php
declare(strict_types=1);

namespace App\Controller;

use App\Repository\FeedItemRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/reader")
 */
class ReaderController extends AbstractController
{
    /**
     * @var FeedItemRepository
     */
    private $feedItemRepository;

    public function __construct(FeedItemRepository $feedItemRepository)
    {
        $this->feedItemRepository = $feedItemRepository;
    }

    /**
     * @Route("/", name="reader_index", methods={"GET"})
     */
    public function index(): Response
    {
        $feeds = $this->feedItemRepository->findBy([], ['lastModified' => 'desc']);
        
        dd($feeds);
    }

}
