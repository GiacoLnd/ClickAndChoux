<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Produit;
use App\Entity\Commentaire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class UpdateCommentaireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('contenu', TextareaType::class)
        ->add('modifier', SubmitType::class, [
            'label' => 'Sauvegarder',
            'attr' => ['class' => 'bubblegum-link']
        ]);

        $builder->setAttribute('attr', ['id' => 'editCommentForm', 'class' => 'flex-column-center']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Commentaire::class,

        ]);
    }
}
