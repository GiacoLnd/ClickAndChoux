<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class LivraisonType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomLivraison', TextType::class, [
                'label' => 'Nom (livraison)',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nom est obligatoire.']),
                ],
                'attr' => [
                    'class' => 'flex-column-center',
                ]
            ])
            ->add('prenomLivraison', TextType::class, [
                'label' => 'Prénom (livraison)',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le prénom est obligatoire.']),
                ],
                'attr' => [
                    'class' => 'flex-column-center',
                ]
            ])
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
            ])
            ->add('nomFacturation', TextType::class, [
                'label' => 'Nom (facturation)',
                'required' => true,
                'attr' => [
                    'class' => 'flex-column-center',
                ]
            ]) 
            ->add('prenomFacturation', TextType::class, [
                'label' => 'Prénom (facturation)',
                'required' => true,
                'attr' => [
                    'class' => 'flex-column-center',
                ]
            ])
            ->add('adresseFacturation', TextType::class, [
                'label' => 'Adresse postale',
                'label_attr' => ['class' => 'security-text'],
                'required' => true,
                'attr' => [
                    'class' => 'flex-column-center',
                ],
            ])
            ->add('codePostalFacturation', TextType::class, [
                'label' => 'Code postal',
                'label_attr' => ['class' => 'security-text'],
                'required' => true,
                'constraints' => [
                    new Regex([
                        'pattern' => '/^\d{5}$/',
                        'message' => 'Le code postal doit contenir exactement 5 chiffres.',
                    ]),
                ],
                'attr' => [
                    'class' => 'flex-column-center',
                ],
            ])
            ->add('villeFacturation', TextType::class, [
                'label' => 'Ville',
                'label_attr' => ['class' => 'security-text'],
                'required' => true,
                'attr' => [
                    'class' => 'flex-column-center',
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Valider et payer',
                'attr' => ['class' => 'bubblegum-link'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'allow_extra_fields' => true,
            'csrf_protection' => true,
        ]);
    }
}
