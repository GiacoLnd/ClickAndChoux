<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class LivraisonType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('adresseLivraison', TextType::class, [
                'label' => 'Adresse postale',
                'label_attr' => ['class' => 'security-text'],
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'L\'adresse est obligatoire.']),
                ],
                'attr' => [
                    'class' => 'flex-column-center',
                ],
            ])
            ->add('codePostalLivraison', TextType::class, [
                'label' => 'Code postal',
                'label_attr' => ['class' => 'security-text'],
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le code postal est obligatoire.']),
                    new Assert\Regex([
                        'pattern' => '/^\d{5}$/',
                        'message' => 'Le code postal doit contenir exactement 5 chiffres.',
                    ]),
                ],
                'attr' => [
                    'class' => 'flex-column-center',
                ],
            ])
            ->add('villeLivraison', TextType::class, [
                'label' => 'Ville',
                'label_attr' => ['class' => 'security-text'],
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La ville est obligatoire.']),
                ],
                'attr' => [
                    'class' => 'flex-column-center',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'allow_extra_fields' => true,
        ]);
    }
}
