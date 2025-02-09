<?php

namespace App\Form;

use App\Entity\Offer;
use App\Entity\Skill;
use App\Entity\User;
use App\Enum\OfferStatus;
use Doctrine\DBAL\Types\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OfferType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                'label' => 'Titre de l\'offre'
            ])
            ->add('description', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                'label' => 'Description'
            ])
            ->add('skillOffered', EntityType::class, [
                'class' => Skill::class,
                'choice_label' => 'name',
                'label' => 'Compétence offerte'
            ])
            ->add('skillWanted', EntityType::class, [
                'class' => Skill::class,
                'choice_label' => 'name',
                'label' => 'Compétence recherchée'
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Disponible' => OfferStatus::Disponible,
                    'Réservé' => OfferStatus::Reserve,
                    'Terminé' => OfferStatus::Termine,
                    'Annulé' => OfferStatus::Annule,
                ],
                'label' => 'Statut de l\'offre',
                'expanded' => false,
                'multiple' => false,
                'choice_label' => function ($choice, $key, $value) {
                    return $key;
                },
            ])
            ->add('negotiable', CheckboxType::class, [
                'label' => 'Négociable ?',
                'required' => false,
            ]);


    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Offer::class,
        ]);
    }
}
