<?php

namespace App\Controller\Knowledge\School;

use App\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use App\Utils\SearchUtils;
use App\Utils\FieldPreprocessorUtils;
use App\Utils\WatchableUtils;
use App\Utils\ActivityUtils;
use App\Entity\Knowledge\School;
use App\Form\Type\Knowledge\School\TestimonialType;
use App\Manager\Knowledge\School\TestimonialManager;
use App\Event\PublicationEvent;
use App\Event\PublicationListener;

/**
 * @Route("/ecoles")
 */
class TestimonialController extends AbstractController {

    public static function getSubscribedServices() {
        return array_merge(parent::getSubscribedServices(), array(
            '?'.TestimonialManager::class,
        ));
    }

    /**
	 * @Route("/{id}/temoignages/new", requirements={"id" = "\d+"}, name="core_knowledge_school_testimonial_new")
	 * @Template("Knowledge/School/Testimonial/new-xhr.html.twig")
	 */
	public function new(Request $request, $id) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed (core_review_new)');
		}

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
	 * @Template("Knowledge/School/Testimonial/new-xhr.html.twig")
	 */
	public function create(Request $request, $id) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed (core_review_create)');
		}

		// Exclude if user is not email confirmed
		if (!$this->getUser()->getEmailConfirmed()) {
			throw $this->createNotFoundException('Not allowed - User email not confirmed (core_knowledge_value_create)');
		}

		$this->createLock('core_knowledge_school_testimonial_create', false, self::LOCK_TTL_CREATE_ACTION, false);

		$om = $this->getDoctrine()->getManager();
		$schoolRepository = $om->getRepository(School::CLASS_NAME);
		$testimonialRepository = $om->getRepository(School\Testimonial::CLASS_NAME);

		$school = $schoolRepository->findOneById($id);
		if (is_null($school)) {
			throw $this->createNotFoundException('Unable to find School entity (id='.$id.').');
		}
		if ($testimonialRepository->existsBySchoolAndUser($school, $this->getUser())) {
			throw $this->createNotFoundException('Only one testimonial is allowed by user.');
		}

		$testimonial = new School\Testimonial();
		$testimonial->setSchool($school);				// Used by validator
		$testimonial->setUser($this->getUser());		// Used by validator
		$form = $this->createForm(TestimonialType::class, $testimonial);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::class);
			$fieldPreprocessorUtils->preprocessFields($testimonial);

			$testimonial->setUser($this->getUser());

			$school->addTestimonial($testimonial);

			$school->incrementTestimonialCount();
			$this->getUser()->getMeta()->incrementTestimonialCount();

			$om->persist($testimonial);

			// Create activity
			$activityUtils = $this->get(ActivityUtils::class);
			$activityUtils->createTestifyActivity($testimonial, false);

			// Dispatch publication event (on School)
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(new PublicationEvent($school), PublicationListener::PUBLICATION_CHANGED);

			// Auto watch
			$watchableUtils = $this->container->get(WatchableUtils::class);
			$watchableUtils->autoCreateWatch($school, $this->getUser());

			$om->flush();

			return $this->render('Knowledge/School/Testimonial/create-xhr.html.twig', array(
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
	 * @Template("Knowledge/School/Testimonial/edit-xhr.html.twig")
	 */
	public function edit(Request $request, $id) {
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
	 * @Template("Knowledge/School/Testimonial/edit-xhr.html.twig")
	 */
	public function update(Request $request, $id) {
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

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::class);
			$fieldPreprocessorUtils->preprocessFields($testimonial);

			if ($testimonial->getUser()->getId() == $this->getUser()->getId()) {
				$testimonial->setUpdatedAt(new \DateTime());
			}

			$om->flush();

			return $this->render('Knowledge/School/Testimonial/update-xhr.html.twig', array(
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
	public function delete(Request $request, $id) {
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
		$testimonialManager = $this->get(TestimonialManager::class);
		$testimonialManager->delete($testimonial);

		// Search index update
		$searchUtils = $this->get(SearchUtils::class);
		$searchUtils->replaceEntityInIndex($school);

		$om->flush();

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('knowledge.school.testimonial.alert.delete_success'));

		return $this->redirect($this->generateUrl('core_school_show', array( 'id' => $school->getSluggedId() )));
	}

	/**
	 * @Route("/temoignages/{id}", requirements={"id" = "\d+"}, name="core_knowledge_school_testimonial_show")
	 */
	public function show(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$testimonialRepository = $om->getRepository(School\Testimonial::CLASS_NAME);

		$testimonial = $testimonialRepository->findOneById($id);
		if (is_null($testimonial)) {
			throw $this->createNotFoundException('Unable to find Testimonial entity (id='.$id.').');
		}

		$school = $testimonial->getSchool();

		return $this->redirect($this->generateUrl('core_school_show', array( 'id' => $school->getSluggedId() )).'#_testimonial_'.$testimonial->getId());
	}

}
