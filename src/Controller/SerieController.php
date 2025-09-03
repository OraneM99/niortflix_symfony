<?php

namespace App\Controller;

use App\Entity\Contributor;
use App\Entity\Serie;
use App\Form\ContributorType;
use App\Form\SerieType;
use App\Repository\ContributorRepository;
use App\Repository\GenreRepository;
use App\Repository\SerieRepository;
use App\Utils\FileManager;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/serie', name: 'serie')]
final class SerieController extends AbstractController
{
    #[Route('/test', name: 'app_serie')]
    public function index(EntityManagerInterface $em): Response
    {
        $serie = new Serie();
        $serie->setName('La Casa de Papel')
            ->setOverview('Beaucoup de fil à retorde pour el Professor ...')
            ->setStatus('ended')
            ->setVote(8.4)
            ->setPopularity(899.2)
            ->setFirstAirDate(new DateTime('2017-05-02'))
            ->setLastAirDate(new DateTime('2021-12-03'))
            ->setDateCreated(new DateTime());

        $em->persist($serie);
        $em->flush();

        return new Response('Une série a été créée en base');
    }

    #[Route('/liste/{page}', name: '_liste', requirements: ['page' => '\d+'], defaults: ['page' => 1])]
    public function liste(SerieRepository $serieRepository,
                          GenreRepository $genreRepository,
                          int $page, ParameterBagInterface $parameterBag, Request $request): Response
    {
        $nbPerPage = $parameterBag->get('serie')['nb_par_page'];
        $offset = ($page - 1) * $nbPerPage;

        $sort = $request->query->get('sort', null);

        $search = $request->query->get('search', null);

        $series = $serieRepository->findSeriesWithQueryBuilder($offset, $nbPerPage, false, $sort, $search);
        $genres = $genreRepository->findAllOrderedByName();

        $nbSeries = $serieRepository->findSeriesWithQueryBuilder($offset, $nbPerPage, true, $sort, $search);

        $nbPages = ceil($nbSeries[1] / $nbPerPage);


        return $this->render('serie/liste.html.twig', [
            'series' => $series,
            'page' => $page,
            'nb_pages' => $nbPages,
            'sort' => $sort,
            'genres' => $genres
        ]);
    }

    #[Route('/detail/{id}', name: '_detail', requirements: ['id' => '\d+'])]
    public function detail(Serie $serie, ContributorRepository $contributorRepository): Response
    {
        $contributors = $contributorRepository->findBySerie($serie);

        return $this->render('serie/detail.html.twig', [
            'serie' => $serie,
            'contributors' => $contributors
        ]);
    }

    #[Route('/create', name: '_create')]
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


    #[Route('/update/{id}', name: '_update', requirements: ['id' => '\d+'])]
    public function update(Request $request, EntityManagerInterface $em, Serie $serie, FileManager $fileManager): Response
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

    #[Route('/contributor/create', name: 'contributor_create')]
    public function createContributor(Request $request, EntityManagerInterface $em): Response
    {
        $contributor = new Contributor();
        $form = $this->createForm(ContributorType::class, $contributor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($contributor);
            $em->flush();

            $this->addFlash('success', 'Le contributeur a été ajouté avec succès !');
            return $this->redirectToRoute('serie_liste');
        }

        return $this->render('contributor/edit.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/delete/{id}', name: '_delete', requirements: ['id' => '\d+'])]
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
