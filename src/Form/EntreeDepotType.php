<?php

namespace App\Form;

use App\Entity\Depot;
use App\Entity\EntreeDepot;
use App\Entity\Produit;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

class EntreeDepotType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $libelle = "";
        $builder
            ->add('depot',EntityType::class, array(
                'class' => Depot::class,
                'label' => false,
                'attr' => array(
                    'class' => 'form-control form-group',
                ),
                'placeholder' => 'Choisissez un produit',
                'required' => false,
                'query_builder' => function(EntityRepository $er) use ($libelle) {
                    return $er->createQueryBuilder('d')
                        ->where('d.libelle LIKE :libelle')
                        ->setParameter('libelle', '%'.$libelle.'%')
                        ->orderBy('d.libelle', 'ASC');
                },
            ))
            ->add('qtEntree', NumberType::class, [
                'label' => false,
                'attr' => [
                    'class' => 'form-control form-group',
                    'placeholder' => 'Quantité',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez spécifier une quantité.',
                    ]),
                    new Type([
                        'type' => 'float',
                        'message' => 'La quantité doit être un nombre.',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EntreeDepot::class,
        ]);
    }
}
