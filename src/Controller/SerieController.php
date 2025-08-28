<?php

namespace App\Controller;

use App\Entity\Serie;
use App\Form\SerieType;
use App\Repository\SerieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
            ->setFirstAirDate(new \DateTime('2017-05-02'))
            ->setLastAirDate(new \DateTime('2021-12-03'))
            ->setDateCreated(new \DateTime());

        $em->persist($serie);
        $em->flush();

        return new Response('Une série a été créée en base');
    }

    #[Route('/liste/{page}', name: '_liste', requirements: ['page' => '\d+'], defaults: ['page' => 1])]
    public function liste(SerieRepository $serieRepository, int $page, ParameterBagInterface $parameterBag): Response
    {
        $nbPerPage = $parameterBag->get('serie')['nb_par_page'];
        $offset = ($page - 1) * $nbPerPage;

//      $series = $serieRepository->findAll();
//        $series = $serieRepository->findBy(
//            // ['status' => 'ended', 'genres' => 'Romance'],
//            [],
//            ['name' => 'ASC'],
//            $nbPerPage,
//            $offset
//        );

        $series = $serieRepository->findSeriesWithQueryBuilder($offset, $nbPerPage);
//      $series = $serieRepository->findSeriesWithDQL($nbPerPage, $offset, "Drama");

        $nbSeries = $serieRepository->findSeriesWithQueryBuilder($offset, $nbPerPage, true);

        $nbPages = ceil($nbSeries[1] / $nbPerPage);


        return $this->render('serie/liste.html.twig', [
            'series' => $series,
            'page' => $page,
            'nb_pages' => $nbPages,
        ]);
    }

    #[Route('/detail/{id}', name: '_detail', requirements: ['id' => '\d+'])] // On utilise le param converter pour récupérer l'id
    public function detail(Serie $serie): Response
    {
        return $this->render('serie/detail.html.twig', [
            'serie' => $serie,
        ]);
    }

    #[Route('/create', name: '_create')]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        $serie = new Serie();
        $serieForm = $this->createForm(SerieType::class, $serie);
        $serieForm->handleRequest($request);

        if ($serieForm->isSubmitted() && $serieForm->isValid()) {
            $em->persist($serie);
            $em->flush();

            $this->addFlash('success', 'Une série a été créée en base');

            return $this->redirectToRoute('serie_detail', ['id' => $serie->getId()]);
        }

        return $this->render('serie/edit.html.twig',
            ['serieForm' => $serieForm]);
    }

    #[Route('/update/{id]', name: '_update', requirements: ['id' => '\d+'])]
    public function update(Request $request, EntityManagerInterface $em, Serie $serie): Response
    {
        $serieForm = $this->createForm(SerieType::class, $serie);
        $serieForm->handleRequest($request);

        if ($serieForm->isSubmitted() && $serieForm->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Une série a été modifié avec succès.');
            return $this->redirectToRoute('serie_detail', ['id' => $serie->getId()]);
        }

        return $this->render('serie/edit.html.twig',
            ['serieForm' => $serieForm]);
    }
}
