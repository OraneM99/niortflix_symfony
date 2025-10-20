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
        return $this->render('home/home.html.twig', [
            'tmdb_trending' => $this->serieManager->getTrendingSeries(6),
            'tmdb_top_rated' => $this->serieManager->getTopRatedSeries(6),
        ]);
    }
}