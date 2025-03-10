<?php
// src/Form/AllergenType.php
namespace App\Form;

use App\Entity\Allergene;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AllergenType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('allergenes', EntityType::class, [
                'class' => Allergene::class,
                'choice_label' => 'nomAllergene',
                'multiple' => true,
                'expanded' => true,
                'choices' => $options['allergenes'],
                'label' => 'Filtrage par allergène(s)',
            ])
            ->add('filtrer', SubmitType::class, [
                'label' => 'Filtrer',
                'attr' => ['class' => 'bubblegum-link'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'allergenes' => [],
            'data_class' => null,
        ]);
    }
}