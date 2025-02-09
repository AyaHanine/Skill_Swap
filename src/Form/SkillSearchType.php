<?php

namespace App\Form;

use App\Entity\Skill;
use App\Entity\User;
use App\Enum\SkillStatus;
use App\Repository\SkillRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SkillSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de la compétence',
                'required' => false,
                'attr' => [
                    'placeholder' => $options['is_search'] ? 'Rechercher une compétence...' : 'Nom de la compétence',
                    'class' => 'w-full p-2 border border-gray-300 rounded-lg focus:ring focus:ring-blue-300',
                ],
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Validé' => SkillStatus::validé,
                    'En Attente' => SkillStatus::enAttente,
                    'Refusé' => SkillStatus::refusé,
                ],
                'required' => !$options['is_search'],
                'placeholder' => $options['is_search'] ? 'Tous les statuts' : null,
                'label' => 'Statut',
                'attr' => [
                    'class' => 'w-full p-2 border border-gray-300 rounded-lg focus:ring focus:ring-blue-300',
                ],
            ]);

        if ($options['is_search']) {
            $builder->add('submit', SubmitType::class, [
                'label' => '🔍 Rechercher',
                'attr' => ['class' => 'bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200'],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'is_search' => false,
            'csrf_protection' => false,
            'data_class' => null,
        ]);
    }
}