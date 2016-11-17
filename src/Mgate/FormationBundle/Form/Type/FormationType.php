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
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Mgate\PersonneBundle\Entity\PersonneRepository as PersonneRepository;

class FormationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('titre', 'text', array('label' => 'Titre de la formation', 'required' => false))
                ->add('description', 'textarea', array('label' => 'Description de la Formation', 'required' => true, 'attr' => array('cols' => '100%', 'rows' => 5)))
                ->add('categorie', 'choice', array('multiple' => true, 'choices' => Formation::getCategoriesChoice(), 'label' => 'Catégorie', 'required' => false))
                ->add('dateDebut', 'datetime', array('label' => 'Date de debut (d/MM/y - HH:mm:ss)', 'format' => 'd/MM/y - HH:mm:ss', 'required' => false, 'widget' => 'single_text'))
                ->add('dateFin', 'datetime', array('label' => 'Date de fin (d/MM/y - HH:mm:ss)', 'format' => 'd/MM/y - HH:mm:ss', 'required' => false, 'widget' => 'single_text'))
                ->add('mandat', 'integer')
                ->add('formateurs', 'collection', array(
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
                ->add('membresPresents', 'collection', array(
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
                ->add('docPath', 'text', array('label' => 'Lien vers des documents externes', 'required' => false))
        ;
    }

    public function getName()
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
