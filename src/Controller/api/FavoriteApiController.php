<?php

namespace App\Controller\api;

use App\Entity\UserFavorite;
use App\Repository\UserFavoriteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/favorites', name: 'api_favorites_')]
#[IsGranted('ROLE_USER')]
class FavoriteApiController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserFavoriteRepository $favoriteRepo
    ) {
    }

    /**
     * Récupérer les favoris de l'utilisateur
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $user = $this->getUser();
        $favorites = $this->favoriteRepo->findBy(
            ['user' => $user],
            ['addedAt' => 'DESC']
        );

        return $this->json($favorites, 200, [], ['groups' => ['favorite:read']]);
    }

    /**
     * Ajouter aux favoris
     */
    #[Route('', name: 'add', methods: ['POST'])]
    public function add(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->getUser();

        // Vérifier si déjà en favoris
        $existing = $this->favoriteRepo->findOneBy([
            'user' => $user,
            'tmdbId' => $data['tmdb_id']
        ]);

        if ($existing) {
            return $this->json(['message' => 'Déjà en favoris'], 200);
        }

        $favorite = new UserFavorite();
        $favorite->setUser($user)
            ->setTmdbId($data['tmdb_id'])
            ->setSerieName($data['serie_name'])
            ->setPoster($data['poster'] ?? null)
            ->setVote($data['vote'] ?? null)
            ->setYear($data['year'] ?? null);

        $this->em->persist($favorite);
        $this->em->flush();

        return $this->json($favorite, 201, [], ['groups' => ['favorite:read']]);
    }

    /**
     * Retirer des favoris
     */
    #[Route('/{tmdbId}', name: 'remove', methods: ['DELETE'])]
    public function remove(int $tmdbId): JsonResponse
    {
        $user = $this->getUser();
        $favorite = $this->favoriteRepo->findOneBy([
            'user' => $user,
            'tmdbId' => $tmdbId
        ]);

        if (!$favorite) {
            return $this->json(['error' => 'Favori non trouvé'], 404);
        }

        $this->em->remove($favorite);
        $this->em->flush();

        return $this->json(['message' => 'Favori supprimé'], 200);
    }

    /**
     * Vérifier si en favoris
     */
    #[Route('/check/{tmdbId}', name: 'check', methods: ['GET'])]
    public function check(int $tmdbId): JsonResponse
    {
        $user = $this->getUser();
        $favorite = $this->favoriteRepo->findOneBy([
            'user' => $user,
            'tmdbId' => $tmdbId
        ]);

        return $this->json(['isFavorite' => $favorite !== null]);
    }
}