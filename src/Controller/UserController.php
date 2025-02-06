<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\Offer;
use App\Entity\Report;
use App\Entity\Skill;
use App\Enum\SkillStatus;
use App\Form\UserEditFormType;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user')]
final class UserController extends AbstractController
{

    private UserPasswordHasherInterface $passwordHasher;

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/all', name: 'app_user')]
    public function manageUsers(EntityManagerInterface $entityManager): Response
    {
        $users = $entityManager->getRepository(User::class)->findAll();

        return $this->render('user/manage_user.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/{id}/profile', name: 'user_profile', methods: ['GET'])]
    public function show(User $user): Response
    {
        $competencesValidées = $user->getSkills()->filter(function ($competence) {
            return $competence->getStatus() !== SkillStatus::enAttente;  // Filtrer celles avec le statut "en attente"
        });
        return $this->render('user/index.html.twig', [
            'user' => $user,
            'skills' => $competencesValidées
        ]);
    }


    #[IsGranted('ROLE_ADMIN')]
    #[Route('/new', name: 'add_user')]
    public function newUser(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $encodedPassword = $passwordHasher->hashPassword($user, 'passwordProvisoir');
            $user->setPassword($encodedPassword);

            // Enregistrer le nouvel utilisateur dans la base de données

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur ajouté avec succès.');

            // Rediriger vers la liste des utilisateurs
            return $this->redirectToRoute('app_user');
        }

        return $this->render('user/new_user.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit_user')]
    #[IsGranted('ROLE_ADMIN')]
    public function editUser(User $user, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Créer le formulaire pour modifier un utilisateur
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setUpdatedAt(new \DateTimeImmutable());
            // Mettre à jour les informations de l'utilisateur
            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur modifié avec succès.');

            // Rediriger vers la liste des utilisateurs
            return $this->redirectToRoute('app_user');
        }

        return $this->render('user/edit_user.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/edit', name: 'user_edit')]
    public function edit(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $skills = $entityManager->getRepository(Skill::class)->findAll();

        $filtredSkills = array_filter($skills, function ($skill) {
            return $skill->getStatus() == SkillStatus::validé;
        });
        $form = $this->createForm(UserEditFormType::class, $user, ['skills' => $filtredSkills]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $otherSkills = $form->get('competence_autre')->getData();
            if ($otherSkills) {
                $competence = new Skill();
                $competence->setName($otherSkills);
                $competence->setStatus(SkillStatus::enAttente);
                $competence->setProposedBy($user);

                $entityManager->persist($competence);
                $entityManager->flush();

                $this->addFlash('success', 'Votre compétence a été soumise pour validation.');
            }

            $entityManager->flush();
            $this->addFlash('success', 'Vos informations ont été mises à jour avec succès.');
            return $this->redirectToRoute('dashboard');
        }

        return $this->render('user/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}/delete', name: 'delete_user')]
    public function deleteUser(User $user, EntityManagerInterface $entityManager): Response
    {

        $reports = $entityManager->getRepository(Report::class)->findBy(['repportedUser' => $user]);
        foreach ($reports as $report) {
            $entityManager->remove($report);
        }
        $offers = $entityManager->getRepository(Offer::class)->findBy(['user' => $user]);
        foreach ($offers as $offer) {
            $entityManager->remove($offer);
        }
        $requests = $entityManager->getRepository(\App\Entity\Request::class)->findBy(['user' => $user]);
        foreach ($requests as $request) {
            $entityManager->remove($request);
        }
        $skills = $entityManager->getRepository(Skill::class)->findAll(); // Récupère toutes les compétences

        foreach ($skills as $skill) {
            if ($skill->getUsers()->contains($user)) { // Vérifie si l'utilisateur est dans la collection
                $skill->removeUser($user); // Supposons que tu as une méthode removeUser() dans Skill
                $entityManager->persist($skill);
            }
        }
        $notifications = $entityManager->getRepository(Notification::class)->findBy(['user' => $user]);
        foreach ($notifications as $notification) {
            $entityManager->remove($notification);
        }
        // Supprimer l'utilisateur de la base de données
        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('success', 'Utilisateur supprimé avec succès.');

        return $this->redirectToRoute('app_users');
    }
}
