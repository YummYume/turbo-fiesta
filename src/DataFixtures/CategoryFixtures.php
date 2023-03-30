<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

final class CategoryFixtures extends Fixture implements DependentFixtureInterface
{
    public const FIXTURE_DATA = [
        'Hospital',
        'House',
        'Well being',
        'Shopping',
        '冰淇淋',
        'Culture',
        'Travel',
        'WW3',
        'Svelte',
        'Caddie',
    ];

    public const REFERENCE_IDENTIFIER = 'category_';

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        foreach (self::FIXTURE_DATA as $i => $name) {
            $category = (new Category())
                ->setName($name)
            ;
            $profiles = $faker->randomElements(range(1, UserFixtures::FIXTURE_RANGE));

            foreach ($profiles as $profile) {
                $category->addProfile($this->getReference(UserFixtures::REFERENCE_IDENTIFIER.$profile)->getProfile());
            }

            $manager->persist($category);
            $this->addReference(self::REFERENCE_IDENTIFIER.$i, $category);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}
