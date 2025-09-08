<?php

namespace App\Controller;

use App\Repository\FilmRepository;
use App\Repository\UserRepository;
use App\Repository\SerieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin', name: 'admin_')]
class AdminController extends AbstractController
{

    #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(
        UserRepository $userRepository,
        SerieRepository $serieRepository,
        FilmRepository $filmRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $usersCount  = $userRepository->count([]);
        $seriesCount = $serieRepository->count([]);
        $filmsCount  = $filmRepository->count([]);

        // 5 derniers utilisateurs
        $lastUsers = $userRepository->findBy([], ['createdAt' => 'DESC'], 5);

        // 5 dernières séries
        $lastSeries = $serieRepository->findBy([], ['dateCreated' => 'DESC'], 5);

        return $this->render('admin/dashboard.html.twig', [
            'usersCount'  => $usersCount,
            'seriesCount' => $seriesCount,
            'filmsCount'  => $filmsCount,
            'lastUsers'   => $lastUsers,
            'lastSeries'  => $lastSeries,
        ]);
    }

    #[Route('/users', name: 'users')]
    public function users(UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $users = $userRepository->findAll();

        return $this->render('admin/users.html.twig', [
            'users' => $users,
        ]);
    }
}
