<?php

namespace App\Controller;

use App\Entity\Serie;
use App\Repository\SerieRepository;
use App\Service\TmdbService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/tmdb', name: 'tmdb_')]
final class TmdbImportController extends AbstractController
{
    private TmdbService $tmdb;

    public function __construct(TmdbService $tmdb)
    {
        $this->tmdb = $tmdb;
    }

    #[Route('/import/{tmdbId}', name: 'import')]
    public function import(int $tmdbId, EntityManagerInterface $em, SerieRepository $serieRepository): Response
    {
        // Vérifie si la série existe déjà
        $existing = $serieRepository->findOneBy(['tmdbId' => $tmdbId]);
        if ($existing) {
            $this->addFlash('warning', "La série {$existing->getName()} est déjà en base.");
            return $this->redirectToRoute('serie_detail', ['id' => $existing->getId()]);
        }

        // Récupère les données depuis TMDb
        $data = $this->tmdb->getSerie($tmdbId);

        $serie = new Serie();
        $serie->setTmdbId($tmdbId)
            ->setName($data['name'] ?? 'Nom inconnu')
            ->setOverview($data['overview'] ?? '')
            ->setStatus($data['status'] ?? '')
            ->setVote($data['vote_average'] ?? 0)
            ->setPopularity($data['popularity'] ?? 0)
            ->setFirstAirDate(isset($data['first_air_date']) ? new \DateTime($data['first_air_date']) : new \DateTime())
            ->setLastAirDate(isset($data['last_air_date']) ? new \DateTime($data['last_air_date']) : null);

        if (!empty($data['poster_path'])) {
            $serie->setPoster('https://image.tmdb.org/t/p/w500' . $data['poster_path']);
        }
        if (!empty($data['backdrop_path'])) {
            $serie->setBackdrop('https://image.tmdb.org/t/p/w500' . $data['backdrop_path']);
        }

        $em->persist($serie);
        $em->flush();

        $this->addFlash('success', "La série {$serie->getName()} a été importée depuis TMDb !");
        return $this->redirectToRoute('serie_detail', ['id' => $serie->getId()]);
    }
}
