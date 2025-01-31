<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class EditProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Adresse email',
                'label_attr' => ['class' => 'security-text'],
                'attr' => ['class' => 'form-group']
            ])
            ->add('nickName', TextType::class, [
                'label' => 'Pseudo',
                'label_attr' => ['class' => 'security-text'],
                'attr' => ['class' => 'form-group']
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Mettre à jour',
                'attr' => ['class' => 'add-to-cart-button']
            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
