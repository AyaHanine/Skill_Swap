<?php

namespace App\Controller;

use App\Entity\Skill;
use App\Entity\User;
use App\Enum\SkillStatus;
use App\Form\RegistrationFormType;
use App\Security\EmailVerifier;
use App\Security\LoginFormAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelper;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class RegistrationController extends AbstractController
{

    private VerifyEmailHelperInterface $verifyEmailHelper;

    public function __construct(VerifyEmailHelperInterface $verifyEmailHelper)
    {
        $this->verifyEmailHelper = $verifyEmailHelper;
    }

    #[Route('/register', name: 'register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        Security $security,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
    ): Response {
        $user = new User();
        $competences = $entityManager->getRepository(Skill::class)->findAll();

        $competencesFiltrées = array_filter($competences, function ($skill) {
            return $skill->getStatus() !== SkillStatus::enAttente; // Ne garder que les compétences non "en attente"
        });
        $form = $this->createForm(RegistrationFormType::class, $user, ['skills' => $competencesFiltrées]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->flush();

            $this->addFlash('success', 'Votre compétence a été soumise pour validation.');



            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));
            $user->setRoles(['ROLE_USER']);
            $user->setIsVerified(false);

            $entityManager->persist($user);
            $entityManager->flush();

            $signatureComponents = $this->verifyEmailHelper->generateSignature(
                'verify_email',
                $user->getId(),
                $user->getEmail(),
                ['id' => $user->getId()]
            );

            $email = (new TemplatedEmail())
                ->from(new Address('ayahanine72@gmail.com', 'SkillSWapp_tech'))
                ->to(new Address($user->getEmail()))
                ->subject('Please Confirm your Email')
                ->htmlTemplate('emails/confirmation_email.html.twig')
                ->context([
                    'signedUrl' => $signatureComponents->getSignedUrl(),
                    'expiresAtMessageKey' => $signatureComponents->getExpirationMessageKey(),
                    'expiresAtMessageData' => $signatureComponents->getExpirationMessageData(),
                ]);
            $mailer->send($email);
            $this->addFlash('success', 'Un email de confirmation vous a été envoyé. Vérifiez votre boite mail.');
            return $this->redirectToRoute('wait_for_verification');
        }
        return $this->render(
            'registration/register.html.twig',
            [
                'registrationForm' => $form->createView(),
            ]
        );
    }


    #[Route('/verify/email', name: 'verify_email')]
    public function verifyUserEmail(Request $request, EntityManagerInterface $entityManager): Response
    {
        $userId = $request->query->get('id');

        if (null === $userId) {
            throw $this->createNotFoundException('Aucun ID utilisateur trouvé');

        }

        $user = $entityManager->getRepository(User::class)->find($userId);

        if (null === $user) {
            throw $this->createNotFoundException('utilisateur non trouvé');
        }

        try {
            $this->verifyEmailHelper->validateEmailConfirmation($request->getUri(), $user->getId(), $user->getEmail());
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('error', $exception->getReason());
            return $this->redirectToRoute('security_login');
        }
        $user->setIsVerified(true);
        $user->setBio("tets");
        $entityManager->flush();
        $this->addFlash('success', 'Votre email a été vérifié avec succès ! Vous pouvez maintenant vous connecter.');

        return $this->redirectToRoute('security_login');
    }

    #[Route('/resend-verification-email', name: 'resend_verification_email')]
    public function resendVerificationEmail(MailerInterface $mailer, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('security_login');
        }

        if ($user->isVerified()) {
            $this->addFlash('info', 'Votre email est déjà vérifié.');
            return $this->redirectToRoute('dashboard');
        }

        $signatureComponents = $this->verifyEmailHelper->generateSignature(
            'verify_email',
            $user->getId(),
            $user->getEmail(),
            ['id' => $user->getId()]
        );

        $email = (new TemplatedEmail())
            ->from(new Address('ayahanine72@gmail.com', 'SkillSwap'))
            ->to(new Address($user->getEmail()))
            ->subject('Vérifiez votre adresse email')
            ->htmlTemplate('emails/confirmation_email.html.twig')
            ->context([
                'signedUrl' => $signatureComponents->getSignedUrl(),
                'expiresAtMessageKey' => $signatureComponents->getExpirationMessageKey(),
                'expiresAtMessageData' => $signatureComponents->getExpirationMessageData(),
            ]);

        $mailer->send($email);

        $this->addFlash('success', 'Un nouvel email de confirmation vous a été envoyé.');

        return $this->redirectToRoute('wait_for_verification');
    }

}
