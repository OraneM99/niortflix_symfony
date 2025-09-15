<?php
// src/Form/StreamingLinkType.php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;

class StreamingLinkType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('provider', ChoiceType::class, [
                'label' => 'Plateforme',
                'required' => false,
                'placeholder' => '-- Choisir --',
                'choices' => [
                    'ADN' => 'ADN',
                    'AppleTV' => 'AppleTV',
                    'Canal+' => 'Canal+',
                    'Crunchyroll' => 'Crunchyroll',
                    'Disney+' => 'Disney+',
                    'France TV' => 'France TV',
                    'HBO' => 'HBO',
                    'Netflix' => 'Netflix',
                    'M6+' => 'M6+',
                    'Molotov' => 'Molotov',
                    'Paramount' => 'Paramount',
                    'Prime Video' => 'Prime Video',
                    'TF1' => 'TF1',
                    'Viki' => 'Viki',
                    'Warner TV' => 'Warner TV',
                    'Youtube' => 'Youtube'
                ],
            ])
            ->add('url', UrlType::class, [
                'label' => 'URL de lecture',
                'required' => false,
                'attr' => ['placeholder' => 'https://...'],
            ])
            ->add('enabled', CheckboxType::class, [
                'label' => 'Activer ce lien',
                'required' => false,
            ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();

            $enabled  = !empty($data['enabled']);
            $provider = $data['provider'] ?? null;
            $url      = $data['url'] ?? null;

            if ($enabled) {
                if (!$provider) {
                    $form->get('provider')->addError(new FormError('Choisis une plateforme.'));
                }
                if (!$url) {
                    $form->get('url')->addError(new FormError('Renseigne une URL.'));
                }
            }
        });
    }
}
