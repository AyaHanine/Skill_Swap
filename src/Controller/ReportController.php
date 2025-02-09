<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\Offer;
use App\Entity\Report;
use App\Enum\ReportStatus;
use App\Form\ReportType;
use App\Repository\ReportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;

#[Route('/report')]
final class ReportController extends AbstractController
{
    #[Route(name: 'app_report')]
    public function index(): Response
    {
        return $this->render('report/index.html.twig', [
            'controller_name' => 'ReportController',
        ]);
    }

    #[Route('/offer/{id}', name: 'report_offer', methods: ['GET', 'POST'])]
    public function reportOffer(Offer $offer, Request $request, EntityManagerInterface $entityManager, ReportRepository $reportRepository): Response
    {
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour signaler une offre.');
            return $this->redirectToRoute('security_login');
        }
        // Vérifier si l'utilisateur a déjà signalé cette offre
        $existingReport = $reportRepository->findOneBy([
            'maker' => $user,
            'Offer' => $offer
        ]);

        if ($existingReport) {
            return new Response('Vous avez déjà signalé cette offre.');
        }

        $report = new Report();
        $report->setMaker($user);
        $report->setOffer($offer);
        $report->setRepportedUser($offer->getUser());
        $report->setCreatedAt(new \DateTimeImmutable());
        $report->setStatus('en attente');

        $notification = new Notification();
        $notification->setMessage("Votre signalement de l'offre      " . $offer->getTitle() . "     a été bien envoyé !");
        $notification->setIsRead(false);
        $notification->setCreatedAt(new \DateTimeImmutable());
        $notification->setUser($report->getMaker());
        $entityManager->persist($notification);
        $entityManager->flush();

        $form = $this->createForm(ReportType::class, $report);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {


            $entityManager->persist($report);
            $entityManager->flush();
            $this->addFlash('success', 'Votre signalement a été envoyé.');
            return $this->redirectToRoute('offer_show', ['id' => $offer->getId()]);
        }

        return $this->render('report/report.html.twig', [
            'form' => $form->createView(),
            'type' => 'offre',
        ]);
    }

    #[Route('/user/{id}', name: 'report_user', methods: ['GET', 'POST'])]
    public function reportUser(User $user, Request $request, EntityManagerInterface $entityManager): Response
    {
        $currentUser = $this->getUser();

        if (!$currentUser) {
            $this->addFlash('error', 'Vous devez être connecté pour signaler un utilisateur.');
            return $this->redirectToRoute('security_login');
        }

        $report = new Report();
        $report->setMaker($currentUser);
        $report->setRepportedUser($user);

        $form = $this->createForm(ReportType::class, $report);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($report);
            $entityManager->flush();

            $this->addFlash('success', 'Votre signalement a été envoyé.');
            return $this->redirectToRoute('user_profile', ['id' => $user->getId()]);
        }

        return $this->render('report/report.html.twig', [
            'form' => $form->createView(),
            'type' => 'utilisateur',
        ]);
    }



}
