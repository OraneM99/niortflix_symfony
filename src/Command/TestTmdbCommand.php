<?php

namespace App\Command;

use App\Service\TmdbService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-tmdb',
    description: 'Test de connexion à l\'API TMDb'
)]
class TestTmdbCommand extends Command
{
    public function __construct(
        private readonly TmdbService $tmdbService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Test de connexion à TMDb');

        // Test 1 : Récupérer les séries populaires
        $io->section('Test 1 : Séries populaires');
        $popular = $this->tmdbService->getPopularSeries(1);

        if (empty($popular['results'])) {
            $io->error('Impossible de récupérer les séries populaires.');
            $io->note('Vérifie ta clé API dans le fichier .env');
            return Command::FAILURE;
        }

        $io->success('Connexion réussie !');
        $io->table(
            ['ID', 'Nom', 'Note'],
            array_slice(array_map(fn($s) => [
                $s['id'],
                $s['name'],
                $s['vote_average'] ?? 'N/A'
            ], $popular['results']), 0, 5)
        );

        // Test 2 : Récupérer une série spécifique (Game of Thrones = 1399)
        $io->section('Test 2 : Détails d\'une série (Game of Thrones)');
        $serie = $this->tmdbService->getSerie(1399);

        if (!$serie) {
            $io->error('Impossible de récupérer les détails de la série.');
            return Command::FAILURE;
        }

        $io->listing([
            'Nom : ' . ($serie['name'] ?? 'N/A'),
            'Saisons : ' . count($serie['seasons'] ?? []),
            'Note : ' . ($serie['vote_average'] ?? 'N/A'),
            'Statut : ' . ($serie['status'] ?? 'N/A'),
        ]);

        // Test 3 : Recherche
        $io->section('Test 3 : Recherche "Breaking Bad"');
        $search = $this->tmdbService->searchSerie('Breaking Bad');

        if (empty($search['results'])) {
            $io->warning('Aucun résultat trouvé.');
        } else {
            $io->success(sprintf('%d résultat(s) trouvé(s)', count($search['results'])));
            $io->text('Premier résultat : ' . ($search['results'][0]['name'] ?? 'N/A'));
        }

        $io->success('Tous les tests sont passés ! 🎉');
        $io->note('Tu peux maintenant utiliser l\'import dans ton application.');

        return Command::SUCCESS;
    }
}