<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Form\MessageType;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Publisher;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Attribute\Route;

final class ChatController extends AbstractController
{


    #[Route('/chat', name: 'chat_index')]
    public function index(ConversationRepository $conversationRepository): Response
    {
        $conversations = $conversationRepository->findByUser($this->getUser()->getId());
        return $this->render('chat/index.html.twig', ['conversations' => $conversations]);
    }

    #[Route('/chat/{id}', name: 'chat_conversation')]
    public function conversation(
        Conversation $conversation,
        MessageRepository $messageRepository,
        Request $request,
        EntityManagerInterface $entityManager,
        HubInterface $hub
    ): Response {
        // Vérifier si l'utilisateur appartient bien à cette conversation
        if ($conversation->getUserOne() !== $this->getUser() && $conversation->getUserTwo() !== $this->getUser()) {
            throw $this->createAccessDeniedException("Vous n'avez pas accès à cette conversation.");
        }

        $messages = $messageRepository->findBy(['conversation' => $conversation], ['createdAt' => 'ASC']);

        // Formulaire d'envoi de message
        $message = new Message();
        $form = $this->createForm(MessageType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $message->setSender($this->getUser());
            $message->setReceiver($conversation->getUserOne() === $this->getUser() ? $conversation->getUserTwo() : $conversation->getUserOne());
            $message->setConversation($conversation);
            $message->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($message);
            $entityManager->flush();

            // 🔴 Publier le message sur Mercure pour le temps réel
            $topic = "http://localhost/chat/" . $conversation->getId();
            error_log("🔴 Mercure : Envoi du message sur le topic " . $topic);

            $update = new Update(
                $topic,
                json_encode([
                    'sender' => $this->getUser()->getLastName(), // Vérifie que getLastName() existe bien
                    'message' => $message->getContent(),
                    'sentAt' => $message->getCreatedAt()->format('Y-m-d H:i:s'),
                ])
            );

            $hub->publish($update);

            return $this->redirectToRoute('chat_conversation', ['id' => $conversation->getId()]);
        }

        return $this->render('chat/conversation.html.twig', [
            'conversation' => $conversation,
            'messages' => $messages,
            'form' => $form->createView(),
        ]);
    }

}
