<?php

namespace App\Controller;

use App\Entity\Offer;
use App\Form\ReviewFormType;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Review;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/review')]
final class ReviewController extends AbstractController
{
    #[Route('/', name: 'app_review')]
    public function index(): Response
    {
        return $this->render('review/index.html.twig', [
            'controller_name' => 'ReviewController',
        ]);
    }

    #[Route('/add/{id}', name: 'review_add', methods: ['GET', 'POST'])]
    public function add(Offer $offer, Request $request, EntityManagerInterface $entityManager, ReviewRepository $reviewRepository): Response
    {
        $user = $this->getUser();

        // Vérifier si l'utilisateur est connecté
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour laisser un avis.');
            return $this->redirectToRoute('security_login');
        }

        // Vérifier si l'utilisateur a déjà laissé un avis sur cette offre
        if ($reviewRepository->findOneBy(['author' => $user, 'offer' => $offer])) {
            $this->addFlash('error', 'Vous avez déjà noté cette offre.');
            return $this->redirectToRoute('offer_show', ['id' => $offer->getId()]);
        }

        // Vérifier si l'utilisateur ne note pas sa propre offre
        if ($offer->getUser() === $user) {
            $this->addFlash('error', 'Vous ne pouvez pas noter votre propre offre.');
            return $this->redirectToRoute('offer_show', ['id' => $offer->getId()]);
        }

        $review = new Review();
        $form = $this->createForm(ReviewFormType::class, $review);
        $form->handleRequest($request);
        dump("Données envoyées par le formulaire :", $request->request->all());
        dump("Données récupérées par Symfony :", $form->getData());
        dump("Formulaire soumis ?", $form->isSubmitted());

        if ($form->isSubmitted() && $form->isValid()) {
            $review->setAuthor($user);
            $review->setOffer($offer);
            $review->setComment($form->get('comment')->getData());
            $review->setRating($form->get('rating')->getData());
            $review->setReviwedUser($offer->getUser());
            $entityManager->persist($review);
            $entityManager->flush();

            $this->addFlash('success', 'Avis ajouté avec succès.');
            return $this->redirectToRoute('offer_show', ['id' => $offer->getId()]);
        }

        return $this->render('offer/show.html.twig', [
            'offer' => $offer,
            'form' => $form->createView(),
        ]);

    }
}
