<?php

namespace App\Controller;

use App\Entity\Skill;
use App\Enum\SkillStatus;
use App\Form\SkillSearchType;
use App\Form\SkillType;
use App\Repository\SkillRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/skills')]
#[IsGranted("ROLE_ADMIN")]
final class SkillController extends AbstractController
{
    #[Route(name: 'app_skill')]
    public function index(Request $request, EntityManagerInterface $entityManager, SkillRepository $skillRepository): Response
    {

        $form = $this->createForm(SkillSearchType::class, null, ['method' => 'GET']);
        $form->handleRequest($request);

        $filters = $form->getData() ?: [];
        dump($filters);

        $skills = $skillRepository->findByFilters($filters);

        return $this->render('skill/index.html.twig', [
            'skills' => $skills,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/new', name: 'add_skill')]
    public function addSkill(Request $request, EntityManagerInterface $entityManager): Response
    {
        $skill = new Skill();

        $form = $this->createForm(SkillType::class, $skill);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($skill);
            $entityManager->flush();

            $this->addFlash('success', 'Compétence ajoutée avec succès.');

            return $this->redirectToRoute('app_skill');
        }

        return $this->render('skill/new_skill.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'delete_skill')]
    public function deleteSkill(Skill $skill, EntityManagerInterface $entityManager): Response
    {

        dump("1");
        $wanted = $skill->getOffersAsWantedSkill();
        dump("2");
        $offered = $skill->getOffersAsSkillOffered();

        $requests = [];
        foreach ($wanted as $offer) {
            foreach ($offer->getRequests() as $request) {
                $requests[] = $request;
            }
        }
        foreach ($offered as $offer) {
            foreach ($offer->getRequests() as $request) {
                $requests[] = $request;
            }
        }
        dump("3");
        foreach ($requests as $request) {
            $entityManager->remove($request);
        }

        dump("4");

        foreach ($wanted as $offer) {
            $entityManager->remove($offer);
        }
        dump("5");
        foreach ($offered as $offer) {
            $entityManager->remove($offer);
        }


        /*foreach ($$offered as $offer) {
            foreach ($offer->getRequests() as $request) {
                // Informer les utilisateurs ayant envoyé une demande pour cette offre
                $notificationService->createNotification(
                    $request->getRequester(),
                    "L'offre '{$offer->getTitle()}' a été supprimée."
                );
            }

            // Informer le propriétaire de l'offre de la suppression
            $notificationService->createNotification(
                $offer->getOwner(),
                "Votre offre '{$offer->getTitle()}' a été supprimée car la compétence associée a été retirée."
            );
        }*/

        $entityManager->remove($skill);
        $entityManager->flush();

        $this->addFlash('success', 'Compétence supprimée avec succès, ainsi que les offres et demandes associées.');

        return $this->redirectToRoute('app_skill');
        // retourner toutes les offres qui ont cette compétence
        // retourner les requetes qui on les offres qui ont cette compétence
        // supprimer les offres et requetes. 
        // créer une notification pour informer que l'offre x a été supprimée ( a la fois aux requesters et aux receivers)
        //$entityManager->remove($skill);
        //$entityManager->flush();

        // $this->addFlash('success', 'Compétence supprimée avec succès.');

        // return $this->redirectToRoute('app_skills');
    }


    #[Route('/{id}/reject', name: 'reject_skill')]
    public function rejectSkill(Skill $skill, EntityManagerInterface $entityManager): Response
    {
        $skill->setStatus(SkillStatus::refusé);
        $entityManager->flush();

        $this->addFlash('success', 'Compétence rejetée.');

        return $this->redirectToRoute('app_skill');
    }

    #[Route('/{id}/validate', name: 'validate_skill')]
    public function validateSkill(Skill $skill, EntityManagerInterface $entityManager): Response
    {
        $skill->setStatus(SkillStatus::validé);
        $user = $skill->getProposedBy();
        if ($user) {
            $user->addSkill($skill);
            $entityManager->persist($user);
        }

        $entityManager->flush();

        $this->addFlash('success', 'Compétence validée avec succès.');

        return $this->redirectToRoute('app_skill');
    }
}
