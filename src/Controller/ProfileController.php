<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        return $this->render('profile/profile.html.twig');
    }

    #[Route('/profile/edit', name: 'app_profile_edit')]
    public function edit(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Récupère le mot de passe actuel saisi
            $currentPassword = $form->get('currentPassword')->getData();
            // Récupère le nouveau mot de passe
            $plainPassword = $form->get('plainPassword')->getData();

            if ($plainPassword) {
                // Vérification que l'ancien mot de passe est correct
                if (! $passwordHasher->isPasswordValid($user, $currentPassword)) {
                    $form->get('currentPassword')->addError(
                        new \Symfony\Component\Form\FormError('Mot de passe actuel incorrect.')
                    );
                } else {
                    // Hash du nouveau mot de passe et sauvegarde
                    $user->setPassword(
                        $passwordHasher->hashPassword($user, $plainPassword)
                    );
                }
            }

            // Si aucune erreur supplémentaire, on flush
            if ($form->isValid()) {
                $em->flush();
                $this->addFlash('success', 'Profil mis à jour avec succès ✅');
                return $this->redirectToRoute('app_profile');
            }
        }

        return $this->render('profile/profile_edit.html.twig', [
            'profileForm' => $form->createView(),
        ]);
    }
}
