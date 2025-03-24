<?php

namespace App\Form;

use App\Entity\Contact;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', TextType::class, [
                'label' => 'Email',
                'label_attr' => ['class' => 'security-text'],
                'required' => false,
                'attr' => [
                    'class' => 'flex-column-center',
                ],
            ])
            ->add('telephone', TextType::class, [
                'label' => 'Téléphone',
                'label_attr' => ['class' => 'security-text'],
                'required' => false,
                'attr' => [
                    'class' => 'flex-column-center',
                ],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Message',
                'label_attr' => ['class' => 'security-text'],
                'required' => false,
                'constraints' => [
                    new NotBlank(['message' => 'Le message est obligatoire']),
                ],
                'attr' => [
                    'class' => 'flex-column-center',
                ],
            ])
            ->add('consent', CheckboxType::class, [
                'label' => 'Je consens à ce que mes informations soient collectées et uniquement utilisées pour répondre à ma requête',
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'Le consentement est obligatoire pour toute demande de contact',
                    ]),
                ],
                'label_attr' => ['class' => 'security-text'],
                'attr' => [
                    'class' => 'flex-column-center',
                ],   
            ]);
        
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contact::class,
        ]);
    }
}
