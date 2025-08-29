<?php

namespace App\EntityListener;

use App\Entity\Serie;
use App\Utils\FileManager;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;


#[AsEntityListener(event: Events::preRemove, method: 'preRemove', entity: Serie::class)]
class SerieListener
{
    public function __construct(private ParameterBagInterface $parameterBag, private FileManager $fileManager) {}

    public function preRemove(Serie $serie, PreRemoveEventArgs $event): void
    {
        $this->fileManager->delete($this->parameterBag->get('serie')['backdrop_dir'], $serie->getBackdrop());
        // S'occuper éventuelement des autres images
    }


}