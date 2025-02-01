<?php

namespace App\EventSubscriber;

use App\Repository\NotificationRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

class NotificationSubscriber implements EventSubscriberInterface
{

    private NotificationRepository $notificationRepository;
    private Security $security;
    private Environment $twig;

    public function __construct(NotificationRepository $notificationRepository, Security $security, Environment $twig)
    {
        $this->notificationRepository = $notificationRepository;
        $this->security = $security;
        $this->twig = $twig;
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $user = $this->security->getUser();

        if ($user) {
            $notifications = $this->notificationRepository->findUnreadByUser($user);
            $this->twig->addGlobal('notifications', $notifications);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }
}
