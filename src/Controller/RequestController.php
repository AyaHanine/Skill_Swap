<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Notification;
use App\Entity\Offer;
use App\Entity\Request;
use App\Entity\User;
use App\Enum\OfferStatus;
use App\Enum\RequestStatus;
use App\Form\RequestType;
use App\Repository\OfferRepository;
use App\Repository\RequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/request')]
final class RequestController extends AbstractController
{
    #[Route('/request', name: 'request_index')]
    public function index(RequestRepository $requestRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('security_login');
        }

        return $this->render('request/index.html.twig', [
            'sentRequests' => $requestRepository->findBy(['user' => $user]),
            'receivedRequests' => $requestRepository->createQueryBuilder('r')
                ->leftJoin('r.offer', 'o')
                ->where('o.user = :user')
                ->setParameter('user', $user)
                ->getQuery()
                ->getResult(),
        ]);
    }



    #[Route('/new', name: 'app_request_new', methods: ['GET', 'POST'])]
    public function new(HttpRequest $request, EntityManagerInterface $entityManager): Response
    {
        $request = new Request();
        $form = $this->createForm(RequestType::class, $request);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($request);
            $entityManager->flush();

            return $this->redirectToRoute('app_request_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('request/new.html.twig', [
            'request' => $request,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_request_show', methods: ['GET'])]
    public function show(HttpRequest $request): Response
    {
        return $this->render('request/show.html.twig', [
            'request' => $request,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_request_edit', methods: ['GET', 'POST'])]
    public function edit(HttpRequest $httpRequest, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RequestType::class, $request);
        $form->handleRequest($httpRequest);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_request_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('request/edit.html.twig', [
            'request' => $httpRequest,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_request_delete', methods: ['POST'])]
    public function delete(HttpRequest $httpRequest, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $request->getId(), $httpRequest->getPayload()->getString('_token'))) {
            $entityManager->remove($request);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_request_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/send/{id}', name: 'request_send', methods: ['POST', 'GET'])]
    public function sendRequest(
        HttpRequest $request,
        EntityManagerInterface $entityManager,
        OfferRepository $offerRepository,
        int $id
    ): Response {

        // Récupérer l'utilisateur connecté
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour envoyer une demande.');
            return $this->redirectToRoute('offer_index');
        }

        // Récupérer l'offre via son ID
        $offer = $offerRepository->find($id);
        if (!$offer) {
            $this->addFlash('error', 'Offre non trouvée.');
            return $this->redirectToRoute('offer_index');
        }

        // Vérifier que l'utilisateur ne postule pas à sa propre offre
        if ($offer->getUser() === $user) {
            $this->addFlash('error', 'Vous ne pouvez pas envoyer une demande pour votre propre offre.');
            return $this->redirectToRoute('offer_show', ['id' => $id]);
        }

        // Vérifier que le statut de l'offre est bien "disponible"
        if ($offer->getStatus() !== OfferStatus::Disponible) {
            $this->addFlash('error', 'Cette offre n\'est pas disponible.');
            return $this->redirectToRoute('offer_show', ['id' => $id]);
        }

        // Vérifier si l'utilisateur a déjà fait une demande pour cette offre
        $existingRequest = $entityManager->getRepository(Request::class)->findOneBy([
            'user' => $user,
            'offer' => $offer
        ]);

        if ($existingRequest) {
            $this->addFlash('warning', 'Vous avez déjà envoyé une demande pour cette offre.');
            return $this->redirectToRoute('offer_show', ['id' => $id]);
        }

        // Récupérer le message depuis le JSON envoyé par le front
        $data = json_decode($request->getContent(), true);
        $message = $data['message'] ?? '';

        // Créer une nouvelle demande
        $requestEntity = new Request();
        $requestEntity->setUser($user);
        $requestEntity->setOffer($offer);
        $requestEntity->setMessage($message);
        $requestEntity->setStatus(RequestStatus::EnAttente);
        $requestEntity->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($requestEntity);

        // Créer une notification pour l'auteur de l'offre
        $notification = new Notification();
        $notification->setUser($offer->getUser());
        $notification->setMessage("Vous avez reçu une nouvelle demande de la part de {$user->getFirstName()} {$user->getLastName()} !");
        $notification->setCreatedAt(new \DateTimeImmutable());
        $notification->setIsRead(false);

        $entityManager->persist($notification);
        $entityManager->flush();

        $this->addFlash('success', 'Demande envoyée avec succès !');
        return $this->redirectToRoute('offer_show', ['id' => $id]);
    }




    #[Route('/{id}/accept', name: 'request_accept', methods: ['POST', 'GET'])]
    public function acceptRequest(Request $requestEntity, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if ($requestEntity->getOffer()->getUser() !== $user) {
            $this->addFlash('error', 'Vous ne pouvez pas accepter cette demande.');
            return $this->redirectToRoute('dashboard');
        }

        // Mettre la demande à "acceptée"
        $requestEntity->setStatus(RequestStatus::Acceptee);

        // Changer le statut de l'offre à "réservée"
        $offer = $requestEntity->getOffer();
        $offer->setStatus(OfferStatus::Reserve);

        $entityManager->flush();

        // Vérifier si une conversation existe déjà entre ces deux utilisateurs
        $existingConversation = $entityManager->getRepository(Conversation::class)->findOneBy([
            'userOne' => $requestEntity->getUser(),
            'userTwo' => $requestEntity->getOffer()->getUser(),
        ]) ?? $entityManager->getRepository(Conversation::class)->findOneBy([
                        'userOne' => $requestEntity->getUser(),
                        'userTwo' => $requestEntity->getOffer()->getUser(),
                    ]);

        if (!$existingConversation) {
            // Créer une nouvelle conversation
            $conversation = new Conversation();
            $conversation->setUserOne($requestEntity->getUser());
            $conversation->setUserTwo($requestEntity->getOffer()->getUser());
            $conversation->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($conversation);
            $entityManager->flush();
        }

        // Notifier l'utilisateur demandeur
        $notification = new Notification();
        $notification->setUser($requestEntity->getUser());
        $notification->setMessage("Votre demande pour '{$offer->getTitle()}' a été acceptée ! Le chat est désormais activé !");
        $notification->setCreatedAt(new \DateTimeImmutable());
        $notification->setIsRead(false);

        $entityManager->persist($notification);
        $entityManager->flush();

        $this->addFlash('success', 'Demande acceptée et offre réservée.');
        return $this->redirectToRoute('dashboard');
    }

    #[Route('/{id}/decline', name: 'request_decline', methods: ['POST', 'GET'])]
    public function declineRequest(Request $requestEntity, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if ($requestEntity->getOffer()->getUser() !== $user) {
            $this->addFlash('error', 'Vous ne pouvez pas refuser cette demande.');
            return $this->redirectToRoute('dashboard');
        }

        // Supprimer la demande
        $entityManager->remove($requestEntity);
        $entityManager->flush();

        // Notifier l'utilisateur demandeur
        $notification = new Notification();
        $notification->setUser($requestEntity->getUser());
        $notification->setMessage("Votre demande pour '{$requestEntity->getOffer()->getTitle()}' a été refusée.");
        $notification->setCreatedAt(new \DateTimeImmutable());
        $notification->setIsRead(false);

        $entityManager->persist($notification);
        $entityManager->flush();

        $this->addFlash('info', 'Demande refusée.');
        return $this->redirectToRoute('dashboard');
    }


}
