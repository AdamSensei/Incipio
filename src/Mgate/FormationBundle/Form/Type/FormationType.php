<?php

/*
 * This file is part of the Incipio package.
 *
 * (c) Florian Lefevre
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mgate\FormationBundle\Form\Type;

use Mgate\FormationBundle\Entity\Formation;
use Mgate\PersonneBundle\Entity\PersonneRepository as PersonneRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FormationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('titre', TextType::class, array('label' => 'Titre de la formation', 'required' => false))
                ->add('description', TextareaType::class, array('label' => 'Description de la Formation', 'required' => true, 'attr' => array('cols' => '100%', 'rows' => 5)))
                ->add('categorie', ChoiceType::class, array('multiple' => true, 'choices' => Formation::getCategoriesChoice(), 'label' => 'Catégorie', 'required' => false))
                ->add('dateDebut', DateTimeType::class, array('label' => 'Date de debut (d/MM/y - HH:mm:ss)', 'format' => 'd/MM/y - HH:mm:ss', 'required' => false, 'widget' => 'single_text'))
                ->add('dateFin', DateTimeType::class, array('label' => 'Date de fin (d/MM/y - HH:mm:ss)', 'format' => 'd/MM/y - HH:mm:ss', 'required' => false, 'widget' => 'single_text'))
                ->add('mandat', IntegerType::class)
                ->add('formateurs', CollectionType::class, array(
                    'type' => 'genemu_jqueryselect2_entity',
                    'options' => array('label' => 'Suiveur de projet',
                        'class' => 'Mgate\\PersonneBundle\\Entity\\Personne',
                        'property' => 'prenomNom',
                        'query_builder' => function (PersonneRepository $pr) {
                            return $pr->getMembreOnly();
                        },
                    'required' => false, ),
                    'allow_add' => true,
                    'allow_delete' => true,
                    'by_reference' => false,
                ))
                ->add('membresPresents', CollectionType::class, array(
                    'type' => 'genemu_jqueryselect2_entity',
                    'options' => array('label' => 'Suiveur de projet',
                        'class' => 'Mgate\\PersonneBundle\\Entity\\Personne',
                        'property' => 'prenomNom',
                        'query_builder' => function (PersonneRepository $pr) {
                            return $pr->getMembreOnly();
                        },
                        'required' => false, ),
                    'allow_add' => true,
                    'allow_delete' => true,
                    'by_reference' => false,
                ))
                ->add('docPath', TextType::class, array('label' => 'Lien vers des documents externes', 'required' => false))
        ;
    }

    public function getBlockPrefix()
    {
        return 'Mgate_suivibundle_formulairetype';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Mgate\FormationBundle\Entity\Formation',
        ));
    }
}
