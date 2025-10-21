<?php

namespace App\Controller\api;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api', name: 'api_auth_')]
class AuthController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator,
        private readonly UserRepository $userRepository
    ) {
    }

    /**
     * Inscription d'un nouvel utilisateur
     */
    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Validation des données
        if (!isset($data['username']) || !isset($data['email']) || !isset($data['password'])) {
            return $this->json([
                'error' => 'Les champs username, email et password sont requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier si l'utilisateur existe déjà
        if ($this->userRepository->findOneBy(['email' => $data['email']])) {
            return $this->json([
                'error' => 'Cet email est déjà utilisé'
            ], Response::HTTP_CONFLICT);
        }

        if ($this->userRepository->findOneBy(['username' => $data['username']])) {
            return $this->json([
                'error' => 'Ce nom d\'utilisateur est déjà pris'
            ], Response::HTTP_CONFLICT);
        }

        // Validation du mot de passe (minimum 6 caractères)
        if (strlen($data['password']) < 6) {
            return $this->json([
                'error' => 'Le mot de passe doit contenir au moins 6 caractères'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Créer l'utilisateur
        $user = new User();
        $user->setUsername($data['username']);
        $user->setEmail($data['email']);

        // Hasher le mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $data['password']
        );
        $user->setPassword($hashedPassword);

        // Définir le rôle par défaut
        $user->setRoles(['ROLE_USER']);
        $user->setIsActive(true);
        $user->setIsVerified(false); // À vérifier par email plus tard

        // Valider l'entité
        $errors = $this->validator->validate($user);
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

        // Sauvegarder
        $this->em->persist($user);
        $this->em->flush();

        return $this->json([
            'message' => 'Inscription réussie',
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail()
            ]
        ], Response::HTTP_CREATED);
    }

    /**
     * Connexion (le JWT est généré automatiquement par le firewall)
     */
    #[Route('/api/login', name: 'login', methods: ['POST'])]
    public function login(Request $request, RateLimiterFactory $loginLimiter): JsonResponse
    {
        $limiter = $loginLimiter->create($request->getClientIp());

        if (!$limiter->consume(1)->isAccepted()) {
            return $this->json([
                'error' => 'Trop de tentatives. Réessayez plus tard.'
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }
    }

        /**
     * Récupérer les informations de l'utilisateur connecté
     */
    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'isVerified' => $user->isVerified(),
            'profilePicture' => $user->getProfilePicture(),
            'createdAt' => $user->getCreatedAt()?->format('c')
        ]);
    }

    /**
     * Déconnexion (côté client, supprimer le token)
     */
    #[Route('/logout', name: 'logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        return $this->json([
            'message' => 'Déconnexion réussie. Supprimez le token côté client.'
        ]);
    }

    /**
     * Rafraîchir le token (optionnel)
     */
    #[Route('/token/refresh', name: 'token_refresh', methods: ['POST'])]
    public function refreshToken(): JsonResponse
    {
        // Pour implémenter le refresh token, il faut installer
        // composer require gesdinet/jwt-refresh-token-bundle
        return $this->json([
            'message' => 'Token refresh endpoint'
        ]);
    }
}