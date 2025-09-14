<?php

namespace App\Controller;

use App\Entity\Contributor;
use App\Form\ContributorType;
use App\Repository\ContributorRepository;
use App\Repository\FilmRepository;
use App\Repository\UserRepository;
use App\Repository\SerieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin', name: 'admin_')]
class AdminController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(
        Request $request,
        UserRepository $userRepository,
        SerieRepository $serieRepository,
        FilmRepository $filmRepository,
        ContributorRepository $contributorRepository,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Statistiques globales
        $usersCount  = $userRepository->count([]);
        $seriesCount = $serieRepository->count([]);
        $filmsCount  = $filmRepository->count([]);

        // Derniers utilisateurs
        $lastUsers = $userRepository->findBy([], ['createdAt' => 'DESC'], 10);

        // Dernières séries
        $lastSeries = $serieRepository->findBy([], ['dateCreated' => 'DESC'], 5);

        // Derniers contributeurs
        $lastContributors = $contributorRepository->createQueryBuilder('c')
            ->orderBy('c.id', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        // Formulaire d’ajout de contributeur directement dans le dashboard
        $newContributor = new Contributor();
        $form = $this->createForm(ContributorType::class, $newContributor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($newContributor);
            $em->flush();

            $this->addFlash('success', 'Contributeur ajouté avec succès !');
            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->render('admin/dashboard.html.twig', [
            'usersCount'       => $usersCount,
            'seriesCount'      => $seriesCount,
            'filmsCount'       => $filmsCount,
            'lastUsers'        => $lastUsers,
            'lastSeries'       => $lastSeries,
            'lastContributors' => $lastContributors,
            'contributorForm'  => $form->createView(),
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
