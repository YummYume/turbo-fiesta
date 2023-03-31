<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class CategoryFixtures extends Fixture
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
        foreach (self::FIXTURE_DATA as $i => $name) {
            $category = (new Category())
                ->setName($name)
            ;

            $manager->persist($category);
            $this->addReference(self::REFERENCE_IDENTIFIER.$i, $category);
        }

        $manager->flush();
    }
}
