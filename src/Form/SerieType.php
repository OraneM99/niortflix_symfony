<?php

namespace App\Form;

use App\Entity\Genre;
use App\Entity\Serie;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SerieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de la série',
                'attr'  => ['maxlength' => 150, 'placeholder' => 'Ex. The Office'],
                'help'  => '1 à 150 caractères',
            ])
            ->add('overview', TextareaType::class, [
                'label' => 'Synopsis',
                'required' => false,
                'attr'  => ['rows' => 5, 'maxlength' => 800, 'data-counter' => 'true'],
                'help'  => '800 caractères max.',
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'En cours'    => 'returning',
                    'Abandonnée'  => 'cancelled',
                    'Terminée'    => 'ended',
                ],
                'placeholder' => '-- Choisir un statut --',
            ])
            ->add('country', CountryType::class, [
                'label' => 'Pays de production',
                'preferred_choices' => ['FR','US','GB','CA','JP'],
                'placeholder' => '-- Choisir un pays --',
            ])
            ->add('vote', TextType::class, [
                'label' => 'Note',
                'attr'  => [
                    'inputmode' => 'decimal',
                    'placeholder' => '0–10',
                ],
                'help'  => 'Entre 0 et 10 (ex: 8,5)',
            ])
            ->add('popularity', TextType::class, [
                'label' => 'Popularité',
                'attr'  => [
                    'inputmode' => 'decimal',
                    'placeholder' => 'ex: 1234,5',
                ],
            ])

            ->add('firstAirDate', DateType::class, [
                'widget' => 'single_text',
                'label'  => 'Première diffusion',
                'html5'  => true,
            ])
            ->add('lastAirDate', DateType::class, [
                'widget'   => 'single_text',
                'label'    => 'Dernière diffusion',
                'required' => false,
                'help'     => 'Laissez vide si la série est en cours',
            ])
            ->add('backdrop_file', FileType::class, [
                'label'    => 'Backdrop',
                'required' => false,
                'mapped'   => false,
                'help'     => 'JPG/PNG/WEBP/AVIF • 2 Mo max',
            ])
            ->add('poster_file', FileType::class, [
                'label'    => 'Poster',
                'required' => false,
                'mapped'   => false,
                'help'     => 'JPG/PNG/WEBP • 2 Mo max',
            ])
            ->add('genres', EntityType::class, [
                'class'        => Genre::class,
                'choice_label' => 'name',
                'multiple'     => true,
                'label'        => 'Genres',
                'attr'         => ['data-tags' => 'true'],
            ])
            ->add('streamingLinks', CollectionType::class, [
                'entry_type'     => StreamingLinkType::class,
                'allow_add'      => true,
                'allow_delete'   => true,
                'by_reference'   => false,
                'prototype'      => true,
                'required'       => false,
                'label'          => false,
                'error_bubbling' => false,
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            if (!is_array($data)) return;

            foreach (['vote', 'popularity'] as $field) {
                if (isset($data[$field]) && is_string($data[$field])) {
                    $clean = preg_replace('/[^0-9,.\-]/', '', $data[$field] ?? '');
                    $data[$field] = str_replace(',', '.', $clean);
                }
            }

            $event->setData($data);
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            /** @var Serie $serie */
            $serie = $event->getData();
            if (!$serie) return;

            $v = $serie->getVote();
            if (is_string($v)) { $serie->setVote((float) $v); }

            $p = $serie->getPopularity();
            if (is_string($p)) { $serie->setPopularity((float) $p); }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Serie::class]);
    }
}
