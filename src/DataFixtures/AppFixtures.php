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

        // Création des utilisateurs
        $users = [];
        for ($i = 0; $i < 10; $i++) {
            $user = new User();
            $user->setEmail($faker->email);
            $user->setFirstName($faker->firstName);
            $user->setLastName($faker->lastName);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
            $user->setRoles(['ROLE_USER']);
            $user->setIsVerified(true); // Simulation d'un email vérifié
            $manager->persist($user);
            $users[] = $user;
        }

        // Création de compétences (Skills)
        $skills = [];
        $skillNames = ['Développement Web', 'Graphisme', 'Marketing', 'SEO', 'Photographie', 'Montage Vidéo'];

        foreach ($skillNames as $name) {
            $skill = new Skill();
            $skill->setName($name);
            $skill->setCategory($faker->word);
            $manager->persist($skill);
            $skills[] = $skill;
        }

        // Création des offres
        for ($i = 0; $i < 15; $i++) {
            $offer = new Offer();
            $offer->setTitle($faker->sentence(4));
            $offer->setDescription($faker->paragraph);
            $offer->setUser($faker->randomElement($users));
            $offer->setCreatedAt(new \DateTimeImmutable());
            $offer->setStatus('active');
            $manager->persist($offer);
        }

        // Création des demandes d’échange (Requests)
        for ($i = 0; $i < 10; $i++) {
            $request = new Request();
            $request->setMessage($faker->sentence);
            $request->setUser($faker->randomElement($users));
            $request->setOffer($faker->randomElement($manager->getRepository(Offer::class)->findAll()));
            $request->setStatus('pending');
            $request->setCreatedAt(new \DateTimeImmutable());
            $manager->persist($request);
        }

        $manager->flush();
    }
}
