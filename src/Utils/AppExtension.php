<?php

namespace App\Utils;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('streamingLogo', [$this, 'getStreamingLogo'], ['is_safe' => ['html']]),
            new TwigFilter('flag', [$this, 'countryFlag'], ['is_safe' => ['html']]),
        ];
    }

    public function getStreamingLogo(string $platform): string
    {
        $logos = [
            'Netflix'   => '/images/providers/netflix.png',
            'Prime Video'     => '/images/providers/prime.png',
            'Disney+'    => '/images/providers/disney.webp',
            'Crunchyroll' => '/images/providers/crunchyroll.png',
            'Canal'     => '/images/providers/canal.jpg',
            'Hbo Max'       => '/images/providers/hbo.png',
            'Paramount+' => '/images/providers/paramount.png',
            'Youtube' => '/images/providers/youtube.jpeg',
            'ADN' => '/images/providers/adn.png',
            'AppleTV' => '/images/providers/apple.png',
        ];

        return isset($logos[strtolower($platform)])
            ? sprintf('<img src="%s" alt="%s" style="height:20px;">', $logos[strtolower($platform)], $platform)
            : ucfirst($platform);
    }

    public function countryFlag(?string $countryCode): string
    {
        if (!$countryCode) {
            return '';
        }

        $code = strtoupper($countryCode);
        $flag = '';
        foreach (str_split($code) as $char) {
            $flag .= mb_chr(ord($char) + 127397, 'UTF-8');
        }

        return $flag;
    }
}
