<?php

namespace App\DataFixtures;

use App\Entity\Serie;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class SerieFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        for ($i = 0; $i < 1000; $i++) {
            $serie = new Serie();
            $serie->setName($faker->realText(30))
                ->setOverview($faker->realText(100))
                ->setStatus($faker->randomElement(['returning', 'ended', 'canceled']))
                ->setGenres($faker->randomElement(['Horreur', 'Romance', 'Policier', 'Action', 'Science-Fiction', 'Western', 'Enigme', 'Espionnage', 'Thriller', 'Comédie', 'Fantaisie']))
                ->setVote($faker->randomFloat(2, 0, 10))
                ->setPopularity($faker->randomFloat(2, 200, 1000))
                ->setFirstAirDate($faker->dateTimeBetween('-10 year', '-1 month'))
                ->setDateCreated(new \DateTime());

            if ($serie->getStatus() !== 'returning') {
                $serie->setLastAirDate($faker->dateTimeBetween($serie->getFirstAirDate(), '-3 day'));
            }

            $manager->persist($serie);
        }

        $manager->flush();
    }
}
