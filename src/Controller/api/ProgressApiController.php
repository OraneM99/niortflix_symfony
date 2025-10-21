<?php

namespace App\Controller\api;

use App\Entity\UserProgress;
use App\Repository\UserProgressRepository;
use App\Service\TmdbService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/progress', name: 'api_progress_')]
#[IsGranted('ROLE_USER')]
class ProgressApiController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserProgressRepository $progressRepo,
        private readonly TmdbService $tmdbService
    ) {
    }

    /**
     * Récupérer la progression pour une série
     */
    #[Route('/{tmdbId}', name: 'serie', methods: ['GET'])]
    public function getSerieProgress(int $tmdbId): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $progress = $this->progressRepo->findByUserAndSerie($user, $tmdbId);
        $watchedCount = $this->progressRepo->countWatchedEpisodes($user, $tmdbId);
        $nextEpisode = $this->progressRepo->findNextEpisodeToWatch($user, $tmdbId);

        $data = [];
        foreach ($progress as $p) {
            $key = "s{$p->getSeasonNumber()}e{$p->getEpisodeNumber()}";
            $data[$key] = [
                'watched' => $p->isWatched(),
                'progress' => $p->getProgress(),
                'watchedAt' => $p->getWatchedAt()?->format('c')
            ];
        }

        return $this->json([
            'progress' => $data,
            'stats' => [
                'watchedCount' => $watchedCount,
                'nextEpisode' => $nextEpisode
            ]
        ]);
    }

    /**
     * Marquer un épisode comme vu/non vu
     */
    #[Route('/{tmdbId}/season/{seasonNumber}/episode/{episodeNumber}', name: 'toggle', methods: ['POST'])]
    public function toggleEpisode(
        int $tmdbId,
        int $seasonNumber,
        int $episodeNumber,
        Request $request
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        $watched = $data['watched'] ?? true;

        $progress = $this->progressRepo->findEpisode($user, $tmdbId, $seasonNumber, $episodeNumber);

        if (!$progress) {
            $progress = new UserProgress();
            $progress->setUser($user)
                ->setTmdbId($tmdbId)
                ->setSeasonNumber($seasonNumber)
                ->setEpisodeNumber($episodeNumber);
        }

        $progress->setWatched($watched);

        if (isset($data['progress'])) {
            $progress->setProgress($data['progress']);
        }

        $this->em->persist($progress);
        $this->em->flush();

        return $this->json([
            'message' => $watched ? 'Épisode marqué comme vu' : 'Épisode marqué comme non vu',
            'watched' => $progress->isWatched(),
            'watchedAt' => $progress->getWatchedAt()?->format('c')
        ]);
    }

    /**
     * Marquer toute une saison comme vue
     */
    #[Route('/{tmdbId}/season/{seasonNumber}/mark-all', name: 'mark_season', methods: ['POST'])]
    public function markSeasonWatched(
        int $tmdbId,
        int $seasonNumber,
        Request $request
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        $watched = $data['watched'] ?? true;

        // Récupérer les infos de la saison depuis TMDb
        $seasonData = $this->tmdbService->getSeasonDetails($tmdbId, $seasonNumber);

        if (!$seasonData || !isset($seasonData['episodes'])) {
            return $this->json(['error' => 'Saison non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $count = 0;
        foreach ($seasonData['episodes'] as $episode) {
            $episodeNumber = $episode['episode_number'];

            $progress = $this->progressRepo->findEpisode($user, $tmdbId, $seasonNumber, $episodeNumber);

            if (!$progress) {
                $progress = new UserProgress();
                $progress->setUser($user)
                    ->setTmdbId($tmdbId)
                    ->setSeasonNumber($seasonNumber)
                    ->setEpisodeNumber($episodeNumber);
            }

            $progress->setWatched($watched);
            $this->em->persist($progress);
            $count++;
        }

        $this->em->flush();

        return $this->json([
            'message' => "$count épisodes marqués",
            'count' => $count
        ]);
    }

    /**
     * Mettre à jour la progression d'un épisode
     */
    #[Route('/{tmdbId}/season/{seasonNumber}/episode/{episodeNumber}/progress', name: 'update_progress', methods: ['PUT'])]
    public function updateProgress(
        int $tmdbId,
        int $seasonNumber,
        int $episodeNumber,
        Request $request
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['progress'])) {
            return $this->json(['error' => 'Progress requis'], Response::HTTP_BAD_REQUEST);
        }

        $progress = $this->progressRepo->findEpisode($user, $tmdbId, $seasonNumber, $episodeNumber);

        if (!$progress) {
            $progress = new UserProgress();
            $progress->setUser($user)
                ->setTmdbId($tmdbId)
                ->setSeasonNumber($seasonNumber)
                ->setEpisodeNumber($episodeNumber);
        }

        $progress->setProgress((int) $data['progress']);

        // Si plus de 90%, marquer comme vu automatiquement
        if (isset($data['duration']) && $progress->getProgressPercentage((int) $data['duration']) >= 90) {
            $progress->setWatched(true);
        }

        $this->em->persist($progress);
        $this->em->flush();

        return $this->json([
            'message' => 'Progression mise à jour',
            'progress' => $progress->getProgress(),
            'watched' => $progress->isWatched()
        ]);
    }
}