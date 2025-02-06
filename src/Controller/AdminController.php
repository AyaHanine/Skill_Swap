<?php

namespace App\Controller;

use App\Entity\Offer;
use App\Entity\Report;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AdminController extends AbstractController
{

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }


    #[Route('/admin', name: 'app_admin')]
    public function index(): Response
    {
        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }


    // Route pour afficher la gestion des signalements
    #[Route('/admin/reports', name: 'admin_manage_reports')]
    public function manageReports(EntityManagerInterface $entityManager): Response
    {
        // Récupérer tous les utilisateurs
        $users = $entityManager->getRepository(User::class)->findAll();

        // Récupérer les offres
        $offers = $entityManager->getRepository(Offer::class)->findAll();

        $reportedUserCounts = 0;
        $reportedOfferCounts = 0;

        // Calculer les signalements pour chaque utilisateur
        foreach ($users as $user) {
            $reportedUserCount = $entityManager->getRepository(Report::class)->count(['repportedUser' => $user]);
            $reportedUserCounts = $reportedUserCount;
        }


        // Calculer les signalements pour chaque offre
        foreach ($offers as $offer) {
            $reportedOfferCount = $entityManager->getRepository(Report::class)->count(['Offer' => $offer]);
            $reportedOfferCounts = $reportedOfferCount;
        }

        return $this->render('admin/manage_reports.html.twig', [
            'users' => $users,
            'offers' => $offers,
            'reportedOffer' => $reportedOfferCounts,
            'reportedUser' => $reportedUserCounts,
        ]);
    }

    #[Route('/admin/banned-users', name: 'admin_banned_users', methods: ['GET'])]
    public function bannedUsers(UserRepository $userRepository): Response
    {
        $bannedUsers = $userRepository->findBannedUsers();

        return $this->render('admin/banned_users.html.twig', [
            'bannedUsers' => $bannedUsers
        ]);
    }


    // Bannir l'utilisateur après 3 signalements
    #[Route('/admin/ban/user/{id}', name: 'admin_ban_user')]
    public function banUser(User $user, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur a 3 signalements
        $reportCount = $entityManager->getRepository(Report::class)->count(['repportedUser' => $user]);

        // Si l'utilisateur a 3 signalements, le bannir
        if ($reportCount >= 3) {
            $user->setRoles(['ROLE_BANNED']);
            $entityManager->flush();
            $this->addFlash('success', 'L\'utilisateur a été banni après 3 signalements.');
        }

        return $this->redirectToRoute('admin_manage_reports');
    }

    // Bannir l'utilisateur associé à une offre après 3 signalements
    #[Route('/admin/ban/offer/{id}', name: 'admin_ban_offer')]
    public function banOffer(Offer $offer, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'offre a 3 signalements
        $reportCount = $entityManager->getRepository(Report::class)->count(['Offer' => $offer]);

        // Si l'offre a 3 signalements, bannir l'utilisateur de l'offre
        if ($reportCount >= 3) {
            $user = $offer->getUser(); // Récupérer l'utilisateur qui a posté l'offre
            $user->setRoles(['ROLE_BANNED']); // Bannir l'utilisateur
            $entityManager->flush();
            $this->addFlash('success', 'L\'utilisateur a été banni à cause de 3 signalements sur ses offres.');
        }

        return $this->redirectToRoute('admin_manage_reports');
    }

}
