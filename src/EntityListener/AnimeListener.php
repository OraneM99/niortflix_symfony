<?php

namespace App\EntityListener;

use App\Entity\Anime;
use App\Utils\FileManager;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsEntityListener(event: Events::preRemove, method: 'preRemove', entity: Anime::class)]
class AnimeListener
{
    public function __construct(private ParameterBagInterface $parameterBag, private FileManager $fileManager) {}

    public function preRemove(Anime $anime, PreRemoveEventArgs $event): void
    {
        $this->fileManager->delete($this->parameterBag->get('anime')['backdrop_dir'], $anime->getBackdrop());
    }
}