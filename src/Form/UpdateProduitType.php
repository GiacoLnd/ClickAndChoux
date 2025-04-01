<?php

namespace App\Form;

use App\Entity\Produit;
use App\Entity\Allergene;
use App\Entity\Categorie;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class UpdateProduitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('isActive', ChoiceType::class, [
            'label' => 'Disponible en stock ?',
            'choices' => [
                'Oui' => true,
                'Non' => false,
            ],
            'expanded' => true, 
            'multiple' => false, 
            'data' => true,
            'attr' => ['class' => 'flex-row-center'],
        ])
        ->add('nomProduit', TextType::class, [
            'label' => 'Nom du produit',
            'label_attr' => ['class' => 'security-text'],
            'attr' => ['class' => 'flex-column-center'],
        ])
        ->add('prixHt', MoneyType::class, [
            'label' => 'Prix hors taxes €',
            'label_attr' => ['class' => 'security-text'],
            'currency' => false,
            'attr' => ['class' => 'flex-column-center'],
        ])
        ->add('description', TextareaType::class, [
            'label' => 'Description',
            'label_attr' => ['class' => 'security-text'],
            'attr' => ['class' => 'flex-column-center'],
        ])
        ->add('TVA', NumberType::class, [
            'label' => 'TVA (%)',
            'label_attr' => ['class' => 'security-text'],
            'attr' => ['class' => 'flex-column-center'],
            'data' => '5.5'
        ])
        ->add('image', FileType::class, [
            'label' => 'Image du produit',
            'label_attr' => ['class' => 'security-text'],
            'mapped' => false, 
            'required' => false,
            'attr' => ['class' => 'flex-column-center'],
            'constraints' => [
                new Assert\Image([
                    'maxSize' => '2M',
                    'maxSizeMessage' => 'La taille du fichier ne doit pas dépasser 2 Mo',
                    'mimeTypes' => ["image/jpeg", "image/png", "image/webp"],
                    'mimeTypesMessage' => "Seuls les fichiers JPG, PNG ou WebP sont autorisés."
                ])
            ]
        ])
        
        ->add('categorie', EntityType::class, [
            'class' => Categorie::class,
            'choice_label' => 'nomCategorie',
            'label' => 'Catégorie',
            'label_attr' => ['class' => 'security-text'],
            'attr' => ['class' => 'flex-column-center']
        ])

        ->add('allergenes', EntityType::class, [
            'class' => Allergene::class,
            'choice_label' => 'nomAllergene',  
            'label' => 'Sélectionner les allergènes',
            'label_attr' => ['class' => 'security-text'],
            'multiple' => true,
            'expanded' => false,
            'required' => false,
            'attr' => [
                'class' => 'flex-column-center',
            ],
        ])
        
        ->add('newAllergenes', CollectionType::class, [
            'entry_type' => AllergenType::class,  
            'allow_add' => true, 
            'allow_delete' => true,
            'mapped' => false,
            'prototype' => true,
            'by_reference' => false,
            'label' => false,
            'required' => false,
            'entry_options' => [
                'attr' => ['class' => 'flex-column-center'],
                'label' => false,
            ],
            'attr' => [
                'class' => 'flex-column-center',
            ],
        ])
        
        ->add('submit', SubmitType::class, [
            'label'=> 'Valider les modifications',
            'attr' => ['class'=> 'bubblegum-link'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Produit::class,
        ]);
    }
}
