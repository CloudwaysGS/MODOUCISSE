<?php

namespace App\Form;

use App\Entity\Client;
use App\Entity\DetteFournisseur;
use App\Entity\Fournisseur;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

class DetteFournisseurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fournisseur',EntityType::class, array(
                'label' => false,
                'class' => Fournisseur::class,
                'placeholder' => '-- Choisir un fournisseur --',
                'attr' => array('class' => 'form-control form-group')
            ))
            ->add('montantDette', TextType::class, array(
                'label' => false,
                'attr' => [
                    'class' => 'form-control form-group',
                    'placeholder' => 'Montant de la dette',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le montant de la dette ne peut pas être vide.']),
                    new Type([
                        'type' => 'numeric',
                        'message' => 'Le montant de la dette doit être un nombre.'
                    ])
                ]
            ))

            ->add('Valider', SubmitType::class, array(
                'attr' =>array('class' => 'btn btn-primary form-group')
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DetteFournisseur::class,
        ]);
    }
}
