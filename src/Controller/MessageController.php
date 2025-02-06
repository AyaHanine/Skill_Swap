<?php

namespace App\Controller;

use App\Entity\Message;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;

final class MessageController extends AbstractController
{
    #[Route('/message', name: 'app_message')]
    public function index(): Response
    {
        return $this->render('message/index.html.twig', [
            'controller_name' => 'MessageController',
        ]);
    }

    #[Route('/send', name: 'send_message', methods: ['POST'])]
    public function sendMessage(Request $request, EntityManagerInterface $entityManager, HubInterface $hub): JsonResponse
    {

        $data = json_decode($request->getContent(), true);
        $sender = $this->getUser();
        $receiver = $entityManager->getRepository(User::class)->find($data['receiverId']);

        if (!$receiver) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé'], 404);
        }

        // Vérifier si le message est vide
        if (!isset($data['message']) || empty($data['message'])) {
            return new JsonResponse(['error' => 'Message vide'], 400);
        }

        $conversationId = $data['conversation_id'];

        $message = new Message();
        $message->setContent($data['content']);
        $message->setSender($sender);
        $message->setReceiver($receiver);

        $entityManager->persist($message);
        $entityManager->flush();

        // Définir un topic basé sur l'ID de la conversation
        $topic = 'http://localhost/chat/' . $conversationId;

        // Publier le message en temps réel avec Mercure
        $update = new Update(
            $topic,
            json_encode([
                'sender' => $this->getUser()->getId(),
                'message' => $data['message'],
                'sentAt' => (new \DateTime())->format('Y-m-d H:i:s'),
            ])
        );

        $hub->publish($update);

        return new JsonResponse(['status' => 'Message envoyé']);

    }

}
