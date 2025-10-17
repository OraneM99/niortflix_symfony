<?php

namespace App\Controller;

use App\Service\SerieManagerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    public function __construct(
        private readonly SerieManagerService $serieManager
    ) {
    }

    #[Route('/', name: 'serie_home')]
    public function index(): Response
    {
        // Récupère un mix de séries locales et API
        $data = $this->serieManager->getHomePageSeries();

        return $this->render('home/home.html.twig', [
            'local_recent' => $data['local_recent'],
            'local_popular' => $data['local_popular'],
            'tmdb_trending' => $data['tmdb_trending'],
            'tmdb_top_rated' => $data['tmdb_top_rated'],
        ]);
    }
}