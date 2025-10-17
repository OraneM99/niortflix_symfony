<?php

namespace App\Controller\api;

use App\Entity\Episode;
use App\Entity\Serie;
use App\Entity\User;
use App\Entity\UserEpisode;
use App\Repository\UserEpisodeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/episode', name: 'episode_')]
class EpisodeTrackingController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserEpisodeRepository $userEpisodeRepo
    ) {
    }

    /**
     * Affiche les saisons et épisodes d'une série
     */
    #[Route('/serie/{id}/seasons', name: 'seasons', requirements: ['id' => '\d+'])]
    public function seasons(Serie $serie): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();

        // Récupère tous les épisodes vus par l'utilisateur pour cette série
        $watchedEpisodes = [];
        foreach ($serie->getSeasons() as $season) {
            foreach ($season->getEpisodes() as $episode) {
                $userEpisode = $this->userEpisodeRepo->findOneBy([
                    'user' => $user,
                    'episode' => $episode
                ]);

                if ($userEpisode && $userEpisode->isWatched()) {
                    $watchedEpisodes[$episode->getId()] = true;
                }
            }
        }

        return $this->render('episode/seasons.html.twig', [
            'serie' => $serie,
            'seasons' => $serie->getSeasons(),
            'watchedEpisodes' => $watchedEpisodes,
        ]);
    }

    /**
     * Marque un épisode comme vu/non vu
     */
    #[Route('/toggle/{id}', name: 'toggle', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function toggleWatched(Episode $episode): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();

        $userEpisode = $this->userEpisodeRepo->findOneBy([
            'user' => $user,
            'episode' => $episode
        ]);

        if (!$userEpisode) {
            // Crée l'entrée si elle n'existe pas
            $userEpisode = new UserEpisode();
            $userEpisode->setUser($user)
                ->setEpisode($episode)
                ->setWatched(true);
            $this->em->persist($userEpisode);
        } else {
            // Inverse l'état
            $userEpisode->setWatched(!$userEpisode->isWatched());
        }

        $this->em->flush();

        return $this->json([
            'success' => true,
            'watched' => $userEpisode->isWatched(),
            'episodeId' => $episode->getId()
        ]);
    }

    /**
     * Marque toute une saison comme vue
     */
    #[Route('/season/{id}/mark-all', name: 'mark_season', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function markSeasonAsWatched(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();

        $season = $this->em->getRepository(\App\Entity\Season::class)->find($id);
        if (!$season) {
            return $this->json(['success' => false, 'message' => 'Saison introuvable'], 404);
        }

        $marked = 0;
        foreach ($season->getEpisodes() as $episode) {
            $userEpisode = $this->userEpisodeRepo->findOneBy([
                'user' => $user,
                'episode' => $episode
            ]);

            if (!$userEpisode) {
                $userEpisode = new UserEpisode();
                $userEpisode->setUser($user)
                    ->setEpisode($episode)
                    ->setWatched(true);
                $this->em->persist($userEpisode);
                $marked++;
            } elseif (!$userEpisode->isWatched()) {
                $userEpisode->setWatched(true);
                $marked++;
            }
        }

        $this->em->flush();

        return $this->json([
            'success' => true,
            'marked' => $marked,
            'message' => sprintf('%d épisode(s) marqué(s) comme vu(s)', $marked)
        ]);
    }

    /**
     * Récupère les statistiques de visionnage pour une série
     */
    #[Route('/serie/{id}/stats', name: 'stats', requirements: ['id' => '\d+'])]
    public function stats(Serie $serie): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();

        $totalEpisodes = 0;
        $watchedCount = 0;

        foreach ($serie->getSeasons() as $season) {
            foreach ($season->getEpisodes() as $episode) {
                $totalEpisodes++;

                $userEpisode = $this->userEpisodeRepo->findOneBy([
                    'user' => $user,
                    'episode' => $episode
                ]);

                if ($userEpisode && $userEpisode->isWatched()) {
                    $watchedCount++;
                }
            }
        }

        $percentage = $totalEpisodes > 0 ? round(($watchedCount / $totalEpisodes) * 100) : 0;

        return $this->json([
            'total' => $totalEpisodes,
            'watched' => $watchedCount,
            'remaining' => $totalEpisodes - $watchedCount,
            'percentage' => $percentage
        ]);
    }

    /**
     * Prochain épisode à regarder
     */
    #[Route('/serie/{id}/next', name: 'next', requirements: ['id' => '\d+'])]
    public function nextEpisode(Serie $serie): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();

        // Parcourt les saisons dans l'ordre
        foreach ($serie->getSeasons() as $season) {
            foreach ($season->getEpisodes() as $episode) {
                $userEpisode = $this->userEpisodeRepo->findOneBy([
                    'user' => $user,
                    'episode' => $episode
                ]);

                // Si pas vu, c'est le prochain
                if (!$userEpisode || !$userEpisode->isWatched()) {
                    return $this->json([
                        'found' => true,
                        'season' => $season->getSeasonNumber(),
                        'episode' => $episode->getEpisodeNumber(),
                        'name' => $episode->getName(),
                        'overview' => $episode->getOverview()
                    ]);
                }
            }
        }

        return $this->json([
            'found' => false,
            'message' => 'Tous les épisodes ont été vus !'
        ]);
    }
}