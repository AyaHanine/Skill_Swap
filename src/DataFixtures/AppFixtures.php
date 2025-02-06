<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Offer;
use App\Entity\Skill;
use App\Entity\Request;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Faker\Factory;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();


        // Création de compétences (Skills)
        $skills = [];
        $skillNames = ['Développement Web', 'Graphisme', 'Marketing', 'SEO', 'Photographie', 'Montage Vidéo'];

        foreach ($skillNames as $name) {
            $skill = new Skill();
            $skill->setName($name);
            $manager->persist($skill);
            $skills[] = $skill;
        }

        // Créer un utilisateur "admin"
        $adminUser = new User();
        $adminUser->setFirstName('admin')
            ->setEmail('admin@gmail.com')
            ->setRoles(['ROLE_ADMIN']);

        // Encoder le mot de passe
        $encodedPassword = $this->passwordHasher->hashPassword($adminUser, 'adminpassword');
        $adminUser->setPassword($encodedPassword);

        // Persist et flush
        $manager->persist($adminUser);





        $manager->flush();
    }
}
