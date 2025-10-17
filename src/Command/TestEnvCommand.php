<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(name: 'app:test-env')]
class TestEnvCommand extends Command
{
    public function __construct(
        private readonly ParameterBagInterface $params
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Test avec getenv
        $envKey = getenv('TMDB_API_KEY');
        $io->section('Test avec getenv()');
        $io->text('Clé API : ' . ($envKey ?: 'NON TROUVÉE'));
        $io->text('Longueur : ' . strlen($envKey ?: ''));

        // Test avec $_ENV
        $io->section('Test avec $_ENV');
        $io->text('Clé API : ' . ($_ENV['TMDB_API_KEY'] ?? 'NON TROUVÉE'));

        // Test avec $_SERVER
        $io->section('Test avec $_SERVER');
        $io->text('Clé API : ' . ($_SERVER['TMDB_API_KEY'] ?? 'NON TROUVÉE'));

        return Command::SUCCESS;
    }
}