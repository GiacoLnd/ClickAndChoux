<?php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class NewsletterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email :',
                
                'attr' => [
                    'class' => 'newsletter-input',
                    'placeholder' => 'Entrez votre e-mail ici'
                    ]
            ])
            ->add('subscribe', SubmitType::class, [
                'label' => 'S\'abonner',
                'attr' => ['class' => 'bubblegum-link']
            ]);
    }
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'attr' => ['class' => 'newsletter-form']
        ]);
    }
}
