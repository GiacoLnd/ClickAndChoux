<?php

namespace App\Form;

use App\Entity\Produit;
use App\Entity\Allergene;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AllergenType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('allergenes', EntityType::class, [
                'class' => Allergene::class,
                'choice_label' => 'nomAllergene',
                'label' => 'Filtrez par allergène',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
            ])

            ->add('filtrer', SubmitType::class, ['label' => 'Filtrez'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'allergenes' => null,
        ]);
    }
}
