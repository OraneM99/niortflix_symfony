<?php

namespace App\Controller;

use App\Entity\Film;
use App\Form\FilmType;
use App\Repository\ContributorRepository;
use App\Repository\FilmRepository;
use App\Repository\GenreRepository;
use App\Utils\FileManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/film', name: 'film_')]
final class FilmController extends AbstractController
{
    #[Route('/liste/{page}',name: 'liste', requirements: ['page' => '\d+'], defaults: ['page' => 1])]
    public function liste(FilmRepository $filmRepository, GenreRepository $genreRepository, int $page, ParameterBagInterface $parameterBag, Request $request): Response
    {
        $nbPerPage = $parameterBag->get('film')['nb_par_page'];
        $offset = ($page - 1) * $nbPerPage;

        $sort = $request->query->get('sort', null);
        $search = $request->query->get('search', null);

        $films = $filmRepository->findFilmsWithQueryBuilder($offset, $nbPerPage, false, $sort, $search);
        $genres = $genreRepository->findAllOrderedByName();

        $nbFilms = $filmRepository->findFilmsWithQueryBuilder($offset, $nbPerPage, true, $sort, $search);
        $nbPages = ceil($nbFilms[1] / $nbPerPage);

        return $this->render('film/liste.html.twig', [
            'films' => $films,
            'page' => $page,
            'nb_pages' => $nbPages,
            'sort' => $sort,
            'genres' => $genres,
        ]);
    }

    #[Route('/detail/{id}', name: '_detail', requirements: ['id' => '\d+'])]
    public function detail(Film $film, Request $request, ContributorRepository $contributorRepository): Response
    {
        $contributors = $contributorRepository->findByFilm($film);

        return $this->render('film/detail.html.twig', [
            'film' => $film,
            'contributors' => $contributors
        ]);
    }

    #[Route('/create', name: 'create', methods: ['GET','POST'])]
    public function create(Request $request, EntityManagerInterface $em, FileManager $fileManager): Response
    {
        $film = new Film();
        $filmForm = $this->createForm(FilmType::class, $film);
        $filmForm->handleRequest($request);

        if ($filmForm->isSubmitted() && $filmForm->isValid()) {
            $file = $filmForm->get('backdrop_file')->getData();
            $poster = $filmForm->get('poster_file')->getData();

            if ($file instanceof UploadedFile) {
                if ($name = $fileManager->upload($file, 'uploads/backdrops', $film->getName())) {
                    $film->setBackdrop($name);
                }
            }

            if ($poster instanceof UploadedFile) {
                if ($name = $fileManager->upload($poster, 'uploads/posters/films', $film->getName())) {
                    $film->setPoster($name);
                }
            }

            $em->persist($film);
            $em->flush();
            $this->addFlash('success', 'Votre film a bien été enregistré !');
            return $this->redirectToRoute('film_detail', ['id' => $film->getId()]);
        }

        return $this->render('film/edit.html.twig', [
            'filmForm' => $filmForm,
            'is_edit' => false,
        ]);
    }

    #[Route('/update/{id}', name: 'update', requirements: ['id' => '\d+'])]
    public function update(
        Request $request,
        Film $film,
        EntityManagerInterface $em,
        FileManager $fileManager): Response
    {
        $filmForm = $this->createForm(FilmType::class, $film);
        $filmForm->handleRequest($request);

        if ($filmForm->isSubmitted() && $filmForm->isValid()) {
            $file = $filmForm->get('backdrop_file')->getData();

            if ($file instanceof UploadedFile) {
                if ($name = $fileManager->upload($file, 'uploads/backdrops', $film->getName())) {
                    $film->setBackdrop($name);
                }
            }

            $poster = $filmForm->get('poster_file')->getData();
            if ($poster instanceof UploadedFile) {
                if ($name = $fileManager->upload($poster, 'uploads/posters/films', $film->getName())) {
                    $film->setPoster($name);
                }
            }

            $em->flush();
            $this->addFlash('success', 'Le film a bien été mis à jour.');
            return $this->redirectToRoute('film_detail', ['id' => $film->getId()]);
        }

        return $this->render('film/edit.html.twig', [
            'filmForm' => $filmForm,
            'is_edit' => true,
        ]);
    }

    #[Route('/delete/{id}', name: 'delete', requirements: ['id' => '\d+'])]
    public function delete(
        Request $request,
        Film $film,
        EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$film->getId(), $request->get('_token'))) {

            $entityManager->remove($film);
            $entityManager->flush();

            $this->addFlash('success', 'Le film a bien été supprimé.');
        } else {
            $this->addFlash('danger', 'Problème lors de la suppression.');
        }

        return $this->redirectToRoute('film_liste');
    }

}
