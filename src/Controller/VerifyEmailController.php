<?php

namespace App\Controller;

use App\Entity\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelper;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;
use App\Entity\User;

final class VerifyEmailController extends AbstractController
{
    private VerifyEmailHelper $verifyEmailHelper;
    private EntityManagerInterface $entityManager;

    public function __construct(VerifyEmailHelperInterface $verifyEmailHelper, EntityManagerInterface $entityManager)
    {
        $this->verifyEmailHelper = $verifyEmailHelper;
        $this->entityManager = $entityManager;
    }

    #[Route('/verify/email', name: 'verify_email')]
    public function verifyEmail(\Symfony\Component\HttpFoundation\Request $request): Response
    {
        $userId = $request->query->get('id');

        if (!$userId) {
            return new Response('User ID missing', Response::HTTP_BAD_REQUEST);
        }

        $user = $this->entityManager->getRepository(User::class)->find($userId);

        if (!$user) {
            return new Response('User not found', Response::HTTP_NOT_FOUND);
        }

        try {
            $this->verifyEmailHelper->validateEmailConfirmation($request->getUri(), $user->getId(), $user->getEmail());
            $user->setIsVerified(true);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            return new Response('Invalid token', Response::HTTP_BAD_REQUEST);
        }

        return new Response('Email verified successfully! 🎉');
    }
}
