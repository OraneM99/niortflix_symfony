<?php

namespace App\Controller;

use App\Entity\Serie;
use App\Form\SerieType;
use App\Repository\ContributorRepository;
use App\Repository\GenreRepository;
use App\Repository\SerieRepository;
use App\Utils\FileManager;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/serie', name: 'serie_')]
final class SerieController extends AbstractController
{
    #[Route('/liste', name: 'liste')]
    public function liste(
        SerieRepository $serieRepository,
        GenreRepository $genreRepository,
        PaginatorInterface $paginator,
        Request $request,
        ParameterBagInterface $parameterBag
    ): Response {
        $sort = $request->query->get('sort', null);
        $search = $request->query->get('search', null);

        $query = $serieRepository->getQueryForSeries($sort, $search);

        $series = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            $parameterBag->get('serie')['nb_par_page']
        );

        $genres = $genreRepository->findAllOrderedByName();

        return $this->render('serie/liste.html.twig', [
            'series' => $series,
            'sort' => $sort,
            'genres' => $genres
        ]);
    }

    #[Route('/detail/{id}', name: 'detail', requirements: ['id' => '\d+'])]
    public function detail(Serie $serie, ContributorRepository $contributorRepository): Response
    {
        $contributors = $contributorRepository->findBySerie($serie);

        return $this->render('serie/detail.html.twig', [
            'serie' => $serie,
            'contributors' => $contributors
        ]);
    }

    #[Route('/favorite/{id}', name: 'favorite', requirements: ['id' => '\d+'])]
    public function favorite(Serie $serie, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('warning', 'Vous devez être connecté pour ajouter une série aux favoris.');
            return $this->redirectToRoute('app_login');
        }

        if ($user->hasFavoriteSerie($serie)) {
            $user->removeFavoriteSerie($serie);
            $this->addFlash('info', 'La série a été retirée de vos favoris.');
        } else {
            $user->addFavoriteSerie($serie);
            $this->addFlash('success', 'La série a été ajoutée à vos favoris.');
        }

        $em->flush();

        return $this->redirectToRoute('serie_detail', ['id' => $serie->getId()]);
    }

    #[Route('/create', name: 'create')]
    public function create(Request $request, EntityManagerInterface $em, FileManager $fileManager): Response
    {
        $serie = new Serie();
        $serieForm = $this->createForm(SerieType::class, $serie);
        $serieForm->handleRequest($request);

        if ($serieForm->isSubmitted() && $serieForm->isValid()) {
            $file = $serieForm->get('backdrop_file')->getData();
            $poster = $serieForm->get('poster_file')->getData();

            if ($file instanceof UploadedFile) {
                if ($name = $fileManager->upload($file, 'uploads/backdrops', $serie->getName())) {
                    $serie->setBackdrop($name);
                }
            }

            if ($poster instanceof UploadedFile) {
                if ($name = $fileManager->upload($poster, 'uploads/posters/series', $serie->getName())) {
                    $serie->setPoster($name);
                }
            }

            $em->persist($serie);
            $em->flush();
            $this->addFlash('success', 'Votre série a bien été enregistrée !');
            return $this->redirectToRoute('serie_detail', ['id' => $serie->getId()]);

        }

        return $this->render('serie/edit.html.twig', [
            'serieForm' => $serieForm,
            'is_edit' => false
        ]);
    }


    #[Route('/update/{id}', name: 'update', requirements: ['id' => '\d+'])]
    public function update(Request $request,
                           EntityManagerInterface $em,
                           Serie $serie,
                           FileManager $fileManager): Response
    {
        $serieForm = $this->createForm(SerieType::class, $serie);
        $serieForm->handleRequest($request);

        if ($serieForm->isSubmitted() && $serieForm->isValid()) {
            $file = $serieForm->get('backdrop_file')->getData();
            if ($file instanceof UploadedFile) {
                if ($name = $fileManager->upload($file, 'uploads/backdrops/', $serie->getName(), $serie->getBackdrop())) {
                    $serie->setBackdrop($name);
                }
            }

            $poster = $serieForm->get('poster_file')->getData();
            if ($poster instanceof UploadedFile) {
                if ($name = $fileManager->uploadPoster($poster, 'uploads/posters/series', $serie->getName(), $serie->getPoster())) {
                    $serie->setPoster($name);
                }
            }

            $em->flush();
            $this->addFlash('success', 'Une série a été modifié avec succès.');
            return $this->redirectToRoute('serie_detail', ['id' => $serie->getId()]);
        }

        return $this->render('serie/edit.html.twig',
            ['serieForm' => $serieForm,
                'is_edit' => true]);
    }

    #[Route('/delete/{id}', name: 'delete', requirements: ['id' => '\d+'])]
    public function delete(Request $request, Serie $serie, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $serie->getId(), $request->get('token'))) {

            $em->remove($serie);
            $em->flush();

            $this->addFlash('success', 'La série a bien été supprimée.');
        } else {
            $this->addFlash('danger', 'Problème lors de la suppression.');
        }

        return $this->redirectToRoute('serie_liste');
    }
}
