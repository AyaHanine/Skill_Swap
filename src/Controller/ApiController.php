<?php

namespace App\Controller;

use App\Entity\Offer;
use App\Entity\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Routing\Attribute\Route;


#[Route('/api', name: 'api_')]

final class ApiController extends AbstractController
{
    #[Route('/offers', name: 'get_offers', methods: ['GET'])]
    public function getOffers(EntityManagerInterface $entityManager, SerializerInterface $serializer): JsonResponse
    {
        $offers = $entityManager->getRepository(Offer::class)->findAll();
        $data = $serializer->normalize($offers, null, ['groups' => 'offer:read']);

        return new JsonResponse($data, JsonResponse::HTTP_OK);
    }

    #[Route('/offers', name: 'create_offer', methods: ['POST'])]
    public function createOffer(\Symfony\Component\HttpFoundation\Request $request, EntityManagerInterface $entityManager, SerializerInterface $serializer): JsonResponse
    {
        $offer = $serializer->deserialize($request->getContent(), Offer::class, 'json');
        $entityManager->persist($offer);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Offer created successfully!'], JsonResponse::HTTP_CREATED);
    }
}
