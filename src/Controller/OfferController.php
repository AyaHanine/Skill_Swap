<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\Offer;
use App\Entity\Report;
use App\Entity\Review;
use App\Enum\OfferStatus;
use App\Form\OfferType;
use App\Form\ReviewFormType;
use App\Repository\OfferRepository;
use App\Service\MailService;
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
    public function index(
        OfferRepository $offerRepository,
        PaginatorInterface $paginator,
        Request $request,
        MailService $mailService
    ): Response {
        $user = $this->getUser();
        $type = $request->query->get('type', 'all'); // Type d'offres sélectionné
        $search = $request->query->get('search', ''); // Terme de recherche

        $mailService->sendEmail(
            'ayahanine721@gmail.com',
            'Bienvenue sur notre site !',
            '<p>Merci de vous être inscrit ! 🎉</p>'
        );


        // Déterminer la requête en fonction du filtre sélectionné
        switch ($type) {
            case 'mine':
                $queryBuilder = $offerRepository->createQueryBuilder('o')
                    ->where('o.user = :user')
                    ->setParameter('user', $user);
                break;

            case 'matching':
                $queryBuilder = $offerRepository->createQueryBuilder('o')
                    ->join('o.skillWanted', 's')
                    ->join('s.users', 'u')
                    ->where('u = :user')
                    ->setParameter('user', $user);
                break;

            case 'all':
            default:
                $queryBuilder = $offerRepository->createQueryBuilder('o');
                break;
        }

        // Appliquer la recherche si un terme est entré
        if (!empty($search)) {
            $queryBuilder->andWhere('o.title LIKE :search OR o.description LIKE :search')
                ->setParameter('search', "%$search%");
        }

        // Trier par date de création (les plus récentes en premier)
        $queryBuilder->orderBy('o.createdAt', 'DESC');

        // Exécuter la requête et paginer les résultats
        $query = $queryBuilder->getQuery();
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            6
        );

        return $this->render('offer/index.html.twig', [
            'pagination' => $pagination,
            'type' => $type,
            'search' => $search, // Permet de conserver la valeur du champ de recherche
        ]);
    }


    #[Route('/get', name: 'offers_get', methods: ['GET'])]
    public function getOffers(
        OfferRepository $offerRepository,
        Request $request,
        PaginatorInterface $paginator
    ): Response {
        $user = $this->getUser();
        $type = $request->query->get('type', 'all');

        if (!$user) {
            return new Response('Vous devez être connecté.', 403);
        }

        switch ($type) {
            case 'mine':
                $query = $offerRepository->createQueryBuilder('o')
                    ->where('o.user = :user')
                    ->setParameter('user', $user)
                    ->orderBy('o.createdAt', 'DESC')
                    ->getQuery();
                break;

            case 'matching':
                $query = $offerRepository->findOffersForUser($user);
                break;

            case 'all':
            default:
                $query = $offerRepository->createQueryBuilder('o')
                    ->orderBy('o.createdAt', 'DESC')
                    ->getQuery();
                break;
        }

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            6
        );

        return $this->render('offer/_offers.html.twig', [
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
            $offer->setStatus(OfferStatus::Disponible);


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

    #[Route('/{id}/delete', name: 'offer_delete', methods: ['POST', 'GET'])]
    public function delete(Request $request, Offer $offer, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('delete', $offer);


        if ($this->isCsrfTokenValid('delete' . $offer->getId(), $request->get('_token'))) {
            // Suppression des demandes associées
            foreach ($offer->getRequests() as $request) {

                $entityManager->remove($request); // Supprimer les demandes associées
                $notification = new Notification();
                $notification->setMessage('L\'offre "' . $offer->getTitle() . '" a été supprimée.')
                    ->setUser($request->getUser()) // Utilise l'utilisateur qui a fait la demande
                    ->setCreatedAt(new \DateTimeImmutable());

                // Sauvegarder la notification dans la base de données
                $entityManager->persist($notification);
            }
            $entityManager->flush(); // Effectuer les suppressions des demandes



            // Suppression des rapports associés à l'offre
            foreach ($offer->getReports() as $report) {
                $entityManager->remove($report); // Supprimer les rapports associés
            }
            $entityManager->flush(); // Effectuer la suppression des rapports

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

    #[Route('/offers/matching', name: 'offers_matching')]
    public function matchingOffers(OfferRepository $offerRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('security_login');
        }

        $matchingOffers = $offerRepository->findOffersForUser($user);

        return $this->render('offer/matching.html.twig', [
            'offers' => $matchingOffers,
        ]);
    }


    #[IsGranted('ROLE_ADMIN')]
    #[Route('/all', name: 'offers_all')]
    public function allOffers(OfferRepository $offerRepository): Response
    {
        $allOffers = $offerRepository->findAll();

        return $this->render('offer/all.html.twig', [
            'offers' => $allOffers,
        ]);
    }

    #[isGranted('ROLE_ADMIN')]
    #[Route('/manage', name: 'admin_offers')]
    public function manageOffers(EntityManagerInterface $entityManager): Response
    {
        // Récupérer toutes les offres
        $offers = $entityManager->getRepository(Offer::class)->findAll();

        return $this->render('admin/manage_offers.html.twig', [
            'offers' => $offers,
        ]);
    }

    // Ajouter une nouvelle offre
    #[Route('/new', name: 'admin_new_offer')]
    #[isGranted('ROLE_ADMIN')]
    public function newOffer(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Créer une nouvelle offre
        $offer = new Offer();

        // Créer le formulaire pour ajouter une offre
        $form = $this->createForm(OfferType::class, $offer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Enregistrer la nouvelle offre dans la base de données
            $entityManager->persist($offer);
            $entityManager->flush();

            $this->addFlash('success', 'Offre ajoutée avec succès.');

            return $this->redirectToRoute('admin_offers');
        }

        return $this->render('admin/new_offer.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_edit_offer')]
    #[isGranted('ROLE_ADMIN')]
    public function editOffer(Offer $offer, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Créer le formulaire pour modifier l'offre
        $form = $this->createForm(OfferType::class, $offer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Mettre à jour l'offre dans la base de données
            $entityManager->flush();

            $this->addFlash('success', 'Offre modifiée avec succès.');

            return $this->redirectToRoute('admin_offers');
        }

        return $this->render('admin/edit_offer.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_delete_offer')]
    #[isGranted('ROLE_ADMIN')]
    public function deleteOffer(Offer $offer, EntityManagerInterface $entityManager): Response
    {
        $reports = $entityManager->getRepository(Report::class)->findBy(['Offer' => $offer]);
        foreach ($reports as $report) {
            $entityManager->remove($report);
        }

        // Suppression des demandes associées
        foreach ($offer->getRequests() as $request) {

            $entityManager->remove($request); // Supprimer les demandes associées

        }
        // Supprimer l'offre de la base de données
        $entityManager->remove($offer);
        $entityManager->flush();

        $this->addFlash('success', 'Offre supprimée avec succès.');

        return $this->redirectToRoute('admin_offers');
    }

}
