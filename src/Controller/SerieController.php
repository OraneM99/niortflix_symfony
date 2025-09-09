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
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

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
    public function detail(
        Serie $serie,
        ContributorRepository $contributorRepository,
        Request $request,
        Environment $twig
    ): Response {
        $contributors = $contributorRepository->findBySerie($serie);

        // Mode "modal" : on renvoie le contenu de la page (bloc body) pour injection
        if ($request->isXmlHttpRequest() || $request->query->getBoolean('partial')) {
            $tpl = $twig->load('serie/detail.html.twig');
            $context = [
                'serie' => $serie,
                'contributors' => $contributors,
            ];

            // Récupère le bloc body du template de la page
            $body = $tpl->renderBlock('body', $context);

            // Si tu as un block stylesheets spécifique à cette page, on peut aussi l’injecter
            $styles = $tpl->hasBlock('stylesheets', $context)
                ? $tpl->renderBlock('stylesheets', $context)
                : '';

            // Wrap dans une structure de modal Bootstrap (header + body)
            $html = sprintf(
                '<div class="modal-header border-0">
                <h5 class="modal-title text-white">%s</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
             </div>
             <div class="modal-body">%s%s</div>',
                htmlspecialchars($serie->getName(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                // styles spécifiques (si présents)
                $styles,
                // contenu principal
                $body
            );

            return new Response($html);
        }

        // Mode "page complète"
        return $this->render('serie/detail.html.twig', [
            'serie' => $serie,
            'contributors' => $contributors,
        ]);
    }

    #[Route('/favorite/{id}', name: 'favorite', requirements: ['id' => '\d+'], methods: ['GET','POST'])]
    public function favorite(Serie $serie, EntityManagerInterface $em, Request $request): Response
    {
        $user = $this->getUser();
        $isAjax = $request->isXmlHttpRequest() || str_contains((string)$request->headers->get('Accept'), 'application/json');

        if (!$user) {
            return $this->json([
                'ok' => false,
                'message' => 'Vous devez être connecté pour ajouter une série aux favoris.',
                'redirect' => $this->generateUrl('app_login'),
            ], Response::HTTP_UNAUTHORIZED);
        }

        // CSRF seulement pour POST
        if ($request->isMethod('POST')) {
            $token = (string) $request->request->get('_token');
            if (!$this->isCsrfTokenValid('favorite'.$serie->getId(), $token)) {
                return $this->json(['ok' => false, 'message' => 'Jeton CSRF invalide.'], Response::HTTP_FORBIDDEN);
            }
        }

        // Toggle
        $favorited = !$user->hasFavoriteSerie($serie);
        if ($favorited) {
            $user->addFavoriteSerie($serie);
            $message = 'La série a été ajoutée à vos favoris.';
            $flash   = 'success';
        } else {
            $user->removeFavoriteSerie($serie);
            $message = 'La série a été retirée de vos favoris.';
            $flash   = 'info';
        }

        $em->flush();

        if ($isAjax) {
            return $this->json(['ok' => true, 'favorited' => $favorited, 'message' => $message]);
        }

        $this->addFlash($flash, $message);
        $referer = $request->headers->get('referer') ?: $this->generateUrl('serie_liste');
        return $this->redirect($referer);
    }

    #[Route('/ignore/{id}', name: 'ignore', requirements: ['id' => '\d+'])]
    public function ignore(Serie $serie, Request $request, SessionInterface $session): Response
    {
        $ignored = $session->get('ignored_series', []);
        $ignored[$serie->getId()] = true;
        $session->set('ignored_series', $ignored);

        $this->addFlash('info', sprintf('« %s » a été masquée.', $serie->getName()));

        // Retour sur la liste en conservant d’éventuels paramètres de tri/recherche
        return $this->redirectToRoute('serie_liste', $request->query->all());
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
