<?php

namespace App\Form;

use App\Entity\Contributor;
use App\Entity\Serie;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContributorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom et Prénom :',
                'attr'  => ['maxlength' => 50]
            ])
            ->add('role', ChoiceType::class, [
                'label' => 'Role :',
                'choices' => [
                    'Acteur' => 'Acteur',
                    'Producteur' => 'Producteur',
                    ''
                ]
            ])
            ->add('birthDate', DateType::class, [
                'widget' => 'single_text',
                'label'  => 'Date de naissance :',
                'html5'  => true,
            ])
            ->add('country', CountryType::class, [
                'label' => 'Pays de production',
                'preferred_choices' => ['FR','US','GB','CA','JP', 'KR'],
                'placeholder' => '-- Choisir un pays --',
            ])
            ->add('biography', TextareaType::class, [
                'label' => 'Synopsis',
                'required' => false,
                'attr'  => ['rows' => 5, 'maxlength' => 1500, 'data-counter' => 'true'],
                'help'  => '1500 caractères max.',
            ])
            ->add('photo', FileType::class, [
                'label'    => 'Photo',
                'required' => false,
                'mapped'   => false,
                'help'     => 'Formats acceptés : JPG/PNG, 2 Mo max',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contributor::class,
        ]);
    }
}
