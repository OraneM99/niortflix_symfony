<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;

class StreamingLinkType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('platform', ChoiceType::class, [
                'label' => 'Plateforme',
                'choices' => [
                    'Netflix' => 'netflix',
                    'Prime Video' => 'prime',
                    'Disney+' => 'disney',
                    'Apple TV+' => 'apple',
                    'Crunchyroll' => 'crunchyroll',
                ],
            ])
            ->add('url', UrlType::class, [
                'label' => 'Lien',
                'attr' => ['placeholder' => 'https://...'],
            ]);
    }
}
