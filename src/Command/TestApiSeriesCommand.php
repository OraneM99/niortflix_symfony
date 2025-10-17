<?php

namespace App\Command;

use App\Service\TmdbService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:test-api-series')]
class TestApiSeriesCommand extends Command
{
    public function __construct(
        private readonly TmdbService $tmdbService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Test direct TmdbService');

        // Test direct
        $io->section('Appel direct getPopularSeries()');
        $data = $this->tmdbService->getPopularSeries(1);

        $io->text('Clés du tableau retourné: ' . json_encode(array_keys($data)));
        $io->text('Nombre de résultats: ' . count($data['results'] ?? []));

        if (!empty($data['results'])) {
            $io->success('✅ API fonctionne !');
            $io->table(
                ['Nom', 'Note'],
                array_map(fn($s) => [
                    $s['name'] ?? 'N/A',
                    $s['vote_average'] ?? 'N/A'
                ], array_slice($data['results'], 0, 5))
            );
        } else {
            $io->error('❌ Aucun résultat');
            $io->text('Contenu complet du retour:');
            $io->text(json_encode($data, JSON_PRETTY_PRINT));
        }

        return Command::SUCCESS;
    }
}