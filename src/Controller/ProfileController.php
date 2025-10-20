<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileType;
use App\Repository\UserFavoriteRepository;
use App\Utils\FileManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/profile', name: 'profile_')]
class ProfileController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(UserFavoriteRepository $favoriteRepo): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Utilisateur non valide.');
        }

        $favoritesCount = $favoriteRepo->countByUser($user);
        $recentFavorites = $favoriteRepo->findRecentByUser($user, 6);

        return $this->render('profile/index.html.twig', [
            'favoritesCount' => $favoritesCount,
            'recentFavorites' => $recentFavorites,
        ]);
    }

    #[Route('/edit', name: 'edit')]
    public function edit(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        FileManager $fileManager
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Utilisateur non valide.');
        }

        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $profilePictureFile = $form->get('profilePictureFile')->getData();

            if ($profilePictureFile) {
                $newFilename = $fileManager->uploadProfilePicture(
                    $profilePictureFile,
                    $user->getUsername(),
                    $user->getProfilePicture() ? basename($user->getProfilePicture()) : null
                );

                $user->setProfilePicture($newFilename);
            }

            // Gestion du mot de passe
            $currentPassword = $form->get('currentPassword')->getData();
            $plainPassword = $form->get('plainPassword')->getData();

            if ($plainPassword) {
                if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                    $form->get('currentPassword')->addError(
                        new FormError('Mot de passe actuel incorrect.')
                    );
                } else {
                    $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
                }
            }

            if ($form->isValid()) {
                $em->flush();
                $this->addFlash('success', 'Profil mis à jour avec succès ✅');
                return $this->redirectToRoute('profile_index');
            }
        }

        return $this->render('profile/edit.html.twig', [
            'profileForm' => $form
        ]);
    }

    #[Route('/favorites', name: 'favorites')]
    public function favorites(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // Rediriger vers la nouvelle route
        return $this->redirectToRoute('favorites_index');
    }

    #[Route('/data-privacy', name: 'data_privacy')]
    public function dataPrivacy(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        return $this->render('profile/data_privacy.html.twig');
    }

    #[Route('/security', name: 'security')]
    public function security(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        return $this->render('profile/security.html.twig');
    }

    #[Route('/delete', name: 'delete')]
    public function delete(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        return $this->render('profile/delete.html.twig');
    }

    #[Route('/desactive', name: 'desactive')]
    public function deactivate(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if ($user instanceof User) {
            $user->setIsActive(false);
            $em->flush();
            $this->addFlash('success', 'Votre compte a été désactivé.');
        }

        return $this->redirectToRoute('app_logout');
    }
}