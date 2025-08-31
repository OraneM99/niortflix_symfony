<?php

namespace App\Form;

use App\Entity\Anime;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class AnimeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de l\'anime',
            ])
            ->add('overview', TextareaType::class, [
                'label' => 'Résumé',
                'required' => false,
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'En cours' => 'returning',
                    'Terminée' => 'ended',
                    'Annulée' => 'cancelled',
                ],
                'label' => 'Statut',
                'placeholder' => 'Choisir un statut',
                'required' => false,
            ])
            ->add('vote', NumberType::class, [
                'label' => 'Note',
                'required' => false,
                'scale' => 1,
            ])
            ->add('popularity', NumberType::class, [
                'label' => 'Popularité',
                'required' => false,
            ])
            ->add('firstAirDate', DateType::class, [
                'label' => 'Date de première diffusion',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('lastAirDate', DateType::class, [
                'label' => 'Date de dernière diffusion',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('country', CountryType::class, [
                'label' => 'Pays de production',
                'placeholder' => '-- Choisir un pays --'
            ])
            ->add('genres', ChoiceType::class, [
                'label' => 'Genres',
                'choices' => [
                    'Anime' => 'Anime',
                    'Dessin animé' => 'Dessin animé',
                    'Film d\'animation' => 'Film d\'animation',
                ],
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('backdrop_file', FileType::class, [
                'label' => 'Image de fond',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (jpeg, png, webp).',
                    ])
                ],
            ])
            ->add('poster_file', FileType::class, [
                'label' => 'Poster',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (jpeg, png, webp).',
                    ])
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Anime::class,
        ]);
    }
}
