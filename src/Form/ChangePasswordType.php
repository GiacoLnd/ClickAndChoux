<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;

class ChangePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Champ pour l'ancien mot de passe
            ->add('oldPassword', PasswordType::class, [
                'label' => 'Ancien mot de passe',
                'label_attr' => ['class' => 'security-text'],
                'mapped' => false,
                'required' => true,
                'attr' => ['class' => 'form-group'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez entrer votre ancien mot de passe.']),
                    new UserPassword(['message' => 'L\'ancien mot de passe est incorrect.'])
                ],
            ])

            // Champ pour le nouveau mot de passe
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Les mots de passe doivent être identiques.',
                'required' => true,
                'first_options'  => [
                    'label' => 'Nouveau mot de passe',
                    'label_attr' => ['class' => 'security-text'],
                    'attr' => ['class' => 'form-group'],
                    'constraints' => [
                        new Assert\NotBlank(['message' => 'Le mot de passe ne peut pas être vide.']),
                        new Assert\Length([
                            'min' => 12,
                            'minMessage' => 'Le mot de passe doit contenir au moins 12 caractères.',
                        ]),
                        new Regex([
                            'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&,;-_])[A-Za-z\d@$!%*?&,;-_]{12,}$/', //.* 0 ou plusieurs caractères sauf retour à la ligne peut importe le placement dans le password -  [a-z] au moins 1 minuscule - [A-Z] au moins 1 majuscule - \d au moins un chiffre - [@$!%*?&,;-_] au moins un symbole parmis ceux proposés - {12,} au moins 12 caractères
                            'message' => 'Votre mot de passe doit contenir au moins 12 caractères, une lettre majuscule, une lettre minuscule, un chiffre et un caractère spécial',
                        ]),
                    ]
                ],
                'second_options' => [
                    'label' => 'Confirmez le mot de passe',
                    'label_attr' => ['class' => 'security-text'],
                    'attr' => ['class' => 'form-group']
                ],
                'mapped' => false
            ])

            ->add('save', SubmitType::class, [
                'label' => 'Changer le mot de passe',
                'attr' => ['class' => 'add-to-cart-button']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
