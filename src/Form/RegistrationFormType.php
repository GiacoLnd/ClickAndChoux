<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Validator\Constraints\PasswordStrength;
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('nickName', TextType::class, [
                'label' => 'Pseudo :',
                'label_attr' => ['class' => 'security-text'],
                'attr' => [
                    'class' => 'flex-column-center',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez renseigner un pseudo',
                    ]),
                        new Length([
                            'min' => 3,
                            'minMessage' => 'Votre pseudo doit comporter au moins {{ limit }} caractères',
                            'max' => 20,
                            'maxMessage' => 'Votre pseudo ne doit pas comporter plus de {{ limit }} caractères',
                        ]),
                ],
            ])      
            ->add('email', EmailType::class, [
                'label' => 'Email :',
                'label_attr' => ['class' => 'security-text'],
                'required' => true,
                'attr' => [
                    'class' => 'flex-column-center',
                    'autocomplete' => 'email',
                ],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'Vous devez accepter les conditions d\'utilisation',
                    ]),
                ],
                'label_attr' => ['class' => 'security-text'],
                'attr' => [
                    'class' => 'flex-column-center',
                    'autocomplete' => 'email',
                ],
                
            ])
            ->add('plainPassword', RepeatedType::class, [
                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
                'type' => PasswordType::class,
                'required' => true,
                'invalid_message' => 'Les mots de passes ne correspondent pas',
                'error_bubbling' => true,
                'first_options' => [
                    'label' => 'Mot de passe :',
                    'label_attr' => ['class' => 'security-text'],
                    'attr' => [
                        'autocomplete' => 'new-password',
                        'class' => 'flex-column-center',
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirmez le mot de passe :',
                    'label_attr' => ['class' => 'security-text'],
                    'attr' => [
                        'autocomplete' => 'new-password',
                        'class' => 'flex-column-center',
                    ],
                ],
                'attr' => [
                    'autocomplete' => 'new-password',
                    'class' => 'flex-column-center',
                    ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un mot de passe',
                    ]),
                    new Length([
                        'min' => 12,
                        'minMessage' => 'Votre mot de passe doit contenir au moins {{ limit }} caractères',
                        'max' => 4096,
                    ]),
                    new NotCompromisedPassword(), // Vérifie dans haveibeenpwnd si le mot de passe en question est détécté dans une data breach
                    new PasswordStrength(([
                        'minScore' => PasswordStrength::STRENGTH_MEDIUM,  // Evite les suites logiques 
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
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
