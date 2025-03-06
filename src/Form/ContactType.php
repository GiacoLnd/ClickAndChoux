<?php

namespace App\Form;

use App\Entity\Contact;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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
            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contact::class,
        ]);
    }
}
