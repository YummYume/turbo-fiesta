<?php

namespace App\DataFixtures;

use App\Entity\Content;
use App\Enum\ContentTypeEnum;
use App\Enum\ContentVisibilityEnum;
use App\Manager\MessageManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

final class ContentFixtures extends Fixture implements DependentFixtureInterface
{
    public const FIXTURE_RANGE = 50;

    public const REFERENCE_IDENTIFIER = 'content_';

    public function __construct(private readonly MessageManager $messageManager)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        $caddie = (new Content())
            ->addCategory($this->getReference(CategoryFixtures::REFERENCE_IDENTIFIER.array_search('Caddie', CategoryFixtures::FIXTURE_DATA, true)))
            ->setTitle('Nouveau Caddie très puissant en vente')
            ->setBlocks(json_decode('{"time":1680182022973,"blocks":[{"id":"4O4EkNTkqy","type":"paragraph","data":{"text":"Nouveau Caddy!"},"tunes":{"textVariant":""}},{"id":"O61k29N1is","type":"image","data":{"url":"https://thumbs.dreamstime.com/b/caddie-avec-le-burning-du-feu-73113614.jpg","caption":"Caddie nouvelle génération","withBorder":false,"withBackground":false,"stretched":false}}],"version":"2.26.5"}', true))
            ->setPublished(true)
        ;

        $manager->persist($caddie);
        $this->addReference(self::REFERENCE_IDENTIFIER.'caddie', $caddie);

        foreach (range(1, self::FIXTURE_RANGE) as $i) {
            $content = (new Content())
                ->setTitle(ucfirst($faker->unique()->words(4, true)))
                ->setBlocks(json_decode(str_replace('__TEXT__', $faker->sentence(10), '{"time":1680182022973,"blocks":[{"id":"4O4EkNTkqy","type":"paragraph","data":{"text":"__TEXT__"},"tunes":{"textVariant":""}}],"version":"2.26.5"}'), true))
                ->setPublished($faker->boolean())
                ->setType($faker->randomElement([ContentTypeEnum::Article, ContentTypeEnum::Formation, ContentTypeEnum::Other]))
                ->setVisibility($faker->randomElement([ContentVisibilityEnum::Public, ContentVisibilityEnum::Member, ContentVisibilityEnum::Employee]))
                ->addCategory($this->getReference(CategoryFixtures::REFERENCE_IDENTIFIER.$faker->randomDigit(1, \count(CategoryFixtures::FIXTURE_DATA))))
            ;

            $manager->persist($content);
            $this->addReference(self::REFERENCE_IDENTIFIER.$i, $content);

            if (($i % 10) === 0) {
                $manager->flush();
            }

            $this->messageManager->createMessagesForContent($content, false);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            CategoryFixtures::class,
            UserFixtures::class,
        ];
    }
}
