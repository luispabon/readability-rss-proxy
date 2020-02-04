<?php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\RssUser;
use App\Form\RssUserType;
use App\Repository\RssUserRepository;
use App\Services\Permissions;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * @Route("/admin/user")
 *
 * @method RssUser getUser()
 */
class RssUserController extends AbstractController
{
    /** @var UserPasswordEncoderInterface */
    private UserPasswordEncoderInterface $passwordEncoder;

    /**@var RssUserRepository */
    private RssUserRepository $userRepository;

    /** @var Permissions */
    private Permissions $permissions;

    public function __construct(
        UserPasswordEncoderInterface $passwordEncoder,
        RssUserRepository $userRepository,
        Permissions $permissions
    ) {
        $this->passwordEncoder = $passwordEncoder;
        $this->userRepository  = $userRepository;
        $this->permissions     = $permissions;
    }

    /**
     * @Route("/", name="user_index", methods={"GET"})
     */
    public function index(): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $this->userRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="user_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        // This should already be handled by the security firewall, but just in case...
        if ($this->getUser()->isAdmin() === false) {
            throw $this->createAccessDeniedException('Only admin users can create other users.');
        }

        $user = new RssUser();
        $user->setPassword('');

        $form = $this->createForm(RssUserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->userRepository->makeUser($user->getEmail(), $user->getPassword(), false);

            return $this->redirectToRoute('user_index');
        }

        return $this->render('user/new.html.twig', [
            'user'  => $user,
            'form'  => $form->createView(),
            'error' => $form->isSubmitted() === true && $form->isValid() === false,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="user_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, RssUser $user): Response
    {
        $form = $this->createForm(RssUserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->permissions->canEditContentFromUser($this->getUser(), $user) === false) {
                throw $this->createAccessDeniedException();
            }

            $entityManager = $this->getDoctrine()->getManager();

            $entityManager->persist($this->encodePassword($user));
            $entityManager->flush();

            return $this->redirectToRoute('user_index');
        }

        return $this->render('user/edit.html.twig', [
            'user'  => $user,
            'form'  => $form->createView(),
            'error' => $form->isSubmitted() === true && $form->isValid() === false,
        ]);
    }

    /**
     * Users cannot delete themselves.
     *
     * @Route("/{id}", name="user_delete", methods={"DELETE"})
     */
    public function delete(Request $request, RssUser $user): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            if ($this->getUser() === $user) {
                throw $this->createAccessDeniedException('Users cannot delete themselves.');
            }

            if ($this->getUser()->isAdmin() === false) {
                throw $this->createAccessDeniedException('Only admins can delete another user.');
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('user_index');
    }

    /**
     * Given a user entity, encode its password with the system encoder and return.
     */
    private function encodePassword(RssUser $user): RssUser
    {
        $rawPassword = $user->getPassword();

        $user->setPassword($this->passwordEncoder->encodePassword($user, $rawPassword));

        return $user;
    }
}
