<?php

namespace App\Form;

use App\Entity\Client;
use App\Entity\Facture2;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

class Facture2Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $libelle = "";
        $builder
            ->add('client', EntityType::class, [
                'class' => Client::class,
                'label' => false,
                'attr' => [
                    'class' => 'form-control form-group',
                ],
                'placeholder' => 'Nom du client',
                'required' => false,
            ])

            ->add('quantite', NumberType::class, [
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

            ->add('prixUnit',TextType::class,array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control form-group',
                    'placeholder' => 'prix unitaire'                ),
            ))

            ->add('produit', null, [
                'label' => 'Liste grossistes',
                'attr' => [
                    'class' => 'form-control',
                    'style' => 'height: 5rem;', // ajout de la hauteur personnalisée
                    'onmouseover' => 'this.style.height = "20rem";', // hauteur augmentée lors du survol de la souris
                    'onmouseout' => 'this.style.height = "5rem";', // hauteur rétablie lorsque la souris quitte le champ
                ],
                'required' => false,
                'query_builder' => function(EntityRepository $er) use ($libelle) {
                    return $er->createQueryBuilder('p')
                        ->where('p.libelle LIKE :libelle')
                        ->setParameter('libelle', '%'.$libelle.'%')
                        ->orderBy('p.libelle', 'ASC');
                },
            ])

            ->add('details', null, [
                'label' => 'Liste détails',
                'attr' => [
                    'class' => 'form-control',
                    'style' => 'height: 5rem;', // ajout de la hauteur personnalisée
                    'onmouseover' => 'this.style.height = "10rem";', // hauteur augmentée lors du survol de la souris
                    'onmouseout' => 'this.style.height = "5rem";', // hauteur rétablie lorsque la souris quitte le champ
                ],
                'required' => false,
                'query_builder' => function(EntityRepository $er) use ($libelle) {
                    return $er->createQueryBuilder('d')
                        ->where('d.libelle LIKE :libelle')
                        ->setParameter('libelle', '%'.$libelle.'%')
                        ->orderBy('d.libelle', 'ASC');
                },
            ])

            ->add('Ajouter', SubmitType::class, array(
                'attr' =>array('class' => 'btn btn-primary form-group')
            ))

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Facture2::class,
        ]);
    }
}
