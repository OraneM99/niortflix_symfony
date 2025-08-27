<?php

namespace App\Form;

use App\Entity\Serie;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SerieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de la série',
                'required' => true,
            ])
            ->add('overview')
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'En cours' => 'returning',
                    'Abandonnée' => 'cancelled',
                    'Terminée' => 'ended',
                ],
                'placeholder' => '-- Choisir un statut --'
            ])
            ->add('vote')
            ->add('popularity')
            ->add('firstAirDate', DateType::class, [
                'widget' => 'single_text',
            ])
            ->add('lastAirDate')
            ->add('backdrop')
            ->add('poster')
            ->add('submit', SubmitType::class, [
                'label' => 'Envoyer',
                'attr' => [
                    'class' => 'btn btn-primary',
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Serie::class,
        ]);
    }
}
