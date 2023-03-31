<?php

namespace App\DataFixtures;

use App\Entity\Profile;
use App\Entity\ProfilePicture;
use App\Entity\User;
use App\Enum\UserRoleEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class UserFixtures extends Fixture implements DependentFixtureInterface
{
    public const FIXTURE_RANGE = 50;

    public const REFERENCE_IDENTIFIER = 'user_';

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        $adminPicture = (new ProfilePicture())
            ->setFile(new UploadedFile(
                __DIR__.'/tmp/profile/admin.jpg',
                'admin.jpg',
                test: true
            ))
        ;
        $adminProfile = (new Profile())
            ->setUsername('Root')
            ->setDescription('Super cool')
            ->setPicture($adminPicture)
            ->addCategory($this->getReference(CategoryFixtures::REFERENCE_IDENTIFIER.(\count(CategoryFixtures::FIXTURE_DATA) - 1)))
        ;
        $admin = (new User())
            ->setEmail('root@root.com')
            ->setPlainPassword('root')
            ->setVerified(true)
            ->setRoles([UserRoleEnum::SuperAdmin->value, UserRoleEnum::AllowedToSwitch->value])
            ->setProfile($adminProfile)
        ;

        $manager->persist($admin);
        $this->addReference(self::REFERENCE_IDENTIFIER.'root', $admin);

        foreach (range(1, self::FIXTURE_RANGE) as $i) {
            $profile = (new Profile())
                ->setUsername(str_replace('.', ' ', $faker->unique()->userName()))
                ->setDescription($faker->paragraph(5))
            ;
            $user = (new User())
                ->setEmail("email-$i@jaji.com")
                ->setPlainPassword('xxx')
                ->setVerified(true)
                ->setProfile($profile)
            ;
            $categories = $faker->randomElements(range(0, \count(CategoryFixtures::FIXTURE_DATA) - 1), 3);

            foreach ($categories as $category) {
                $profile->addCategory($this->getReference(CategoryFixtures::REFERENCE_IDENTIFIER.$category));
            }

            $manager->persist($user);
            $this->addReference(self::REFERENCE_IDENTIFIER.$i, $user);

            if (($i % 10) === 0) {
                $manager->flush();
                $manager->clear();
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            PreparePicturesFixtures::class,
            CategoryFixtures::class,
        ];
    }
}
