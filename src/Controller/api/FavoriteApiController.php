<?php

namespace App\Controller\api;

use App\Entity\UserFavorite;
use App\Repository\UserFavoriteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $favorites = $this->favoriteRepo->findBy(
            ['user' => $user],
            ['addedAt' => 'DESC']
        );

        return $this->json(array_map(function($fav) {
            return [
                'id' => $fav->getId(),
                'tmdbId' => $fav->getTmdbId(),
                'serieName' => $fav->getSerieName(),
                'poster' => $fav->getPoster(),
                'vote' => $fav->getVote(),
                'year' => $fav->getYear(),
                'addedAt' => $fav->getAddedAt()->format('c')
            ];
        }, $favorites));
    }

    /**
     * Ajouter aux favoris
     */
    #[Route('', name: 'add', methods: ['POST'])]
    public function add(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['tmdb_id']) || !isset($data['serie_name'])) {
            return $this->json([
                'error' => 'Les champs tmdb_id et serie_name sont requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier si déjà en favoris
        $existing = $this->favoriteRepo->findOneBy([
            'user' => $user,
            'tmdbId' => $data['tmdb_id']
        ]);

        if ($existing) {
            return $this->json([
                'message' => 'Déjà en favoris',
                'favorite' => [
                    'id' => $existing->getId(),
                    'tmdbId' => $existing->getTmdbId(),
                    'serieName' => $existing->getSerieName(),
                    'poster' => $existing->getPoster(),
                    'vote' => $existing->getVote(),
                    'year' => $existing->getYear(),
                    'addedAt' => $existing->getAddedAt()->format('c')
                ]
            ]);
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

        return $this->json([
            'message' => 'Ajouté aux favoris',
            'favorite' => [
                'id' => $favorite->getId(),
                'tmdbId' => $favorite->getTmdbId(),
                'serieName' => $favorite->getSerieName(),
                'poster' => $favorite->getPoster(),
                'vote' => $favorite->getVote(),
                'year' => $favorite->getYear(),
                'addedAt' => $favorite->getAddedAt()->format('c')
            ]
        ], Response::HTTP_CREATED);
    }

    /**
     * Retirer des favoris
     */
    #[Route('/{tmdbId}', name: 'remove', methods: ['DELETE'])]
    public function remove(int $tmdbId): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $favorite = $this->favoriteRepo->findOneBy([
            'user' => $user,
            'tmdbId' => $tmdbId
        ]);

        if (!$favorite) {
            return $this->json(['error' => 'Favori non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($favorite);
        $this->em->flush();

        return $this->json([
            'message' => 'Retiré des favoris'
        ]);
    }

    /**
     * Vérifier si en favoris
     */
    #[Route('/check/{tmdbId}', name: 'check', methods: ['GET'])]
    public function check(int $tmdbId): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['isFavorite' => false]);
        }

        $favorite = $this->favoriteRepo->findOneBy([
            'user' => $user,
            'tmdbId' => $tmdbId
        ]);

        return $this->json(['isFavorite' => $favorite !== null]);
    }
}