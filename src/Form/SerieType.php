<?php

namespace App\Form;

use App\Entity\Genre;
use App\Entity\Serie;
use Doctrine\DBAL\Types\FloatType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class SerieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de la série',
                'required' => true,
            ])
            ->add('overview', TextAreaType::class, [
                'label' => 'Synopsis'
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'En cours' => 'returning',
                    'Abandonnée' => 'cancelled',
                    'Terminée' => 'ended',
                ],
                'placeholder' => '-- Choisir un statut --'
            ])
            ->add('country', CountryType::class, [
                'label' => 'Pays de production',
                'placeholder' => '-- Choisir un pays --'
            ])
            ->add('vote', TextType::class, [
                'label' => 'Note'
            ])
            ->add('popularity', NumberType::class, [
                'label' => 'Popularité'
            ])
            ->add('firstAirDate', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Première diffusion'
            ])
            ->add('lastAirDate', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Dernière diffusion'
            ])
            ->add('backdrop_file', FileType::class, [
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2000k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/jpg',
                            'image/png',
                            'image/webp',
                            'image/avif'
                        ],
                        'mimeTypesMessage' => "Le format de l'image n'est pas valide : .jpg, .jpeg, .png ou .webp",
                        'maxSizeMessage' => 'Max file size 2 MB',
                    ])
                ]
            ])
            ->add('poster_file', FileType::class, [
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2000k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/jpg',
                            'image/png',
                            'image/webp'
                        ],
                        'mimeTypesMessage' => "Le format de l'image n'est pas valide : .jpg, .jpeg, .png ou .webp",
                        'maxSizeMessage' => 'Max file size 2 MB',
                    ])
                ]
            ])
            ->add('genres', EntityType::class, [
                'class' => Genre::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => false, // mettre true pour des checkboxes
                'label' => 'Genres',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Serie::class,
        ]);
    }
}
