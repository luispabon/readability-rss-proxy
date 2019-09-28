<?php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\RssUser;
use App\Repository\FeedItemRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @Route("/reader")
 *
 * @method RssUser getUser()
 */
class ReaderController extends AbstractController
{
    /** @var FeedItemRepository */
    private $feedItemRepository;

    /** @var SerializerInterface */
    private $serializer;

    public function __construct(FeedItemRepository $feedItemRepository, SerializerInterface $serializer)
    {
        $this->feedItemRepository = $feedItemRepository;
        $this->serializer         = $serializer;
    }

    /**
     * @Route("/", name="reader_index", methods={"GET"}, format="html")
     */
    public function reader(): Response
    {
        return $this->render('reader/index.html.twig');
    }

    /**
     * @Route("/{page}", requirements={"page"="\d+"}, name="reader_index_json", methods={"GET"}, format="json")
     */
    public function listFeedItems(int $page): Response
    {
        $feedItems = $this->feedItemRepository->findAllForUserPaginated(
            $this->getUser(),
            [],
            $page,
            20
        );

        $response          = JsonResponse::create();
        $normalizerOptions = ['ignored_attributes' => ['feed']];

        return $response->setJson($this->serializer->serialize($feedItems, 'json', $normalizerOptions));
    }
}
