<?php

/*
 * This file is part of the Incipio package.
 *
 * (c) Florian Lefevre
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mgate\PersonneBundle\Controller;

use Mgate\PersonneBundle\Entity\Membre;
use Mgate\PersonneBundle\Form\Type\MembreType;
use Mgate\PubliBundle\Entity\RelatedDocument;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;

class MembreController extends Controller
{
    /**
     * @Security("has_role('ROLE_SUIVEUR')")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('MgatePersonneBundle:Membre')->getMembres();

        return $this->render('MgatePersonneBundle:Membre:index.html.twig', array(
            'membres' => $entities,
        ));
    }

    /**
     * @Security("has_role('ROLE_SUIVEUR')")
     */
    public function listIntervenantsAction()
    {
        $em = $this->getDoctrine()->getManager();
        $intervenants = $em->getRepository('MgatePersonneBundle:Membre')->getByMissionsNonNul();

        return $this->render('MgatePersonneBundle:Membre:indexIntervenants.html.twig', array(
            'intervenants' => $intervenants,
        ));
    }

    /**
     * @Security("has_role('ROLE_SUIVEUR')")
     */
    public function statistiqueAction($page)
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('MgatePersonneBundle:Membre')->findAll();

        $membresActifs = array();
        foreach ($entities as $membre) {
            foreach ($membre->getMandats() as $mandat) {
                if ($mandat->getPoste()->getIntitule() == 'Membre' && $mandat->getDebutMandat() < new \DateTime('now') && $mandat->getFinMandat() > new \DateTime('now')) {
                    $membresActifs[] = $membre;
                }
            }
        }

        return $this->render('MgatePersonneBundle:Membre:index.html.twig', array(
            'membres' => $membresActifs,
        ));
    }

    /**
     * @Security("has_role('ROLE_ELEVE')")
     */
    public function voirAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $membre = $em->getRepository('MgatePersonneBundle:Membre')->getMembreCompetences($id);

        if (!$membre) {
            throw $this->createNotFoundException('Le membre demandé n\'existe pas !');
        }
        if ($this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $membre->getPersonne() && !$membre->getPersonne()->getUser()) {
            $create_user_form = $this->createFormBuilder(array('id' => $membre->getPersonne()->getId()))
                ->add('id', HiddenType::class)
                ->setAction($this->generateUrl('Mgate_user_addFromPersonne', array('id' => $membre->getPersonne()->getId())))
                ->setMethod('POST')
                ->getForm();
        }


        return $this->render('MgatePersonneBundle:Membre:voir.html.twig', array(
            'membre' => $membre,
            'create_user_form' => (isset($create_user_form) ? $create_user_form->createView() : null)
        ));
    }

    /*
     * Ajout ET modification des membres (create if membre not existe )
     */

    /**
     * @Security("has_role('ROLE_SUIVEUR')")
     */
    public function modifierAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $documentManager = $this->get('Mgate.document_manager');

        if (!$membre = $em->getRepository('Mgate\PersonneBundle\Entity\Membre')->find($id)) {
            $membre = new Membre();

            $now = new \DateTime('now');
            $now->modify('+ 3 year');

            $membre->setPromotion($now->format('Y'));

            $now = new \DateTime('now');
            $now->modify('- 20 year');
            $membre->setDateDeNaissance($now);
        }

        $deleteForm = $this->createDeleteForm($id);

        $form = $this->createForm(MembreType::class, $membre);

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);
            $photoUpload = $form->get('photo')->getData();

            if ($form->isValid()) {

                // Mail étudiant
                if (!$membre->getEmailEMSE()) {
                    $email_etu_service = $this->container->get('Mgate.email_etu');
                    $membre->setEmailEMSE($email_etu_service->getEmailEtu($membre));
                }

                /*
                 * Traitement de l'image de profil
                 */
                if ($membre->getPersonne()) {
                    $authorizedMIMEType = array('image/jpeg', 'image/png', 'image/bmp');
                    $photoInformation = new RelatedDocument();
                    $photoInformation->setMembre($membre);
                    $name = 'Photo - ' . $membre->getIdentifiant() . ' - ' . $membre->getPersonne()->getPrenomNom();

                    if ($photoUpload) {
                        $document = $documentManager->uploadDocumentFromFile($photoUpload, $authorizedMIMEType, $name, $photoInformation, true);
                        $membre->setPhotoURI($this->get('router')->generate('Mgate_publi_document_voir', array('id' => $document->getId())));
                    }
                }

                if (!$membre->getIdentifiant()) {
                    $initial = substr($membre->getPersonne()->getPrenom(), 0, 1) . substr($membre->getPersonne()->getNom(), 0, 1);
                    $ident = count($em->getRepository('Mgate\PersonneBundle\Entity\Membre')->findBy(array('identifiant' => $initial))) + 1;
                    while ($em->getRepository('Mgate\PersonneBundle\Entity\Membre')->findOneBy(array('identifiant' => $initial . $ident))) {
                        $ident++;
                    }
                    $membre->setIdentifiant(strtoupper($initial . $ident));
                }

                $em->persist($membre);
                $em->flush();
                $this->addFlash('success', 'Membre enregistré');
                return $this->redirectToRoute('MgatePersonne_membre_voir', array('id' => $membre->getId()));

            }
            //form invalid
            $this->addFlash('danger', 'Le formulaire contient des erreurs.');
        }

        return $this->render('MgatePersonneBundle:Membre:modifier.html.twig', array(
            'form' => $form->createView(),
            'delete_form' => $deleteForm->createView(),
            'photoURI' => $membre->getPhotoURI(),
        ));
    }

    /**
     * @Security("has_role('ROLE_SUIVEUR')")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            if (!$entity = $em->getRepository('Mgate\PersonneBundle\Entity\Membre')->find($id)) {
                throw $this->createNotFoundException('Le membre demandé n\'existe pas !');
            }

            if ($entity->getPersonne()) {
                $entity->getPersonne()->setMembre(null);
                if ($entity->getPersonne()->getUser()) {
                    // pour pouvoir réattribuer le compte
                    $entity->getPersonne()->getUser()->setPersonne(null);
                }
                $entity->getPersonne()->setUser(null);
            }
            $entity->setPersonne(null);
            //est-ce qu'on supprime la personne aussi ?

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('MgatePersonne_membre_homepage'));
    }

    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', HiddenType::class)
            ->getForm();
    }

    /**
     * @Security("has_role('ROLE_SUIVEUR')")
     */
    public function impayesAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('MgatePersonneBundle:Membre')->findByformatPaiement('aucun');

        return $this->render('MgatePersonneBundle:Membre:impayes.html.twig', array(
            'membres' => $entities,
        ));
    }
}
