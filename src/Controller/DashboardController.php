<?php

namespace App\Controller;

use App\Repository\MessageRepository;
use App\Repository\OfferRepository;
use App\Repository\RequestRepository;
use App\Repository\ReviewRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dashboard')]

final class DashboardController extends AbstractController
{
    #[Route('/', name: 'dashboard')]
    public function index(
        OfferRepository $offerRepository,
        RequestRepository $requestRepository,
        MessageRepository $messageRepository,
        ReviewRepository $reviewRepository
    ) {
        $user = $this->getUser();

        $latestOffers = $offerRepository->findBy([], ['createdAt' => 'DESC'], 5);

        $pendingRequests = $requestRepository->findBy(['user' => $user, 'status' => 'pending']);

        $unreadMessages = $messageRepository->findBy(['receiver' => $user]);

        dump($userReviews = $reviewRepository->findBy(['reviwedUser' => $user]));

        $requests = $requestRepository->findReceivedRequests($user);



        return $this->render('dashboard/index.html.twig', [
            'latest_offers' => $latestOffers,
            'pending_requests' => $pendingRequests,
            'unread_messages' => $unreadMessages,
            'user_reviews' => $userReviews,
            'requests' => $requests,
            'User' => $user
        ]);
    }
}
