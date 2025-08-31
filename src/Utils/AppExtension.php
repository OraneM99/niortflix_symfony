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
            'Netflix'   => '/images/streaming/netflix.png',
            'Prime Video'     => '/images/streaming/prime.png',
            'Disney+'    => '/images/streaming/disney.webp',
            'Crunchyroll' => '/images/streaming/crunchyroll.png',
            'Canal'     => '/images/streaming/canal.jpg',
            'Hbo Max'       => '/images/streaming/hbo.png',
            'Paramount+' => '/images/streaming/paramount.png',
            'Youtube' => '/images/streaming/youtube.jpeg',
            'ADN' => '/images/streaming/adn.png',
            'AppleTV' => '/images/streaming/apple.png',
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
