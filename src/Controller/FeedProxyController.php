<?php
declare(strict_types=1);

namespace App\Controller;

use App\Repository\FeedRepository;
use FeedIo\Feed;
use FeedIo\Feed\Item;
use FeedIo\FeedIo;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/feed")
 */
class FeedProxyController
{
    /** @var HttpFoundationFactoryInterface */
    private $psrConverter;

    /** @var FeedRepository */
    private $feedRepository;

    /** @var FeedIo */
    private $feedIo;

    public function __construct(
        HttpFoundationFactoryInterface $httpFoundationFactory,
        FeedRepository $feedRepository,
        FeedIo $feedIo
    ) {
        $this->psrConverter   = $httpFoundationFactory;
        $this->feedRepository = $feedRepository;
        $this->feedIo         = $feedIo;
    }

    /**
     * @Route("/{id}", name="feed_display", methods={"GET"})
     */
    public function get(int $id): Response
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
            ->setLink($feed->getFeedUrl());

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
     * @Route("/{id}", name="opml_display", methods={"GET"})
     */
    public function getOpml(int $rssUserId, int $rssUserOpmlToken): Response
    {
//        $feeds = $this->feedRepository->findForUser()
    }

    private function removeQueryFromUrl(string $url): string
    {
        $parsed = parse_url($url);

        return sprintf('%s://%s%s', $parsed['scheme'], $parsed['host'], $parsed['path'] ?? '');
    }
}
