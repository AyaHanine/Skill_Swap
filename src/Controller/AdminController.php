<?php

namespace App\Controller;

use App\Entity\Offer;
use App\Entity\Report;
use App\Enum\OfferStatus;
use App\Repository\OfferRepository;
use App\Repository\ReportRepository;
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



    #[Route('/admin/reports', name: 'admin_manage_reports')]
    public function manageReports(EntityManagerInterface $entityManager, ReportRepository $reportRepository): Response
    {
        $reports = $reportRepository->findBy(['status' => 'En attente']);

        return $this->render('admin/reports/index.html.twig', [
            'reports' => $reports,
        ]);
    }

    #[Route('/approve/{id}', name: 'admin_approve_report')]
    public function approve(
        Report $report,
        EntityManagerInterface $entityManager,
        ReportRepository $reportRepository,
        OfferRepository $offerRepository
    ): Response {
        $report->approve();
        $entityManager->flush();

        $offer = $report->getOffer();
        $approvedReports = $reportRepository->count(['Offer' => $offer, 'isApproved' => true]);

        if ($approvedReports >= 3) {
            $offer->setStatus(OfferStatus::Banni);
            $offerCreator = $offer->getUser();
            $offerCreator->setRoles(['ROLE_BANNED']);
            $entityManager->flush();

            $this->addFlash('warning', "L'offre et son créateur ont été bannis.");
        } else {
            $this->addFlash('success', "Signalement approuvé.");
        }

        return $this->redirectToRoute('admin_manage_reports');
    }

    #[Route('/reject/{id}', name: 'admin_reject_report')]
    public function reject(Report $report, EntityManagerInterface $entityManager): Response
    {
        $report->setStatus('Rejeté');
        $entityManager->flush();

        $this->addFlash('danger', "Signalement rejeté.");
        return $this->redirectToRoute('admin_manage_reports');
    }



}
