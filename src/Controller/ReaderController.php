<?php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\RssUser;
use App\Repository\FeedItemRepository;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Serializer;

/**
 * @Route("/reader")
 *
 * @method RssUser getUser()
 */
class ReaderController extends AbstractController
{
    /**
     * @var FeedItemRepository
     */
    private $feedItemRepository;
    /**
     * @var SerializerInterface
     */
    private $serializer;
    /**
     * @var \Symfony\Component\Serializer\SerializerInterface
     */
    private $symSerializer;

    public function __construct(FeedItemRepository $feedItemRepository, SerializerInterface $serializer, \Symfony\Component\Serializer\SerializerInterface $symSerializer)
    {
        $this->feedItemRepository = $feedItemRepository;
        $this->serializer = $serializer;
        $this->symSerializer = $symSerializer;
    }

    /**
     * @Route("/", name="reader_index", methods={"GET"}, format="html")
     */
    public function reader(): Response
    {
        return $this->render('reader/index.html.twig', [
            'feedItems' => '',
        ]);
    }

    /**
     * @Route("/{page}", requirements={"page"="\d+"}, name="reader_index_json", methods={"GET"}, format="json")
     */
    public function listFeedItems(int $page): Response
    {
        $feedItems = $this->feedItemRepository->findAllForUserPaginated($this->getUser(), [], 1, 10);

        $response = JsonResponse::create();
        $normalizerOptions = ['ignored_attributes' => ['feed']];

//        $response->setJson($this->serializer->serialize($feedItems->getItems(), 'json', [
//            'ignored_attributes' => ['feed']
//        ]));

//        dd($this->serializer->serialize($feedItems, 'json'));

        $response->setJson($this->symSerializer->serialize($feedItems, 'json', $normalizerOptions));

        return $response;
    }

}
