<?php
declare(strict_types=1);

namespace App\Controller;

use App\Repository\FeedRepository;
use App\Repository\RssUserRepository;
use FeedIo\Feed;
use FeedIo\Feed\Item;
use FeedIo\FeedIo;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/feed")
 */
class FeedProxyController extends AbstractController
{
    /** @var HttpFoundationFactoryInterface */
    private HttpFoundationFactoryInterface $psrConverter;

    /** @var FeedRepository */
    private FeedRepository $feedRepository;

    /** @var FeedIo */
    private FeedIo $feedIo;

    /** @var RssUserRepository */
    private RssUserRepository $userRepository;

    public function __construct(
        HttpFoundationFactoryInterface $httpFoundationFactory,
        FeedRepository $feedRepository,
        RssUserRepository $userRepository,
        FeedIo $feedIo
    ) {
        $this->psrConverter   = $httpFoundationFactory;
        $this->feedRepository = $feedRepository;
        $this->feedIo         = $feedIo;
        $this->userRepository = $userRepository;
    }

    /**
     * @Route("/{id}", name="feed_display", methods={"GET"})
     */
    public function getFeed(int $id): Response
    {
        $feed = $this->feedRepository->find($id);
        if ($feed === null) {
            return new Response('Feed not found', 404);
        }

        $formattedFeed = (new Feed())
            ->setTitle($feed->getTitle())
            ->setDescription($feed->getDescription())
            ->setLastModified($feed->getLastModified())
            ->setPublicId($this->removeQueryFromUrl($feed->getFeedUrl()))
            ->setLink($feed->getFeedUrl())
            ->setLogo($feed->getIcon());

        foreach ($feed->getFeedItems() as $feedItem) {
            $formattedItem = (new Item())
                ->setTitle($feedItem->getTitle())
                ->setDescription($feedItem->getDescription())
                ->setLastModified($feedItem->getLastModified())
                ->setLink($feedItem->getLink())
                ->setPublicId($this->removeQueryFromUrl($feedItem->getLink()));

            $formattedFeed->add($formattedItem);
        }

        $atomResponse = $this->feedIo->getPsrResponse($formattedFeed, 'atom');

        return $this->psrConverter->createResponse($atomResponse);
    }

    /**
     * @Route("/opml/{userId}/{token}", name="opml_display", methods={"GET"})
     */
    public function getOpml(int $userId, string $token, Request $request): Response
    {
        $user = $this->userRepository->findByIdAndOpmlToken($userId, $token);
        if ($user === null) {
            return new Response('Feed not found', 404);
        }

        $response = $this->render('feed/opml.xml.twig', [
            'feeds' => $this->feedRepository->findForUser($user),
        ]);

        $reqHeaders = $request->headers;
        $opmlMime   = 'text/x-opml';

        // If client hasn't said specifically they accept opml (possibly a browser), simply return a standard xml
        // mime type. This is also correct, since opml is xml
        $response->headers->set('content-type', $reqHeaders->get('accept') === $opmlMime ? $opmlMime : 'text/xml');

        return $response;
    }

    /**
     * Given a url, return it without any query parameters.
     */
    private function removeQueryFromUrl(string $url): string
    {
        $parsed = parse_url($url);

        return sprintf('%s://%s%s', $parsed['scheme'], $parsed['host'], $parsed['path'] ?? '');
    }
}
