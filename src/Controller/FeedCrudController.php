<?php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\Feed;
use App\Entity\RssUser;
use App\Feed\Processor as FeedProcessor;
use App\Form\FeedType;
use App\Repository\FeedRepository;
use App\Services\Permissions;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * @Route("/admin/feed")
 *
 * @method RssUser getUser()
 */
class FeedCrudController extends AbstractController
{
    public function __construct(private FeedProcessor $feedProcessor, private Permissions $permissions)
    {
    }

    /**
     * @Route("/", name="feed_index", methods={"GET"})
     */
    public function index(FeedRepository $feedRepository): Response
    {
        return $this->render('feed/index.html.twig', [
            'feeds' => $feedRepository->findForUser($this->getUser()),
            'user'  => $this->getUser(),
        ]);
    }

    /**
     * @Route("/new", name="feed_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $feed = new Feed();
        $form = $this->createForm(FeedType::class, $feed);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $feed->setRssUser($this->getUser());
            $entityManager->persist($feed);
            $entityManager->flush();

            // Fetch feed, but fail gracefully
            try {
                $this->feedProcessor->fetchFeeds([$feed], true);
            } catch (Throwable $exception) {
                // Do nothing
            }

            return $this->redirectToRoute('feed_new');
        }

        return $this->render('feed/new.html.twig', [
            'feed'     => $feed,
            'form'     => $form->createView(),
            'embedded' => $request->get('embedded', false) === 'true',
        ]);
    }

    /**
     * @Route("/{id}/edit", name="feed_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Feed $feed): Response
    {
        $form = $this->createForm(FeedType::class, $feed);
        $form->handleRequest($request);

        if ($this->permissions->canEditContentFromUser($this->getUser(), $feed->getRssUser()) === false) {
            throw $this->createAccessDeniedException('Cannot edit a feed that is not yours.');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('feed_index');
        }

        return $this->render('feed/edit.html.twig', [
            'feed'     => $feed,
            'form'     => $form->createView(),
            'embedded' => $request->get('embedded', false) === 'true',
        ]);
    }

    /**
     * @Route("/{id}", name="feed_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Feed $feed): Response
    {
        if ($this->isCsrfTokenValid('delete' . $feed->getId(), $request->request->get('_token'))) {
            if ($this->permissions->canEditContentFromUser($this->getUser(), $feed->getRssUser()) === false) {
                throw $this->createAccessDeniedException('Cannot delete a feed that is not yours.');
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($feed);
            $entityManager->flush();
        }

        return $this->redirectToRoute('feed_index');
    }
}
