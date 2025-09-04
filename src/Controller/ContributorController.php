<?php

namespace App\Controller;

use App\Entity\Contributor;
use App\Entity\Serie;
use App\Form\ContributorType;
use App\Repository\ContributorRepository;
use App\Repository\SerieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/contributor')]
final class ContributorController extends AbstractController
{
    #[Route(name: 'app_contributor_index', methods: ['GET'])]
    public function index(ContributorRepository $contributorRepository): Response
    {
        return $this->render('contributor/index.html.twig', [
            'contributors' => $contributorRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_contributor_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $contributor = new Contributor();
        $form = $this->createForm(ContributorType::class, $contributor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($contributor);
            $entityManager->flush();

            return $this->redirectToRoute('app_contributor_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('contributor/detail.html.twig', [
            'contributor' => $contributor,
            'form' => $form,
        ]);
    }

    /**
     * Ajouter un contributeur à une série depuis la page de détail
     */
    #[Route('/serie/{serieId}/add', name: 'contributor_add', methods: ['POST'])]
    public function addToSerie(
        int $serieId,
        Request $request,
        SerieRepository $serieRepository,
        EntityManagerInterface $entityManager,
        CsrfTokenManagerInterface $csrfTokenManager,
        ValidatorInterface $validator
    ): Response {
        // Vérification du token CSRF
        $token = $request->request->get('token');
        if (!$csrfTokenManager->isTokenValid(new \Symfony\Component\Security\Csrf\CsrfToken('contributor_form', $token))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('serie_detail', ['id' => $serieId]);
        }

        // Récupération de la série
        $serie = $serieRepository->find($serieId);
        if (!$serie) {
            $this->addFlash('error', 'Série non trouvée.');
            return $this->redirectToRoute('serie_liste');
        }

        // Récupération et validation des données
        $name = trim($request->request->get('name', ''));
        $role = $request->request->get('role', '');
        $customRole = trim($request->request->get('custom_role', ''));
        $notes = trim($request->request->get('notes', ''));

        // Validation basique
        if (empty($name)) {
            $this->addFlash('error_name', 'Le nom du contributeur est requis.');
            return $this->redirectToRoute('serie_detail', ['id' => $serieId]);
        }

        if (empty($role)) {
            $this->addFlash('error_role', 'Le rôle du contributeur est requis.');
            return $this->redirectToRoute('serie_detail', ['id' => $serieId]);
        }

        // Si le rôle est "Autre", utiliser le rôle personnalisé
        if ($role === 'Autre') {
            if (empty($customRole)) {
                $this->addFlash('error_role', 'Veuillez préciser le rôle personnalisé.');
                return $this->redirectToRoute('serie_detail', ['id' => $serieId]);
            }
            $role = $customRole;
        }

        // Vérification de doublon (même nom et rôle pour cette série)
        $existingContributor = $entityManager->getRepository(Contributor::class)
            ->findOneBy([
                'serie' => $serie,
                'name' => $name,
                'role' => $role
            ]);

        if ($existingContributor) {
            $this->addFlash('error', 'Ce contributeur avec ce rôle existe déjà pour cette série.');
            return $this->redirectToRoute('serie_detail', ['id' => $serieId]);
        }

        try {
            // Création du contributeur
            $contributor = new Contributor();
            $contributor->setName($name);
            $contributor->setRole($role);
            $contributor->setSerie($serie);

            if (!empty($notes)) {
                $contributor->setNotes($notes);
            }

            // Validation avec le validator Symfony
            $errors = $validator->validate($contributor);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->redirectToRoute('serie_detail', ['id' => $serieId]);
            }

            $entityManager->persist($contributor);
            $entityManager->flush();

            $this->addFlash('contributor_success',
                sprintf('Le contributeur "%s" a été ajouté avec succès en tant que "%s".', $name, $role)
            );

        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de l\'ajout du contributeur.');

            // Log de l'erreur pour le debug
            if ($this->getParameter('kernel.environment') === 'dev') {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->redirectToRoute('serie_detail', ['id' => $serieId]);
    }

    /**
     * Mettre à jour un contributeur via AJAX
     */
    #[Route('/{id}/update', name: 'contributor_update', methods: ['POST'])]
    public function update(
        Contributor $contributor,
        Request $request,
        EntityManagerInterface $entityManager,
        CsrfTokenManagerInterface $csrfTokenManager,
        ValidatorInterface $validator
    ): Response {
        // Vérification du token CSRF
        $token = $request->request->get('token');
        if (!$csrfTokenManager->isTokenValid(new \Symfony\Component\Security\Csrf\CsrfToken('contributor_form', $token))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('serie_detail', ['id' => $contributor->getSerie()->getId()]);
        }

        // Récupération et validation des données
        $name = trim($request->request->get('name', ''));
        $role = $request->request->get('role', '');
        $customRole = trim($request->request->get('custom_role', ''));
        $notes = trim($request->request->get('notes', ''));

        // Validation basique
        if (empty($name)) {
            $this->addFlash('error_name', 'Le nom du contributeur est requis.');
            return $this->redirectToRoute('serie_detail', ['id' => $contributor->getSerie()->getId()]);
        }

        if (empty($role)) {
            $this->addFlash('error_role', 'Le rôle du contributeur est requis.');
            return $this->redirectToRoute('serie_detail', ['id' => $contributor->getSerie()->getId()]);
        }

        // Si le rôle est "Autre", utiliser le rôle personnalisé
        if ($role === 'Autre') {
            if (empty($customRole)) {
                $this->addFlash('error_role', 'Veuillez préciser le rôle personnalisé.');
                return $this->redirectToRoute('serie_detail', ['id' => $contributor->getSerie()->getId()]);
            }
            $role = $customRole;
        }

        try {
            // Mise à jour du contributeur
            $contributor->setName($name);
            $contributor->setRole($role);

            if (!empty($notes)) {
                $contributor->setNotes($notes);
            } else {
                $contributor->setNotes(null);
            }

            // Validation avec le validator Symfony
            $errors = $validator->validate($contributor);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->redirectToRoute('serie_detail', ['id' => $contributor->getSerie()->getId()]);
            }

            $entityManager->flush();

            $this->addFlash('contributor_success',
                sprintf('Le contributeur "%s" a été modifié avec succès.', $name)
            );

        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la modification du contributeur.');

            // Log de l'erreur pour le debug
            if ($this->getParameter('kernel.environment') === 'dev') {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->redirectToRoute('serie_detail', ['id' => $contributor->getSerie()->getId()]);
    }

    /**
     * Supprimer un contributeur
     */
    #[Route('/{id}/delete', name: 'contributor_delete_from_serie', methods: ['POST'])]
    public function deleteFromSerie(
        Contributor $contributor,
        Request $request,
        EntityManagerInterface $entityManager,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response {
        $serieId = $contributor->getSerie()->getId();

        // Vérification du token CSRF
        $token = $request->request->get('token');
        if (!$csrfTokenManager->isTokenValid(new \Symfony\Component\Security\Csrf\CsrfToken('delete_contributor', $token))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('serie_detail', ['id' => $serieId]);
        }

        try {
            $contributorName = $contributor->getName();
            $entityManager->remove($contributor);
            $entityManager->flush();

            $this->addFlash('contributor_success',
                sprintf('Le contributeur "%s" a été supprimé avec succès.', $contributorName)
            );

        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la suppression du contributeur.');

            // Log de l'erreur pour le debug
            if ($this->getParameter('kernel.environment') === 'dev') {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->redirectToRoute('serie_detail', ['id' => $serieId]);
    }

    /**
     * Récupérer les données d'un contributeur via AJAX
     */
    #[Route('/{id}/data', name: 'contributor_get_data', methods: ['GET'])]
    public function getContributorData(Contributor $contributor): JsonResponse
    {
        return $this->json([
            'id' => $contributor->getId(),
            'name' => $contributor->getName(),
            'role' => $contributor->getRole(),
            'notes' => $contributor->getNotes(),
            'serie_id' => $contributor->getSerie()->getId()
        ]);
    }

    /**
     * Recherche de contributeurs existants (pour autocomplétion)
     */
    #[Route('/search', name: 'contributor_search', methods: ['GET'])]
    public function search(Request $request, ContributorRepository $contributorRepository): JsonResponse
    {
        $query = $request->query->get('q', '');

        if (strlen($query) < 2) {
            return $this->json([]);
        }

        $contributors = $contributorRepository->searchByName($query, 10);

        $results = [];
        foreach ($contributors as $contributor) {
            $results[] = [
                'id' => $contributor->getId(),
                'name' => $contributor->getName(),
                'role' => $contributor->getRole(),
                'serie' => $contributor->getSerie()->getName()
            ];
        }

        return $this->json($results);
    }

    // Méthodes existantes...

    #[Route('/{id}', name: 'app_contributor_show', methods: ['GET'])]
    public function show(Contributor $contributor): Response
    {
        return $this->render('contributor/show.html.twig', [
            'contributor' => $contributor,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_contributor_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Contributor $contributor, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ContributorType::class, $contributor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_contributor_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('contributor/edit.html.twig', [
            'contributor' => $contributor,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_contributor_delete', methods: ['POST'])]
    public function delete(Request $request, Contributor $contributor, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$contributor->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($contributor);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_contributor_index', [], Response::HTTP_SEE_OTHER);
    }
}