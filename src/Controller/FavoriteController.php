<?php

namespace App\Controller;

use App\Entity\UserFavorite;
use App\Repository\UserFavoriteRepository;
use App\Service\TmdbService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/favorites', name: 'favorites_')]
class FavoriteController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserFavoriteRepository $favoriteRepo,
        private readonly TmdbService $tmdbService
    ) {
    }

    /**
     * Liste des favoris de l'utilisateur
     */
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $favorites = $this->favoriteRepo->findByUser($this->getUser());

        return $this->render('favorites/index.html.twig', [
            'favorites' => $favorites,
        ]);
    }

    /**
     * Toggle favori (AJAX)
     */
    #[Route('/toggle/{tmdbId}', name: 'toggle', methods: ['POST'])]
    public function toggle(int $tmdbId, Request $request): JsonResponse
    {
        if (!$this->getUser()) {
            return new JsonResponse([
                'success' => false,
                'redirect' => $this->generateUrl('app_login')
            ], 401);
        }

        $user = $this->getUser();

        // Vérifier si déjà en favoris
        $existing = $this->favoriteRepo->findOneBy([
            'user' => $user,
            'tmdbId' => $tmdbId
        ]);

        if ($existing) {
            // Retirer des favoris
            $this->em->remove($existing);
            $this->em->flush();

            return new JsonResponse([
                'success' => true,
                'action' => 'removed',
                'message' => 'Retiré des favoris'
            ]);
        }

        // Récupérer les infos depuis l'API
        $serieData = $this->tmdbService->getSerie($tmdbId);

        if (!$serieData) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Série introuvable'
            ], 404);
        }

        // Ajouter aux favoris
        $favorite = new UserFavorite();
        $favorite->setUser($user)
            ->setTmdbId($tmdbId)
            ->setSerieName($serieData['name'] ?? 'Titre inconnu')
            ->setPoster($this->tmdbService->getImageUrl($serieData['poster_path'] ?? null));

        $this->em->persist($favorite);
        $this->em->flush();

        return new JsonResponse([
            'success' => true,
            'action' => 'added',
            'message' => 'Ajouté aux favoris'
        ]);
    }

    /**
     * Vérifier si une série est en favoris (AJAX)
     */
    #[Route('/check/{tmdbId}', name: 'check', methods: ['GET'])]
    public function check(int $tmdbId): JsonResponse
    {
        if (!$this->getUser()) {
            return new JsonResponse(['is_favorite' => false]);
        }

        $isFavorite = $this->favoriteRepo->isFavorite($this->getUser(), $tmdbId);

        return new JsonResponse(['is_favorite' => $isFavorite]);
    }
}