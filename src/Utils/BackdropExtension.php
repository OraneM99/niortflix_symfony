<?php

namespace App\Utils;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class BackdropExtension extends AbstractExtension
{
    private string $backdropsPath;

    public function __construct(string $projectDir)
    {
        $this->backdropsPath = $projectDir . '/public/uploads/backdrops/';
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('random_background', [$this, 'getRandomBackground']),
        ];
    }

    public function getRandomBackground(): string
    {
        if (!is_dir($this->backdropsPath)) {
            return '';
        }

        $images = glob($this->backdropsPath . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);

        if (empty($images)) {
            return '';
        }

        return '/uploads/backdrops/' . basename($images[array_rand($images)]);
    }
}
