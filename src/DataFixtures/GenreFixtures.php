<?php

namespace App\DataFixtures;

use App\Entity\Genre;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class GenreFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $genres = ['Action', 'Drame', 'Comédie', 'Science-Fiction', 'Thriller'];

        foreach ($genres as $name) {
            $genre = new Genre();
            $genre->setName($name);
            $manager->persist($genre);
        }

        $manager->flush();
    }
}
