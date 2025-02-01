<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\Publisher;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Attribute\Route;

final class ChatController extends AbstractController
{
    private $publisher;

    public function __construct(Publisher $publisher)
    {
        $this->publisher = $publisher;
    }

    #[Route('/chat', name: 'chat')]
    public function chat(): Response
    {
        return $this->render('chat/index.html.twig');
    }

    #[Route('/chat/send', name: 'chat_send', methods: ['POST'])]
    public function sendMessage(Request $request): JsonResponse
    {
        // Récupérer le contenu du message
        $content = $request->get('content');

        // Créer un objet Update qui représente la mise à jour du message
        $update = new Update(
            'chat', // Le topic (peut être un canal ou un sujet spécifique)
            json_encode(['content' => $content]) // Le message à envoyer (en JSON)
        );

        // Publier la mise à jour sur le hub Mercure
        $this->publisher->__invoke($update);

        return new JsonResponse(['status' => 'Message envoyé']);
    }
}
