<?php

/*
 * This file is part of the Incipio package.
 *
 * (c) Florian Lefevre
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mgate\FormationBundle\Controller;

use Mgate\FormationBundle\Entity\Formation;
use Mgate\FormationBundle\Form\Type\FormationType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FormationController extends Controller
{
    /**
     * @Security("has_role('ROLE_CA')")
     * Display a list of all training given order by date desc
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $formations = $em->getRepository('MgateFormationBundle:Formation')->getAllFormations(array(), array('dateDebut' => 'DESC'));

        return $this->render('MgateFormationBundle:Gestion:index.html.twig', array(
            'formations' => $formations,
        ));
    }

    /**
     * @Security("has_role('ROLE_SUIVEUR')")
     * Display a list of all training group by term.
     */
    public function listerAction()
    {
        $em = $this->getDoctrine()->getManager();
        $formationsParMandat = $em->getRepository('MgateFormationBundle:Formation')->findAllByMandat();

        return $this->render('MgateFormationBundle:Formations:lister.html.twig', array(
            'formationsParMandat' => $formationsParMandat,
        ));
    }

    /**
     * @Security("has_role('ROLE_SUIVEUR')")
     *
     * @param Formation $formation The training to display
     *
     * @return Response
     *                  Display a training
     */
    public function voirAction(Formation $formation)
    {
        return $this->render('MgateFormationBundle:Formations:voir.html.twig', array(
            'formation' => $formation,
        ));
    }

    /**
     * @Security("has_role('ROLE_CA')")
     *
     * @param $id mixed valid id : modify an existing training; unknown id : display a creation form
     *
     * @return Response
     *                  Manage creation and update of a training
     */
    public function modifierAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        if (!$formation = $em->getRepository('Mgate\FormationBundle\Entity\Formation')->find($id)) {
            $formation = new Formation();
        }

        $form = $this->createForm(new FormationType(), $formation);
        $messages = array();

        if ($this->get('request')->getMethod() == 'POST') {
            $form->handleRequest($this->get('request'));

            if ($form->isValid()) {
                $em->persist($formation);
                $em->flush();

                $form = $this->createForm(new FormationType(), $formation);
                array_push($messages, array('label' => 'success', 'message' => 'Formation modifée'));
            } else {
                //constitution du tableau d'erreurs
                $errors = $this->get('validator')->validate($formation);
                foreach ($errors as $error) {
                    array_push($messages, array('label' => 'warning', 'message' => $error->getPropertyPath().' : '.$error->getMessage()));
                }
            }
        }

        return $this->render('MgateFormationBundle:Gestion:modifier.html.twig', array('form' => $form->createView(),
            'formation' => $formation,
            'messages' => $messages,
        ));
    }

    /**
     * @Security("has_role('ROLE_CA')")
     *
     * @param Request $request
     *
     * @return Response
     *                  Manage particpant present to a training
     */
    public function participationAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $formationsParMandat = $em->getRepository('MgateFormationBundle:Formation')->findAllByMandat();

        $choices = array();
        foreach ($formationsParMandat as $key => $value) {
            $choices[$key] = $key;
        }

        $defaultData = array();
        $form = $this->createFormBuilder($defaultData)
            ->add(
                'mandat',
                'choice',
                array(
                    'label' => 'Présents aux formations du mandat ',
                    'choices' => $choices,
                    'required' => true,
                )
            )->getForm();

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            $data = $form->getData();
            $mandat = $data['mandat'];
            $formations = array_key_exists($mandat, $formationsParMandat) ? $formationsParMandat[$mandat] : array();
        } else {
            $formations = count($formationsParMandat) ? reset($formationsParMandat) : array();
        }

        $presents = array();

        foreach ($formations as $formation) {
            foreach ($formation->getMembresPresents() as $present) {
                $id = $present->getPrenomNom();
                if (array_key_exists($id, $presents)) {
                    $presents[$id][] = $formation->getId();
                } else {
                    $presents[$id] = array($formation->getId());
                }
            }
        }

        return $this->render('MgateFormationBundle:Gestion:participation.html.twig', array(
            'form' => $form->createView(),
            'formations' => $formations,
            'presents' => $presents,
        ));
    }

    /**
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param Formation $formation The training to delete (paramconverter from id)
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *                                                            Delete a training
     */
    public function supprimerAction(Formation $formation)
    {
        $em = $this->getDoctrine()->getManager();

        $em->remove($formation);
        $em->flush();

        return $this->redirect($this->generateUrl('Mgate_formations_lister', array()));
    }
}
