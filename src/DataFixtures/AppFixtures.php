<?php

namespace App\DataFixtures;

use App\Entity\Notification;
use App\Entity\Report;
use App\Entity\Review;
use App\Entity\User;
use App\Entity\Offer;
use App\Entity\Skill;
use App\Entity\Request;
use App\Enum\OfferStatus;
use App\Enum\ReportStatus;
use App\Enum\RequestStatus;
use App\Enum\SkillStatus;
use App\Form\ReportType;
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

        $faker = Factory::create('fr_FR');

        // ---------------- USERS ----------------
        $users = [];
        $roles = [
            ['ROLE_USER'],
            ['ROLE_USER'],
            ['ROLE_USER'],
            ['ROLE_USER'],
            ['ROLE_USER'],
            ['ROLE_BANNED'],
            ['ROLE_BANNED'],
            ['ROLE_BANNED'],
            ['ROLE_ADMIN'],
            ['ROLE_ADMIN', 'ROLE_USER']
        ];

        foreach ($roles as $index => $role) {
            $user = new User();
            $user->setEmail($faker->email);
            $user->setRoles($role);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));
            $user->setFirstName($faker->firstName);
            $user->setLastName($faker->lastName);
            $user->setBio($faker->sentence(10));
            $user->setCreatedAt(new \DateTimeImmutable());
            $user->setUpdatedAt(new \DateTimeImmutable());
            $manager->persist($user);
            $users[] = $user;
        }

        // ---------------- SKILLS ----------------
        $skills = [];
        for ($i = 0; $i < 10; $i++) {
            $skill = new Skill();
            $skill->setName($faker->word);
            $skill->setStatus($faker->randomElement([SkillStatus::validé, SkillStatus::enAttente, SkillStatus::refusé]));
            $skill->setProposedBy($faker->randomElement($users));
            $manager->persist($skill);
            $skills[] = $skill;
        }

        // ---------------- OFFERS ----------------
        $offers = [];
        for ($i = 0; $i < 14; $i++) {
            $offer = new Offer();
            $offer->setTitle($faker->sentence(3));
            $offer->setDescription($faker->paragraph(3));
            $offer->setCreatedAt(new \DateTimeImmutable());
            $offer->setStatus($faker->randomElement([
                OfferStatus::Disponible,
                OfferStatus::Reserve,
                OfferStatus::Banni,
                OfferStatus::Termine,
                OfferStatus::Annule
            ]));
            $offer->setUser($faker->randomElement($users));
            $offer->setNegotiable($faker->boolean);
            $offer->setWantedSkill($faker->randomElement($skills));
            $offer->setOfferedSkill($faker->randomElement($skills));
            $manager->persist($offer);
            $offers[] = $offer;
        }

        // ---------------- REVIEWS ----------------
        foreach ($offers as $offer) {
            for ($i = 0; $i < random_int(0, 4); $i++) {
                $review = new Review();
                $review->setRating(random_int(1, 5));
                $review->setComment($faker->sentence(10));
                $review->setCreatedAt(new \DateTimeImmutable());
                $review->setAuthor($faker->randomElement($users));
                $review->setOffer($offer);
                $manager->persist($review);
            }
        }

        // ---------------- REQUESTS ----------------
        foreach ($offers as $offer) {
            for ($i = 0; $i < random_int(0, 5); $i++) {
                $request = new Request();
                $request->setStatus($faker->randomElement([
                    RequestStatus::Acceptee,
                    RequestStatus::Refusee,
                    RequestStatus::EnAttente,
                    RequestStatus::EnAttente,
                    RequestStatus::Refusee
                ]));
                $request->setMessage($faker->sentence(8));
                $request->setCreatedAt(new \DateTimeImmutable());
                $request->setUser($faker->randomElement($users));
                $request->setOffer($offer);
                $manager->persist($request);
            }
        }

        // ---------------- REPORTS ----------------
        $reportOffers = $faker->randomElements($offers, 3);
        foreach ($reportOffers as $offer) {
            for ($i = 0; $i < 3; $i++) {
                $report = new Report();
                $report->setMaker($faker->randomElement($users));
                $report->setOffer(Offer: $offer);
                $report->setRepportedUser($faker->randomElement($users));
                $report->setReason($faker->sentence(5));
                $report->setStatus('en cours');
                $report->setCreatedAt(new \DateTimeImmutable());
                $manager->persist($report);
            }
        }

        // ---------------- NOTIFICATIONS ----------------
        foreach ($users as $user) {
            for ($i = 0; $i < random_int(0, 2); $i++) {
                $notification = new Notification();
                $notification->setMessage($faker->sentence(6));
                $notification->setIsRead($faker->boolean);
                $notification->setCreatedAt(new \DateTimeImmutable());
                $notification->setUser($user);
                $manager->persist($notification);
            }
        }

        $manager->flush();
    }
}
