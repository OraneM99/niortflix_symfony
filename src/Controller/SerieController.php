<?php

namespace App\Controller;

use App\Entity\Serie;
use App\Repository\SerieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
            ->setFirstAirDate(new \DateTime('2017-05-02'))
            ->setLastAirDate(new \DateTime('2021-12-03'))
            ->setDateCreated(new \DateTime());

        $em->persist($serie);
        $em->flush();

        return new Response('Une série a été créée en base');
    }

    #[Route('/liste', name: '_liste')]
    public function liste(SerieRepository $serieRepository): Response
    {
        $series = $serieRepository->findAll();

        return $this->render('serie/liste.html.twig', [
            'series' => $series
        ]);
    }
}
