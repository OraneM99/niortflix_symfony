<?php

namespace App\Controller\api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Psr\Log\LoggerInterface;

#[Route('/api/auth', name: 'api_auth_')]
class AuthController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly ValidatorInterface $validator,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Connexion utilisateur avec JWT
     */
    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $this->logger->info('🔐 Tentative de connexion', ['email' => $data['email'] ?? 'non fourni']);

        // Validation des données
        if (empty($data['email']) || empty($data['password'])) {
            $this->logger->warning('❌ Données manquantes');
            return $this->json([
                'success' => false,
                'message' => 'Email et mot de passe requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = null;
        $identifier = $data['email'];

        // Recherche de l'utilisateur par email OU username
        $user = $this->em->getRepository(User::class)->findOneBy([
            'email' => $identifier
        ]);

        if (!$user) {
            // Essayer avec le username si pas trouvé par email
            $user = $this->em->getRepository(User::class)->findOneBy([
                'username' => $identifier
            ]);
        }

        // Vérification de l'existence et du mot de passe
        if (!$user || !$this->passwordHasher->isPasswordValid($user, $data['password'])) {
            $this->logger->warning('❌ Identifiants incorrects', ['identifier' => $identifier]);
            return $this->json([
                'success' => false,
                'message' => 'Identifiants incorrects'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Vérification que le compte est actif
        if (!$user->isActive()) {
            $this->logger->warning('❌ Compte inactif', ['user_id' => $user->getId()]);
            return $this->json([
                'success' => false,
                'message' => 'Votre compte a été désactivé'
            ], Response::HTTP_FORBIDDEN);
        }

        // Vérification que l'email est vérifié (DÉSACTIVÉ en développement)
        // Décommentez en production si vous voulez forcer la vérification d'email
        // TODO : SMTP pour vérification d'email
        /*
        if (!$user->isVerified()) {
            $this->logger->warning('❌ Email non vérifié', ['user_id' => $user->getId()]);
            return $this->json([
                'success' => false,
                'message' => 'Veuillez vérifier votre email avant de vous connecter'
            ], Response::HTTP_FORBIDDEN);
        }
        */

        // Génération du token JWT
        try {
            $token = $this->jwtManager->create($user);
        } catch (\Exception $e) {
            $this->logger->error('❌ Erreur génération JWT', ['error' => $e->getMessage()]);
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du token'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->logger->info('✅ Connexion réussie', [
            'user_id' => $user->getId(),
            'username' => $user->getUsername()
        ]);

        // ✅ STRUCTURE CORRECTE attendue par le frontend
        return $this->json([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'profilePicture' => $user->getProfilePicture()
                    ? '/uploads/profile_pictures/' . $user->getProfilePicture()
                    : null,
                'isActive' => $user->isActive(),
                'isVerified' => $user->isVerified()
            ]
        ], Response::HTTP_OK);
    }

    /**
     * Inscription utilisateur
     */
    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $this->logger->info('📝 Tentative d\'inscription', ['email' => $data['email'] ?? 'non fourni']);

        // Validation basique
        if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
            $this->logger->warning('❌ Données manquantes');
            return $this->json([
                'success' => false,
                'message' => 'Tous les champs sont requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Validation longueur mot de passe
        if (strlen($data['password']) < 6) {
            return $this->json([
                'success' => false,
                'message' => 'Le mot de passe doit contenir au moins 6 caractères'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier si l'email existe déjà
        $existingUser = $this->em->getRepository(User::class)->findOneBy([
            'email' => $data['email']
        ]);

        if ($existingUser) {
            $this->logger->warning('❌ Email déjà utilisé', ['email' => $data['email']]);
            return $this->json([
                'success' => false,
                'message' => 'Cet email est déjà utilisé'
            ], Response::HTTP_CONFLICT);
        }

        // Vérifier si le username existe déjà
        $existingUsername = $this->em->getRepository(User::class)->findOneBy([
            'username' => $data['username']
        ]);

        if ($existingUsername) {
            $this->logger->warning('❌ Username déjà pris', ['username' => $data['username']]);
            return $this->json([
                'success' => false,
                'message' => 'Ce nom d\'utilisateur est déjà pris'
            ], Response::HTTP_CONFLICT);
        }

        // Création du nouvel utilisateur
        $user = new User();
        $user->setUsername($data['username']);
        $user->setEmail($data['email']);
        $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));
        $user->setRoles(['ROLE_USER']);
        $user->setIsActive(true);
        $user->setIsVerified(true); // Auto-vérification ou false si vous voulez un système de vérification
        $user->setCreatedAt(new \DateTimeImmutable());

        // Validation avec les contraintes de l'entité
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            $this->logger->warning('❌ Erreur de validation', ['errors' => $errorMessages]);
            return $this->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $errorMessages
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->em->persist($user);
            $this->em->flush();
        } catch (\Exception $e) {
            $this->logger->error('❌ Erreur sauvegarde utilisateur', ['error' => $e->getMessage()]);
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la création du compte'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Génération du token JWT
        try {
            $token = $this->jwtManager->create($user);
        } catch (\Exception $e) {
            $this->logger->error('❌ Erreur génération JWT', ['error' => $e->getMessage()]);
            return $this->json([
                'success' => false,
                'message' => 'Compte créé mais erreur lors de la génération du token'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->logger->info('✅ Inscription réussie', [
            'user_id' => $user->getId(),
            'username' => $user->getUsername()
        ]);

        // ✅ STRUCTURE CORRECTE avec auto-login
        return $this->json([
            'success' => true,
            'message' => 'Inscription réussie',
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'isActive' => $user->isActive(),
                'isVerified' => $user->isVerified()
            ]
        ], Response::HTTP_CREATED);
    }

    /**
     * Récupérer le profil de l'utilisateur connecté
     */
    #[Route('/profile', name: 'profile', methods: ['GET'])]
    public function profile(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'success' => false,
                'message' => 'Non authentifié'
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'success' => true,
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'profilePicture' => $user->getProfilePicture()
                    ? '/uploads/profile_pictures/' . $user->getProfilePicture()
                    : null,
                'isActive' => $user->isActive(),
                'isVerified' => $user->isVerified(),
                'createdAt' => $user->getCreatedAt()?->format('Y-m-d H:i:s')
            ]
        ]);
    }

    /**
     * Déconnexion (côté client uniquement)
     */
    #[Route('/logout', name: 'logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        // Avec JWT, la déconnexion se fait côté client en supprimant le token
        return $this->json([
            'success' => true,
            'message' => 'Déconnexion réussie'
        ]);
    }

    /**
     * Vérification du token (optionnel, utile pour le frontend)
     */
    #[Route('/verify', name: 'verify', methods: ['GET'])]
    public function verify(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'success' => false,
                'valid' => false
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'success' => true,
            'valid' => true,
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles()
            ]
        ]);
    }
}