<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Validator\Constraints\PasswordStrength;
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;

class ChangePasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'options' => [
                    'attr' => [
                        'autocomplete' => 'new-password',
                    ],
                ],
                'first_options' => [
                    'constraints' => [
                        new NotBlank([
                            'message' => 'Entrez un mot de passe',
                        ]),
                        new Length([
                            'min' => 12,
                            'minMessage' => 'Your password should be at least {{ limit }} characters',
                            // max length allowed by Symfony for security reasons
                            'max' => 4096,
                        ]),
                        new NotCompromisedPassword(),
                        new PasswordStrength(([
                            'minScore' => PasswordStrength::STRENGTH_MEDIUM, 
                            // contrainte calculant l'entropie (imprevisibilité) du mot de passe - calcul :
                            // $pool = $lower + $upper + $digit + $symbol + $control + $other;
                            // $entropy = $chars * log($pool, 2) + ($length - $chars) * log($chars, 2);
                            'message' =>    'Votre mot de passe doit être plus complexe.'
                        ])),
                        new Regex([
                            'pattern' =>    '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&,;-_])[A-Za-z\d@$!%*?&,;-_]{12,}$/', //.* 0 ou plusieurs caractères sauf retour à la ligne peut importe le placement dans le password -  [a-z] au moins 1 minuscule - [A-Z] au moins 1 majuscule - \d au moins un chiffre - [@$!%*?&,;-_] au moins un symbole parmis ceux proposés - {12,} au moins 12 caractères
                            'message' =>    'Votre mot de passe doit contenir au moins 12 caractères, une lettre majuscule, une lettre minuscule, un chiffre et un caractère spécial',
                        ]),
                    ],
                    'label' => 'Nouveau mot de passe',
                ],
                'second_options' => [
                    'label' => 'Confirmez le mot de passe',
                ],
                'invalid_message' => 'Les mots de passes doivent correspondre',
                // Instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
