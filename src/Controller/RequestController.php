<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\Offer;
use App\Entity\Request;
use App\Form\RequestType;
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
    #[Route(name: 'app_request_index', methods: ['GET'])]
    public function index(RequestRepository $requestRepository): Response
    {
        return $this->render('request/index.html.twig', [
            'requests' => $requestRepository->findAll(),
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

    #[Route('/send/{id}', name: 'request_send', methods: ['POST'])]
    public function sendRequest(Offer $offer, \Symfony\Component\HttpFoundation\Request $request, EntityManagerInterface $entityManager): JsonResponse
    {


        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Vous devez être connecté pour envoyer une demande.'], Response::HTTP_UNAUTHORIZED);
        }

        if ($offer->getUser() === $user) {
            return new JsonResponse(['message' => 'Vous ne pouvez pas envoyer une demande pour votre propre offre.'], Response::HTTP_FORBIDDEN);
        }

        // 🚨 Bloquer les demandes si l'offre est réservée
        if ($offer->getStatus() === 'reserved') {
            return new JsonResponse(['message' => 'Cette offre est déjà réservée. Vous ne pouvez plus envoyer de demande.'], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier si les données JSON sont valides
        $data = json_decode($request->getContent(), true);
        if ($data === null) {
            return new JsonResponse(['message' => 'Format JSON invalide.'], Response::HTTP_BAD_REQUEST);
        }


        $message = $data['message'] ?? '';

        $requestEntity = new Request();
        $requestEntity->setUser($user);
        $requestEntity->setOffer($offer);
        $requestEntity->setMessage($message);
        $requestEntity->setStatus('pending');


        $entityManager->persist($requestEntity);

        // Créer une notification pour l'auteur de l'offre
        $notification = new Notification();
        $notification->setUser($offer->getUser());
        $notification->setMessage("Vous avez reçu une nouvelle demande de la part de {$user->getFirstName()} {$user->getLastName()} !");
        $notification->setCreatedAt(new \DateTimeImmutable());
        $notification->setIsRead(false);
        $entityManager->persist($notification);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Votre demande a bien été envoyée !'], 200);

    }

    #[Route('/request/{id}/accept', name: 'request_accept', methods: ['POST', 'GET'])]
    public function acceptRequest(Request $requestEntity, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if ($requestEntity->getOffer()->getUser() !== $user) {
            $this->addFlash('error', 'Vous ne pouvez pas accepter cette demande.');
            return $this->redirectToRoute('dashboard');
        }

        // Mettre la demande à "acceptée"
        $requestEntity->setStatus('accepted');

        // Changer le statut de l'offre à "réservée"
        $offer = $requestEntity->getOffer();
        $offer->setStatus('reserved');

        $entityManager->flush();

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

    #[Route('/request/{id}/decline', name: 'request_decline', methods: ['POST', 'GET'])]
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
