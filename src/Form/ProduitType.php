<?php

namespace App\Form;

use App\Entity\Produit;
use App\Entity\Categorie;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ProduitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomProduit', TextType::class, [
                'label' => 'Nom du produit'
            ])
            ->add('prixHt', MoneyType::class, [
                'label' => 'Prix HT',
                'currency' => 'EUR'
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description'
            ])
            ->add('allergene', TextType::class, [
                'label' => 'Allergènes'
            ])
            ->add('TVA', NumberType::class, [
                'label' => 'TVA (%)'
            ])
            ->add('image', FileType::class, [
                'label' => 'Image du produit',
                'mapped' => false, 
                'required' => true, 
                'constraints' => [
                    new Assert\NotBlank(['message' => "Veuillez sélectionner une image."]),
                    new Assert\Image([
                        'mimeTypes' => ["image/jpeg", "image/png", "image/webp"],
                        'mimeTypesMessage' => "Seuls les fichiers JPG, PNG ou WebP sont autorisés."
                    ])
                ]
            ])
            
            ->add('categorie', EntityType::class, [
                'class' => Categorie::class,
                'choice_label' => 'nomCategorie',
                'label' => 'Catégorie'
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Ajouter le produit',
                'attr' => ['class' => 'btn btn-success']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Produit::class,
        ]);
    }
}
