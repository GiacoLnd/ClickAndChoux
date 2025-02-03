<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('nickName', TextType::class, [
                'label' => 'Pseudo :',
                'label_attr' => ['class' => 'security-text'],
                'attr' => [
                    'class' => 'form-control',
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
                'attr' => [
                    'class' => 'form-control',
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
                    'class' => 'form-group',
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
                        'class' => 'form-control',
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirmez le mot de passe :',
                    'label_attr' => ['class' => 'security-text'],
                    'attr' => [
                        'autocomplete' => 'new-password',
                        'class' => 'form-control',
                    ],
                ],
                'attr' => [
                    'autocomplete' => 'new-password',
                    'class' => 'form-control',
                    ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un mot de passe',
                    ]),
                    new Length([
                        'min' => 12,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        'max' => 4096,
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
