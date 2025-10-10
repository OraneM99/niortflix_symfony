<?php

namespace App\Controller;

use App\Repository\SerieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'serie_home')]
    public function index(SerieRepository $serieRepository): Response
    {
        $nouvellesSeries = $serieRepository->findRecentSeries(6);
        $seriesPopulaires = $serieRepository->findPopularSeries(6);

        return $this->render('home/home.html.twig', [
            'nouvelles_series' => $nouvellesSeries,
            'series_populaires' => $seriesPopulaires,
        ]);
    }
}