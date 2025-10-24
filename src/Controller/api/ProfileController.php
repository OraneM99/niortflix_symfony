<?php

namespace App\Controller\api;

use App\Entity\User;
use App\Repository\UserFavoriteRepository;
use App\Utils\FileManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/profile', name: 'api_profile_')]
class ProfileController extends AbstractController
{
    /**
     * Récupérer les informations du profil avec statistiques
     */
    #[Route('/', name: 'show', methods: ['GET'])]
    public function show(UserFavoriteRepository $favoriteRepo): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $favoritesCount = $favoriteRepo->countByUser($user);
        $recentFavorites = $favoriteRepo->findRecentByUser($user, 6);

        return $this->json([
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'isVerified' => $user->isVerified(),
                'isActive' => $user->isActive(),
                'profilePicture' => $user->getProfilePicture(),
                'createdAt' => $user->getCreatedAt()?->format('c')
            ],
            'stats' => [
                'favoritesCount' => $favoritesCount
            ],
            'recentFavorites' => array_map(function ($favorite) {
                return [
                    'id' => $favorite->getId(),
                    'tmdbId' => $favorite->getTmdbId(),
                    'title' => $favorite->getTitle(),
                    'posterPath' => $favorite->getPosterPath(),
                    'addedAt' => $favorite->getAddedAt()?->format('c')
                ];
            }, $recentFavorites)
        ]);
    }

    /**
     * Mettre à jour le profil
     */
    #[Route('/update', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        // Mise à jour du username (optionnel)
        if (isset($data['username']) && $data['username'] !== $user->getUsername()) {
            $user->setUsername($data['username']);
        }

        // Mise à jour de l'email (optionnel)
        if (isset($data['email']) && $data['email'] !== $user->getEmail()) {
            $user->setEmail($data['email']);
        }

        // Validation
        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $errorsString = [];
            foreach ($errors as $error) {
                $errorsString[] = $error->getMessage();
            }
            return $this->json([
                'error' => 'Erreur de validation',
                'details' => $errorsString
            ], Response::HTTP_BAD_REQUEST);
        }

        $em->flush();

        return $this->json([
            'message' => 'Profil mis à jour avec succès',
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'profilePicture' => $user->getProfilePicture()
            ]
        ]);
    }

    /**
     * Changer le mot de passe
     */
    #[Route('/change-password', name: 'change_password', methods: ['POST'])]
    public function changePassword(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['currentPassword']) || !isset($data['newPassword'])) {
            return $this->json([
                'error' => 'Mot de passe actuel et nouveau mot de passe requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier le mot de passe actuel
        if (!$passwordHasher->isPasswordValid($user, $data['currentPassword'])) {
            return $this->json([
                'error' => 'Mot de passe actuel incorrect'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Valider le nouveau mot de passe
        if (strlen($data['newPassword']) < 8) {
            return $this->json([
                'error' => 'Le nouveau mot de passe doit contenir au moins 8 caractères'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Mettre à jour le mot de passe
        $hashedPassword = $passwordHasher->hashPassword($user, $data['newPassword']);
        $user->setPassword($hashedPassword);
        $em->flush();

        return $this->json([
            'message' => 'Mot de passe changé avec succès'
        ]);
    }

    /**
     * Upload photo de profil
     */
    #[Route('/upload-picture', name: 'upload_picture', methods: ['POST'])]
    public function uploadPicture(
        Request $request,
        EntityManagerInterface $em,
        FileManager $fileManager
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $file = $request->files->get('profilePicture');

        if (!$file) {
            return $this->json([
                'error' => 'Aucun fichier fourni'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Valider le fichier avant upload
            $fileManager->validateImageFile($file, 5); // 5MB max

            // Upload avec suppression de l'ancien fichier
            $newFilename = $fileManager->uploadProfilePicture(
                $file,
                $user->getUsername(),
                $user->getProfilePicture() ? basename($user->getProfilePicture()) : null
            );

            // Mettre à jour l'utilisateur avec le chemin relatif
            $user->setProfilePicture('/uploads/profiles/' . $newFilename);
            $em->flush();

            return $this->json([
                'message' => 'Photo de profil mise à jour',
                'profilePicture' => '/uploads/profiles/' . $newFilename
            ]);
        } catch (FileException $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de l\'upload : ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Désactiver le compte
     */
    #[Route('/deactivate', name: 'deactivate', methods: ['POST'])]
    public function deactivate(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        // Demander confirmation avec le mot de passe
        if (!isset($data['password'])) {
            return $this->json([
                'error' => 'Mot de passe requis pour désactiver le compte'
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!$passwordHasher->isPasswordValid($user, $data['password'])) {
            return $this->json([
                'error' => 'Mot de passe incorrect'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user->setIsActive(false);
        $em->flush();

        return $this->json([
            'message' => 'Compte désactivé avec succès'
        ]);
    }
}