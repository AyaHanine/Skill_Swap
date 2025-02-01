<?php

namespace App\Form;

use App\Entity\Offer;
use App\Entity\Review;
use App\Entity\User;
use Doctrine\DBAL\Types\IntegerType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

class ReviewFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('rating', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, [
                'label' => 'Note (1 à 5)',
                'constraints' => [
                    new Range(['min' => 1, 'max' => 5, 'notInRangeMessage' => 'La note doit être entre 1 et 5.']),
                ],
                'attr' => ['min' => 1, 'max' => 5],
                'mapped' => false
            ])
            ->add('comment', TextareaType::class, [
                'label' => 'Commentaire',
                'attr' => ['rows' => 4],
                'mapped' => false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Review::class,
        ]);
    }
}
