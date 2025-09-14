<?php

namespace App\Controller;

use App\Entity\Contributor;
use App\Entity\Serie;
use App\Form\ContributorType;
use App\Repository\ContributorRepository;
use App\Repository\SerieRepository;
use App\Utils\FileManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/contributor', name: 'contributor_')]
class ContributorController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(ContributorRepository $repo): Response
    {
        return $this->render('contributor/index.html.twig', [
            'contributors' => $repo->findAll()
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Contributor $contributor): Response
    {
        return $this->render('contributor/show.html.twig', [
            'contributor' => $contributor,
            'series'      => $contributor->getSeries()
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, FileManager $fileManager): Response
    {
        $contributor = new Contributor();
        $form = $this->createForm(ContributorType::class, $contributor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadedFile = $form->get('photo')->getData();

            if ($uploadedFile) {
                $fileName = $fileManager->uploadPhoto(
                    $uploadedFile,
                    $this->getParameter('contributor_photo_dir'),
                    $contributor->getName(),
                    $contributor->getPhoto()
                );
                $contributor->setPhoto($fileName);
            }

            $em->persist($contributor);
            $em->flush();

            $this->addFlash('success', 'Contributeur ajouté avec succès !');
            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->render('contributor/new.html.twig', [
            'contributor' => $contributor,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Contributor $contributor, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ContributorType::class, $contributor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Contributeur modifié avec succès.');
            return $this->redirectToRoute('contributor_index');
        }

        return $this->render('contributor/edit.html.twig', [
            'contributor' => $contributor,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Contributor $contributor, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$contributor->getId(), $request->request->get('_token'))) {
            $em->remove($contributor);
            $em->flush();
            $this->addFlash('success', 'Contributeur supprimé.');
        }

        return $this->redirectToRoute('contributor_index');
    }

    /**
     * Associer un contributeur existant à une série
     */
    #[Route('/{id}/add-to-serie/{serieId}', name: 'add_to_serie', methods: ['POST'])]
    public function addToSerie(
        Contributor $contributor,
        int $serieId,
        SerieRepository $serieRepository,
        EntityManagerInterface $em
    ): Response {
        $serie = $serieRepository->find($serieId);
        if (!$serie) {
            $this->addFlash('error', 'Série non trouvée.');
            return $this->redirectToRoute('contributor_show', ['id' => $contributor->getId()]);
        }

        if (!$contributor->getSeries()->contains($serie)) {
            $contributor->addSerie($serie);
            $em->flush();
            $this->addFlash('success', sprintf('%s a été associé à la série "%s".', $contributor->getName(), $serie->getName()));
        }

        return $this->redirectToRoute('contributor_show', ['id' => $contributor->getId()]);
    }

    /**
     * Retirer l'association entre un contributeur et une série
     */
    #[Route('/{id}/remove-from-serie/{serieId}', name: 'remove_from_serie', methods: ['POST'])]
    public function removeFromSerie(
        Contributor $contributor,
        int $serieId,
        SerieRepository $serieRepository,
        EntityManagerInterface $em
    ): Response {
        $serie = $serieRepository->find($serieId);
        if ($serie && $contributor->getSeries()->contains($serie)) {
            $contributor->removeSerie($serie);
            $em->flush();
            $this->addFlash('success', sprintf('%s a été retiré de la série "%s".', $contributor->getName(), $serie->getName()));
        }

        return $this->redirectToRoute('contributor_show', ['id' => $contributor->getId()]);
    }

    /**
     * Autocomplétion pour rechercher un contributeur
     */
    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(Request $request, ContributorRepository $repo): JsonResponse
    {
        $q = $request->query->get('q', '');
        if (strlen($q) < 2) {
            return $this->json([]);
        }

        $contributors = $repo->createQueryBuilder('c')
            ->where('c.name LIKE :q')
            ->setParameter('q', '%'.$q.'%')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        return $this->json(array_map(fn(Contributor $c) => [
            'id'   => $c->getId(),
            'name' => $c->getName(),
            'role' => $c->getRole(),
        ], $contributors));
    }
}
