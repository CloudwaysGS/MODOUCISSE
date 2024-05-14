<?php

namespace App\Form;

use App\Entity\Client;
use App\Entity\Dette;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

class DetteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('client',EntityType::class, array(
                'label' => false,
                'class' => Client::class,
                'placeholder' => 'Select client',
                'attr' => array('class' => 'form-control form-group'),
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->orderBy('c.nom', 'ASC'); // Tri par ordre alphabétique sur la colonne 'nom'
                },
            ))
            ->add('montant_dette', TextType::class, array(
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
            ->add('commentaire')

            ->add('Valider', SubmitType::class, array(
                'attr' =>array('class' => 'btn btn-primary form-group')
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Dette::class,
        ]);
    }
}
