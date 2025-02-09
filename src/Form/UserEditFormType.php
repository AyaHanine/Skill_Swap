<?php

namespace App\Form;

use App\Entity\Conversation;
use App\Entity\Skill;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserEditFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        dump($options);
        dump($skillsValidées = $options['skills']);

        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'constraints' => [new NotBlank()]
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'constraints' => [new NotBlank()]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [new NotBlank()]
            ])
            ->add('bio')
            ->add('skills', EntityType::class, [
                'class' => Skill::class,
                'choice_label' => 'name',
                'choices' => $this->getSkillsChoices($skillsValidées),
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('competence_autre', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                'label' => 'Autre compétence',
                'required' => false,
                'mapped' => false,
            ])
            ->add('submit', SubmitType::class, ['label' => 'Mettre à jour']);
    }

    private function getSkillsChoices($skillsValidées)
    {
        $choices = [];

        foreach ($skillsValidées as $skill) {
            $choices[$skill->getName()] = $skill;
        }
        return $choices;
    }
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'skills' => [],
        ]);
    }
}
