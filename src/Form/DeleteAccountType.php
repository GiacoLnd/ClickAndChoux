<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver as SymfonyOptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class DeleteAccountType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('password', PasswordType::class, [
                'label' => false,
                'required' => true,
                'attr' => [
                    'placeholder' => 'Confirmez votre mot de passe',
                    'style' => 'text-align: center;',
                    'class' => 'form-control',],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Supprimer mon compte',
                'attr' => [ 'class' => 'delete-link' ],
            ]);
    }

    public function configureOptions(SymfonyOptionsResolver $resolver)
    {
        $resolver->setDefaults(['data_class' => User::class,]);
    }
    
}
