<?php

namespace Ladb\CoreBundle\Controller\Knowledge\School;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Ladb\CoreBundle\Utils\SearchUtils;
use Ladb\CoreBundle\Utils\FieldPreprocessorUtils;
use Ladb\CoreBundle\Utils\WatchableUtils;
use Ladb\CoreBundle\Utils\ActivityUtils;
use Ladb\CoreBundle\Entity\Knowledge\School;
use Ladb\CoreBundle\Form\Type\Knowledge\School\TestimonialType;
use Ladb\CoreBundle\Manager\Knowledge\School\TestimonialManager;
use Ladb\CoreBundle\Event\PublicationEvent;
use Ladb\CoreBundle\Event\PublicationListener;

/**
 * @Route("/ecoles")
 */
class TestimonialController extends Controller {

	/**
	 * @Route("/{id}/temoignages/new", requirements={"id" = "\d+"}, name="core_knowledge_school_testimonial_new")
	 * @Template("LadbCoreBundle:Knowledge/School/Testimonial:new-xhr.html.twig")
	 */
	public function newAction($id) {
		$om = $this->getDoctrine()->getManager();
		$schoolRepository = $om->getRepository(School::CLASS_NAME);

		$school = $schoolRepository->findOneById($id);
		if (is_null($school)) {
			throw $this->createNotFoundException('Unable to find School entity (id='.$id.').');
		}

		$testimonial = new School\Testimonial();
		$form = $this->createForm(TestimonialType::class, $testimonial);

		return array(
			'school' => $school,
			'form'   => $form->createView(),
		);
	}

	/**
	 * @Route("/{id}/temoignages/create", requirements={"id" = "\d+"}, methods={"POST"}, name="core_knowledge_school_testimonial_create")
	 * @Template("LadbCoreBundle:Knowledge/School/Testimonial:new-xhr.html.twig")
	 */
	public function createAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$schoolRepository = $om->getRepository(School::CLASS_NAME);

		$school = $schoolRepository->findOneById($id);
		if (is_null($school)) {
			throw $this->createNotFoundException('Unable to find School entity (id='.$id.').');
		}

		$testimonial = new School\Testimonial();
		$testimonial->setSchool($school);				// Used by validator
		$testimonial->setUser($this->getUser());		// Used by validator
		$form = $this->createForm(TestimonialType::class, $testimonial);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
			$fieldPreprocessorUtils->preprocessFields($testimonial);

			$testimonial->setUser($this->getUser());

			$school->addTestimonial($testimonial);

			$school->incrementTestimonialCount();
			$this->getUser()->getMeta()->incrementTestimonialCount();

			$om->persist($testimonial);

			// Create activity
			$activityUtils = $this->get(ActivityUtils::NAME);
			$activityUtils->createTestifyActivity($testimonial, false);

			// Dispatch publication event (on School)
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(PublicationListener::PUBLICATION_CHANGED, new PublicationEvent($school));

			// Auto watch
			$watchableUtils = $this->container->get(WatchableUtils::NAME);
			$watchableUtils->autoCreateWatch($school, $this->getUser());

			$om->flush();

			return $this->render('LadbCoreBundle:Knowledge/School/Testimonial:create-xhr.html.twig', array(
				'school'      => $school,
				'testimonial' => $testimonial,
			));
		}

		return array(
			'school' => $school,
			'form'   => $form->createView(),
		);
	}

	/**
	 * @Route("/temoignages/{id}/edit", requirements={"id" = "\d+"}, name="core_knowledge_school_testimonial_edit")
	 * @Template("LadbCoreBundle:Knowledge/School/Testimonial:edit-xhr.html.twig")
	 */
	public function editAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$testimonialRepository = $om->getRepository(School\Testimonial::CLASS_NAME);

		$testimonial = $testimonialRepository->findOneById($id);
		if (is_null($testimonial)) {
			throw $this->createNotFoundException('Unable to find Testimonial entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $testimonial->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_knowledge_school_testimonial_edit)');
		}

		$form = $this->createForm(TestimonialType::class, $testimonial);

		return array(
			'testimonial' => $testimonial,
			'form'        => $form->createView(),
		);
	}

	/**
	 * @Route("/temoignages/{id}/update", requirements={"id" = "\d+"}, methods={"POST"}, name="core_knowledge_school_testimonial_update")
	 * @Template("LadbCoreBundle:Knowledge/School/Testimonial:edit-xhr.html.twig")
	 */
	public function updateAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$testimonialRepository = $om->getRepository(School\Testimonial::CLASS_NAME);

		$testimonial = $testimonialRepository->findOneById($id);
		if (is_null($testimonial)) {
			throw $this->createNotFoundException('Unable to find Testimonial entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $testimonial->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_knowledge_school_testimonial_update)');
		}

		$form = $this->createForm(TestimonialType::class, $testimonial);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
			$fieldPreprocessorUtils->preprocessFields($testimonial);

			if ($testimonial->getUser()->getId() == $this->getUser()->getId()) {
				$testimonial->setUpdatedAt(new \DateTime());
			}

			$om->flush();

			return $this->render('LadbCoreBundle:Knowledge/School/Testimonial:update-xhr.html.twig', array(
				'school'      => $testimonial->getSchool(),
				'testimonial' => $testimonial,
			));
		}

		return array(
			'testimonial' => $testimonial,
			'form'        => $form->createView(),
		);
	}

	/**
	 * @Route("/temoignages/{id}/delete", requirements={"id" = "\d+"}, name="core_knowledge_school_testimonial_delete")
	 */
	public function deleteAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$testimonialRepository = $om->getRepository(School\Testimonial::CLASS_NAME);

		$testimonial = $testimonialRepository->findOneById($id);
		if (is_null($testimonial)) {
			throw $this->createNotFoundException('Unable to find Testimonial entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $testimonial->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_knowledge_school_testimonial_delete)');
		}

		$school = $testimonial->getSchool();

		// Delete
		$testimonialManager = $this->get(TestimonialManager::NAME);
		$testimonialManager->delete($testimonial);

		// Search index update
		$searchUtils = $this->get(SearchUtils::NAME);
		$searchUtils->replaceEntityInIndex($school);

		$om->flush();

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('knowledge.school.testimonial.form.alert.delete_success'));

		return $this->redirect($this->generateUrl('core_school_show', array( 'id' => $school->getSluggedId() )));
	}

}
