<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\UserFavoriteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin', name: 'admin_')]
class AdminController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(
        UserRepository $userRepository,
        UserFavoriteRepository $favoriteRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Statistiques globales
        $usersCount = $userRepository->count([]);
        $activeUsersCount = $userRepository->countActiveUsers();
        $verifiedUsersCount = $userRepository->count(['isVerified' => true]);

        // Derniers utilisateurs
        $lastUsers = $userRepository->findRecentUsers(10);

        return $this->render('admin/dashboard.html.twig', [
            'usersCount' => $usersCount,
            'activeUsersCount' => $activeUsersCount,
            'verifiedUsersCount' => $verifiedUsersCount,
            'lastUsers' => $lastUsers,
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

    #[Route('/notifications', name: 'notifications')]
    public function notifications(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // TODO : Implémenter la logique de notifications
        $notifications = [];

        return $this->render('admin/notifications.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    #[Route('/settings', name: 'settings')]
    public function settings(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // TODO : Implémenter la gestion des paramètres
        $settings = [];

        return $this->render('admin/settings.html.twig', [
            'settings' => $settings,
        ]);
    }
}