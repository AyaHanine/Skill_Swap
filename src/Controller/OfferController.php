<?php

namespace App\Controller;

use App\Entity\Offer;
use App\Entity\Review;
use App\Form\OfferType;
use App\Form\ReviewFormType;
use App\Repository\OfferRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Knp\Component\Pager\PaginatorInterface;


#[Route('/offer')]
#[IsGranted('ROLE_USER')]
final class OfferController extends AbstractController
{
    #[Route(name: 'offer_index', methods: ['GET'])]
    public function index(OfferRepository $offerRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $query = $offerRepository->createQueryBuilder('o')
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1), // Numéro de la page actuelle
            6 // Nombre d'offres par page
        );

        return $this->render('offer/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }


    #[Route('/new', name: 'offer_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $offer = new Offer();
        $form = $this->createForm(OfferType::class, $offer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $offer->setUser($this->getUser());
            $offer->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($offer);
            $entityManager->flush();

            return $this->redirectToRoute('offer_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('offer/new.html.twig', [
            'offer' => $offer,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'offer_show', methods: ['GET', 'POST'])]
    public function show(Offer $offer): Response
    {
        // Créer un formulaire vide pour éviter l'erreur dans Twig
        $review = new Review();
        $form = $this->createForm(ReviewFormType::class, $review);

        return $this->render('offer/show.html.twig', [
            'offer' => $offer,
            'form' => $form->createView(), // ⚠️ Envoie bien le formulaire à Twig
        ]);
    }

    #[Route('/{id}/edit', name: 'offer_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Offer $offer, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('edit', $offer);

        $form = $this->createForm(OfferType::class, $offer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('offer_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('offer/edit.html.twig', [
            'offer' => $offer,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'offer_delete', methods: ['POST'])]
    public function delete(Request $request, Offer $offer, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('delete', $offer);

        if ($this->isCsrfTokenValid('delete' . $offer->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($offer);
            $entityManager->flush();
        }

        return $this->redirectToRoute('offer_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/offer/search', name: 'offer_search', methods: ['GET'])]
    public function search(Request $request, OfferRepository $offerRepository): Response
    {
        $query = $request->query->get('q');

        if (!$query) {
            return $this->redirectToRoute('offer_index');
        }

        $offers = $offerRepository->searchOffers($query);

        return $this->render('offer/index.html.twig', [
            'offers' => $offers,
        ]);
    }
}
