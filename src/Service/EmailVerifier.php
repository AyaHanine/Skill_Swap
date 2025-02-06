<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class EmailVerifier
{
    private $mailer;
    private $verifyEmailHelper;

    public function __construct(MailerInterface $mailer, VerifyEmailHelperInterface $verifyEmailHelper)
    {
        $this->mailer = $mailer;
        $this->verifyEmailHelper = $verifyEmailHelper;
    }

    public function sendEmailConfirmation(string $userEmail, string $userId): void
    {
        $signatureComponents = $this->verifyEmailHelper->generateSignature(
            'verify_email',
            $userId,
            $userEmail
        );

        $email = (new Email())
            ->from('ton-email@exemple.com')
            ->to($userEmail)
            ->subject('Confirmez votre e-mail')
            ->html('<p>Veuillez confirmer votre e-mail en cliquant sur <a href="' . $signatureComponents->getSignedUrl() . '">ce lien</a>.</p>');

        $this->mailer->send($email);
    }
}
