<?php

namespace App\Form;

use App\Entity\PayoffSupplier;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

class PayoffSupplierType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fournisseur', EntityType::class, [
                'class' => 'App\Entity\Fournisseur',
                'label' => false,
                'placeholder' => '-- Choisir un fournisseur --',
                'required' => true,
                'multiple' => false,
            ])
            ->add('montant', TextType::class, array(
                'label' => false,
                'attr' => array(
                    'class' => 'form-control form-group',
                    'placeholder' => 'montant avance',
                ),
                'required' => true,
                'constraints' => array(
                    new NotBlank(),
                    new Type('numeric')
                )
            ))
            ->add('payer', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PayoffSupplier::class,
        ]);
    }
}
