<?php

namespace App\Controller;

use App\Entity\Feed;
use App\Form\FeedType;
use App\Repository\FeedRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/feed")
 */
class FeedCrudController extends AbstractController
{
    /**
     * @Route("/", name="feed_index", methods={"GET"})
     */
    public function index(FeedRepository $feedRepository): Response
    {
        return $this->render('feed/index.html.twig', [
            'feeds' => $feedRepository->findBy([], ['id' => 'ASC']),
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
            $entityManager->persist($feed);
            $entityManager->flush();

            return $this->redirectToRoute('feed_index');
        }

        return $this->render('feed/new.html.twig', [
            'feed' => $feed,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="feed_show", methods={"GET"})
     */
    public function show(Feed $feed): Response
    {
        return $this->render('feed/show.html.twig', [
            'feed' => $feed,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="feed_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Feed $feed): Response
    {
        $form = $this->createForm(FeedType::class, $feed);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('feed_index');
        }

        return $this->render('feed/edit.html.twig', [
            'feed' => $feed,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="feed_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Feed $feed): Response
    {
        if ($this->isCsrfTokenValid('delete'.$feed->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($feed);
            $entityManager->flush();
        }

        return $this->redirectToRoute('feed_index');
    }
}
