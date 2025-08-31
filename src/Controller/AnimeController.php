<?php

namespace App\Controller;

use App\Entity\Anime;
use App\Form\AnimeType;
use App\Repository\AnimeRepository;
use App\Utils\FileManager;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/anime', name: 'anime')]
final class AnimeController extends AbstractController
{
    #[Route('/test', name: 'app_anime')]
    public function index(EntityManagerInterface $em): Response
    {
        $anime = new Anime();
        $anime->setName('Attack on Titan')
            ->setOverview('L\'humanité lutte contre les Titans ...')
            ->setStatus('returning')
            ->setVote(9.0)
            ->setPopularity(1200)
            ->setFirstAirDate(new DateTime('2013-04-07'))
            ->setLastAirDate(new DateTime('2023-03-29'))
            ->setDateCreated(new DateTime());

        $em->persist($anime);
        $em->flush();

        return new Response('Un anime a été créé en base');
    }

    #[Route('/liste/{page}', name: '_liste', requirements: ['page' => '\d+'], defaults: ['page' => 1])]
    public function liste(AnimeRepository $animeRepository, int $page, ParameterBagInterface $parameterBag, Request $request): Response
    {
        $nbPerPage = $parameterBag->get('anime')['nb_par_page'];
        $offset = ($page - 1) * $nbPerPage;

        $sort = $request->query->get('sort', null);
        $search = $request->query->get('search', null);

        $animes = $animeRepository->findAnimesWithQueryBuilder($offset, $nbPerPage, false, $sort, $search);
        $nbAnimes = $animeRepository->findAnimesWithQueryBuilder($offset, $nbPerPage, true, $sort, $search);

        $nbPages = ceil($nbAnimes[1] / $nbPerPage);

        return $this->render('anime/liste.html.twig', [
            'animes' => $animes,
            'page' => $page,
            'nb_pages' => $nbPages,
            'sort' => $sort
        ]);
    }

    #[Route('/detail/{id}', name: '_detail', requirements: ['id' => '\d+'])]
    public function detail(Anime $anime, Request $request): Response
    {
        $referer = $request->headers->get('referer');

        return $this->render('anime/detail.html.twig', [
            'anime' => $anime,
            'referer' => $referer,
        ]);
    }

    #[Route('/create', name: '_create')]
    public function create(Request $request, EntityManagerInterface $em, FileManager $fileManager): Response
    {
        $anime = new Anime();
        $animeForm = $this->createForm(AnimeType::class, $anime);
        $animeForm->handleRequest($request);

        if ($animeForm->isSubmitted() && $animeForm->isValid()) {
            $file = $animeForm->get('backdrop_file')->getData();
            $poster = $animeForm->get('poster_file')->getData();

            if ($file instanceof UploadedFile) {
                if ($name = $fileManager->upload($file, 'uploads/backdrops', $anime->getName())) {
                    $anime->setBackdrop($name);
                }
            }

            if ($poster instanceof UploadedFile) {
                if ($name = $fileManager->upload($poster, 'uploads/posters/animes', $anime->getName())) {
                    $anime->setPoster($name);
                }
            }

            $em->persist($anime);
            $em->flush();
            $this->addFlash('success', 'Votre anime a bien été enregistré !');
            return $this->redirectToRoute('anime_detail', ['id' => $anime->getId()]);
        }

        return $this->render('anime/edit.html.twig', [
            'animeForm' => $animeForm,
            'is_edit' => false,
            'anime' => $anime,
        ]);
    }

    #[Route('/update/{id}', name: '_update', requirements: ['id' => '\d+'])]
    public function update(Request $request, EntityManagerInterface $em, Anime $anime, FileManager $fileManager): Response
    {
        $animeForm = $this->createForm(AnimeType::class, $anime);
        $animeForm->handleRequest($request);

        if ($animeForm->isSubmitted() && $animeForm->isValid()) {
            $file = $animeForm->get('backdrop_file')->getData();
            if ($file instanceof UploadedFile) {
                if ($name = $fileManager->upload($file, 'uploads/backdrops/', $anime->getName(), $anime->getBackdrop())) {
                    $anime->setBackdrop($name);
                }
            }

            $poster = $animeForm->get('poster_file')->getData();
            if ($poster instanceof UploadedFile) {
                if ($name = $fileManager->uploadPoster($poster, 'uploads/posters/animes', $anime->getName(), $anime->getPoster())) {
                    $anime->setPoster($name);
                }
            }

            $em->flush();
            $this->addFlash('success', 'Un anime a été modifié avec succès.');
            return $this->redirectToRoute('anime_detail', ['id' => $anime->getId()]);
        }

        return $this->render('anime/edit.html.twig', [
            'animeForm' => $animeForm,
            'anime' => $anime,
            'is_edit' => true
        ]);
    }

    #[Route('/delete/{id}', name: '_delete', requirements: ['id' => '\d+'])]
    public function delete(Request $request, Anime $anime, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $anime->getId(), $request->get('token'))) {
            $em->remove($anime);
            $em->flush();
            $this->addFlash('success', 'L\'anime a bien été supprimé.');
        } else {
            $this->addFlash('danger', 'Problème lors de la suppression.');
        }

        return $this->redirectToRoute('anime_liste');
    }
}