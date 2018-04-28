<?php

namespace Ladb\CoreBundle\Controller\Knowledge;

use Ladb\CoreBundle\Utils\KnowledgeUtils;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Ladb\CoreBundle\Entity\Core\User;
use Ladb\CoreBundle\Entity\Knowledge\AbstractKnowledge;
use Ladb\CoreBundle\Event\PublicationEvent;
use Ladb\CoreBundle\Event\PublicationListener;
use Ladb\CoreBundle\Event\KnowledgeEvent;
use Ladb\CoreBundle\Event\KnowledgeListener;
use Ladb\CoreBundle\Model\WatchableInterface;
use Ladb\CoreBundle\Utils\VotableUtils;
use Ladb\CoreBundle\Utils\CommentableUtils;
use Ladb\CoreBundle\Utils\WatchableUtils;
use Ladb\CoreBundle\Utils\PaginatorUtils;
use Ladb\CoreBundle\Utils\SearchUtils;
use Ladb\CoreBundle\Utils\TypableUtils;
use Ladb\CoreBundle\Utils\ActivityUtils;
use Ladb\CoreBundle\Utils\PropertyUtils;

/**
 * @Route("/knowledge")
 */
class KnowledgeController extends Controller {

	private function _retriveRelatedEntityRepository($entityType) {

		$typableUtils = $this->get(TypableUtils::NAME);
		try {
			$entityRepository = $typableUtils->getRepositoryByType($entityType);
		} catch (\Exception $e) {
			throw $this->createNotFoundException($e->getMessage());
		}

		return $entityRepository;
	}

	private function _retriveRelatedEntity($entityRepository, $entityId) {

		$entity = $entityRepository->findOneById($entityId);
		if (is_null($entity)) {
			throw $this->createNotFoundException('Unknow Entity (entityId='.$entityId.').');
		}
		if (!($entity instanceof AbstractKnowledge)) {
			throw $this->createNotFoundException('Entity must extends AbstractKnowledge.');
		}

		return $entity;
	}

	private function _retieveFieldDef($entity, $field) {

		$fieldDef = $entity->getFieldDefs()[$field];
		if (is_null($fieldDef)) {
			throw $this->createNotFoundException('Unknow knowledge field (field='.$field.').');
		}

		return $fieldDef;
	}

	private function _getFieldType($fieldDef) {
		return $fieldDef[AbstractKnowledge::ATTRIB_TYPE];
	}

	private function _getFieldMutltiple($fieldDef) {
		return $fieldDef[AbstractKnowledge::ATTRIB_MULTIPLE];
	}

	private function _getFieldChoices($fieldDef) {
		return isset($fieldDef[AbstractKnowledge::ATTRIB_CHOICES]) ? $fieldDef[AbstractKnowledge::ATTRIB_CHOICES] : null;
	}

	private function _getFieldConstraints($fieldDef, $entity, $attib = AbstractKnowledge::ATTRIB_CONSTRAINTS) {
		$constraintDefs = isset($fieldDef[$attib]) ? $fieldDef[$attib] : null;
		$fieldConstraints = array();
		if (!is_null($constraintDefs)) {
			foreach ($constraintDefs as $constraintDef) {
				$constraint = new $constraintDef[0]();
				if (isset($constraintDef[1])) {
					foreach ($constraintDef[1] as $key => $value) {
						if (strpos($value, '@') !== false) {
							$value = $entity->{ substr($value, 1) }();
						}
						$constraint->{ $key } = $value;
					}
				}
				$fieldConstraints[] = $constraint;
			}
		}
		return $fieldConstraints;
	}

	private function _computeEntityClass($fieldType) {
		return '\\Ladb\\CoreBundle\\Entity\\Knowledge\\Value\\'.ucfirst($fieldType);
	}

	private function _computeFormTypeFqcn($fieldType) {
		return 'Ladb\\CoreBundle\\Form\\Type\\Knowledge\\Value\\'.ucfirst($fieldType).'ValueType';
	}

	private function _retrieveValue($valueRepository, $id) {
		$value = $valueRepository->findOneById($id);
		if (is_null($value)) {
			throw $this->createNotFoundException('Unable to find Value entity (id='.$id.').');
		}

		return $value;
	}

	private function _computeFormTypeName($fieldType, $value) {
		return 'ladb_knowledge_value_'.$fieldType.'_'.$value->getId();
	}

	/////

	/**
	 * @Route("/{entityType}/{entityId}/contributors", requirements={"entityType" = "\d+","entityId" = "\d+"}, name="core_knowledge_contributors")
	 * @Route("/{entityType}/{entityId}/contributors/{page}", requirements={"entityType" = "\d+", "entityId" = "\d+", "page" = "\d+"}, name="core_knowledge_contributors_page")
	 * @Template("LadbCoreBundle:Knowledge:contributors.html.twig")
	 */
	public function contributorsAction(Request $request, $entityType, $entityId, $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$userRepository = $om->getRepository(User::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

		// Retrieve related entity

		$entityRepository = $this->_retriveRelatedEntityRepository($entityType);
		$entity = $this->_retriveRelatedEntity($entityRepository, $entityId);

		// Retrive contributors

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$contributorIds = array_slice($entityRepository->findUserIdsById($entity->getId()), $offset, $limit - $offset);
		$contributors = $userRepository->findByIds($contributorIds);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_knowledge_contributors_page', array( 'entityType' => $entityType, 'entityId' => $entityId ), $page, $entity->getContributorCount());

		$parameters = array(
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'entity'      => $entity,
			'users'       => $contributors,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Knowledge:contributors-xhr.html.twig', $parameters);
		}
		return $parameters;
	}

	/**
	 * @Route("/{entityType}/{entityId}/{field}/create", requirements={"entityType" = "\d+","entityId" = "\d+", "field" = "\w+"}, name="core_knowledge_value_create")
	 * @Method("POST")
	 * @Template("LadbCoreBundle:Knowledge:value-new-xhr.html.twig")
	 */
	public function createFieldValueAction(Request $request, $entityType, $entityId, $field) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed.');
		}

		$knowledgeUtils = $this->get(KnowledgeUtils::NAME);
		$propertyUtils = $this->get(PropertyUtils::NAME);
		$om = $this->getDoctrine()->getManager();

		// Retrieve related entity

		$entityRepository = $this->_retriveRelatedEntityRepository($entityType);
		$entity = $this->_retriveRelatedEntity($entityRepository, $entityId);

		// Process field

		$fieldDef = $this->_retieveFieldDef($entity, $field);

		$fieldType = $this->_getFieldType($fieldDef);
		$fieldMultiple = $this->_getFieldMutltiple($fieldDef);
		$fieldChoices = $this->_getFieldChoices($fieldDef);
		$fieldConstraints = $this->_getFieldConstraints($fieldDef, $entity);
		$fieldDataConstraints = $this->_getFieldConstraints($fieldDef, $entity, AbstractKnowledge::ATTRIB_DATA_CONSTRAINTS);

		$entityClass = $this->_computeEntityClass($fieldType);
		$formTypeFqcn = $this->_computeFormTypeFqcn($fieldType);

		$value = new $entityClass();
		$value->setParentEntity($entity);
		$value->setParentEntityField($field);	// Before form validation because it was used for the uniqueness
		$form = $this->createForm($formTypeFqcn, $value, array( 'choices' => $fieldChoices, 'dataConstraints' => $fieldDataConstraints, 'constraints' => $fieldConstraints ));
		$form->handleRequest($request);
		if ($form->isValid()) {

			$user = $this->getUser();

			$value->setCreatedAt(new \DateTime());	// Force createdAt to be able to sort values on this field
			$value->setUser($user);
			$user->getMeta()->incrementProposalCount();

			$propertyUtils->addValue($entity, $field.'_value', $value);

			// Dispatch knowledge event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(KnowledgeListener::FIELD_VALUE_ADDED, new KnowledgeEvent($entity, array( 'field' => $field, 'value' => $value )));

			// Update contributors
			$contributorIds = $entityRepository->findUserIdsById($entity->getId());
			$newContributor = !in_array($user->getId(), $contributorIds);
			if ($newContributor) {
				$entity->incrementContributorCount();
			}

			$om->persist($value);

			// Create activity
			$activityUtils = $this->get(ActivityUtils::NAME);
			$activityUtils->createContributeActivity($value, false);

			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(PublicationListener::PUBLICATION_CHANGED, new PublicationEvent($entity));

			if ($entity instanceof WatchableInterface) {
				$watchableUtils = $this->get(WatchableUtils::NAME);

				// Auto watch
				$watchableUtils->autoCreateWatch($entity, $user);

			}

			// Regenerate en empty form
			$value = new $entityClass();
			$form = $this->createForm($formTypeFqcn, $value, array( 'choices' => $fieldChoices, 'dataConstraints' => $fieldDataConstraints, 'constraints' => $fieldConstraints ));
			$values = $propertyUtils->getValue($entity, $field.'_values');

			$commentableUtils = $this->get(CommentableUtils::NAME);
			$votableUtils = $this->get(VotableUtils::NAME);

			return $this->render('LadbCoreBundle:Knowledge:value-create-xhr.html.twig', array(
				'knowledge'       => $entity,
				'field'           => $field,
				'form'            => $form->createView(),
				'sourcesHistory'  => $knowledgeUtils->getValueSourcesHistory(),
				'values'          => $values,
				'commentContexts' => $commentableUtils->getCommentContexts($values),
				'voteContexts'    => $votableUtils->getVoteContexts($values, $user),
			));

		}

		return array(
			'knowledge'      => $entity,
			'field'          => $field,
			'form'           => $form->createView(),
			'sourcesHistory' => $knowledgeUtils->getValueSourcesHistory(),
		);
	}

	/**
	 * @Route("/{entityType}/{entityId}/{field}/{id}/edit", requirements={"entityType" = "\d+","entityId" = "\d+", "field" = "\w+","id" = "\d+"}, name="core_knowledge_value_edit")
	 * @Template("LadbCoreBundle:Knowledge:value-edit-xhr.html.twig")
	 */
	public function editFieldValueAction(Request $request, $entityType, $entityId, $field, $id) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed.');
		}

		$knowledgeUtils = $this->get(KnowledgeUtils::NAME);
		$om = $this->getDoctrine()->getManager();

		// Retrieve related entity

		$entityRepository = $this->_retriveRelatedEntityRepository($entityType);
		$entity = $this->_retriveRelatedEntity($entityRepository, $entityId);

		// Process field

		$fieldDef = $this->_retieveFieldDef($entity, $field);

		$fieldType = $this->_getFieldType($fieldDef);
		$fieldChoices = $this->_getFieldChoices($fieldDef);
		$fieldConstraints = $this->_getFieldConstraints($fieldDef, $entity);
		$fieldDataConstraints = $this->_getFieldConstraints($fieldDef, $entity, AbstractKnowledge::ATTRIB_DATA_CONSTRAINTS);

		$entityClass = $this->_computeEntityClass($fieldType);
		$formTypeFqcn = $this->_computeFormTypeFqcn($fieldType);

		$valueRepository = $om->getRepository($entityClass::CLASS_NAME);
		$value = $this->_retrieveValue($valueRepository, $id);
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && ($value->getUser()->getId() != $this->getUser()->getId() || $value->getVoteCount() > 0)) {
			throw $this->createNotFoundException('Not allowed (core_knowledge_value_edit)');
		}

		$formTypeName = $this->_computeFormTypeName($fieldType, $value);

		$form = $this->get('form.factory')->createNamed($formTypeName, $formTypeFqcn, $value, array( 'choices' => $fieldChoices, 'dataConstraints' => $fieldDataConstraints, 'constraints' => $fieldConstraints ));

		return array(
			'knowledge'      => $entity,
			'field'          => $field,
			'form'           => $form->createView(),
			'sourcesHistory' => $knowledgeUtils->getValueSourcesHistory(),
		);
	}

	/**
	 * @Route("/{entityType}/{entityId}/{field}/{id}/update", requirements={"entityType" = "\d+","entityId" = "\d+", "field" = "\w+","id" = "\d+"}, name="core_knowledge_value_update")
	 * @Template("LadbCoreBundle:Knowledge:value-edit-xhr.html.twig")
	 */
	public function updateFieldValueAction(Request $request, $entityType, $entityId, $field, $id) {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
			throw $this->createNotFoundException('Not allowed (core_knowledge_value_update)');
		}
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed.');
		}

		$knowledgeUtils = $this->get(KnowledgeUtils::NAME);
		$om = $this->getDoctrine()->getManager();

		// Retrieve related entity

		$entityRepository = $this->_retriveRelatedEntityRepository($entityType);
		$entity = $this->_retriveRelatedEntity($entityRepository, $entityId);

		// Process field

		$fieldDef = $this->_retieveFieldDef($entity, $field);

		$fieldType = $this->_getFieldType($fieldDef);
		$fieldChoices = $this->_getFieldChoices($fieldDef);
		$fieldConstraints = $this->_getFieldConstraints($fieldDef, $entity);
		$fieldDataConstraints = $this->_getFieldConstraints($fieldDef, $entity, AbstractKnowledge::ATTRIB_DATA_CONSTRAINTS);

		$entityClass = $this->_computeEntityClass($fieldType);
		$formTypeFqcn = $this->_computeFormTypeFqcn($fieldType);

		$valueRepository = $om->getRepository($entityClass::CLASS_NAME);
		$value = $this->_retrieveValue($valueRepository, $id);
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && ($value->getUser()->getId() != $this->getUser()->getId() || $value->getVoteCount() > 0)) {
			throw $this->createNotFoundException('Not allowed (core_knowledge_value_update)');
		}

		$formTypeName = $this->_computeFormTypeName($fieldType, $value);

		$form = $this->get('form.factory')->createNamed($formTypeName, $formTypeFqcn, $value, array( 'choices' => $fieldChoices, 'dataConstraints' => $fieldDataConstraints, 'constraints' => $fieldConstraints ));
		$form->handleRequest($request);
		if ($form->isValid()) {

			// Dispatch knowledge event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(KnowledgeListener::FIELD_VALUE_UPDATED, new KnowledgeEvent($entity, array( 'field' => $field, 'value' => $value )));

			$om->flush();

			// Search index update
			$searchUtils = $this->get(SearchUtils::NAME);
			$searchUtils->replaceEntityInIndex($entity);

			$commentableUtils = $this->get(CommentableUtils::NAME);
			$votableUtils = $this->get(VotableUtils::NAME);

			return $this->render('LadbCoreBundle:Knowledge:value-update-xhr.html.twig', array(
				'knowledge'       => $entity,
				'field'           => $field,
				'value'           => $value,
				'commentContext'  => $commentableUtils->getCommentContext($value),
				'voteContext'     => $votableUtils->getVoteContext($value, $this->getUser()),
			));

		}

		return array(
			'knowledge'      => $entity,
			'field'          => $field,
			'form'           => $form->createView(),
			'sourcesHistory' => $knowledgeUtils->getValueSourcesHistory(),
		);
	}

	/**
	 * @Route("/{entityType}/{entityId}/{field}/{id}/delete", requirements={"entityType" = "\d+","entityId" = "\d+", "field" = "\w+", "id" = "\d+"}, name="core_knowledge_value_delete")
	 * @Template("LadbCoreBundle:Knowledge:value-delete-xhr.html.twig")
	 */
	public function deleteFieldValueAction(Request $request, $entityType, $entityId, $field, $id) {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
			throw $this->createNotFoundException('Not allowed (core_knowledge_value_delete)');
		}
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed (core_knowledge_value_delete)');
		}

		$propertyUtils = $this->get(PropertyUtils::NAME);
		$om = $this->getDoctrine()->getManager();

		// Retrieve related entity

		$entityRepository = $this->_retriveRelatedEntityRepository($entityType);
		$entity = $this->_retriveRelatedEntity($entityRepository, $entityId);

		// Process field

		$fieldDef = $this->_retieveFieldDef($entity, $field);

		$fieldType = $fieldDef[AbstractKnowledge::ATTRIB_TYPE];
		$fieldMandatory = isset($fieldDef[AbstractKnowledge::ATTRIB_MANDATORY]) && $fieldDef[AbstractKnowledge::ATTRIB_MANDATORY];

		$entityClass = $this->_computeEntityClass($fieldType);

		$valueRepository = $om->getRepository($entityClass::CLASS_NAME);
		$value = $this->_retrieveValue($valueRepository, $id);
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && ($value->getUser()->getId() != $this->getUser()->getId() || $value->getVoteCount() > 0 || $fieldMandatory)) {
			throw $this->createNotFoundException('Not allowed (core_knowledge_value_delete)');
		}

		$propertyUtils->removeValue($entity, $field.'_value', $value);

		// Dispatch knowledge event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(KnowledgeListener::FIELD_VALUE_REMOVED, new KnowledgeEvent($entity, array( 'field' => $field, 'value' => $value )));

		// Decrement user proposal count
		$value->getUser()->getMeta()->incrementProposalCount(-1);

		// Delete comments
		$commentableUtils = $this->get(CommentableUtils::NAME);
		$commentableUtils->deleteComments($value, false);

		// Delete votes
		$votableUtils = $this->get(VotableUtils::NAME);
		$votableUtils->deleteVotes($value, $entity, false);

		// Delete activities
		$activityUtils = $this->get(ActivityUtils::NAME);
		$activityUtils->deleteActivitiesByValue($value, false);

		$om->remove($value);
		$om->flush(); // Flushed to be sure the deleted value will be excluded from contributor request

		// Update contributors
		$contributorIds = $entityRepository->findUserIdsById($entity->getId());
		$noMoreContribution = !in_array($value->getUser()->getId(), $contributorIds);
		if ($noMoreContribution) {
			$entity->incrementContributorCount(-1);
			$om->flush();	// Flush updated entity
		}

		// Search index update
		$searchUtils = $this->get(SearchUtils::NAME);
		$searchUtils->replaceEntityInIndex($entity);

		// Reload values

		$values = $propertyUtils->getValue($entity, $field.'_values');

		$commentableUtils = $this->get(CommentableUtils::NAME);
		$votableUtils = $this->get(VotableUtils::NAME);

		return array(
			'knowledge'       => $entity,
			'field'           => $field,
			'values'          => $values,
			'commentContexts' => $commentableUtils->getCommentContexts($values),
			'voteContexts'    => $votableUtils->getVoteContexts($values, $this->getUser()),
		);
	}

	/**
	 * @Route("/{entityType}/{entityId}/{fieldSrc}/{fieldDest}/{id}/admin/move", requirements={"entityType" = "\d+","entityId" = "\d+", "fieldSrc" = "\w+", "fieldDest" = "\w+", "id" = "\d+"}, name="core_knowledge_value_admin_move")
	 * @Template("LadbCoreBundle:Knowledge:value-move-xhr.html.twig")
	 */
	public function adminMoveFieldValueAction(Request $request, $entityType, $entityId, $fieldSrc, $fieldDest, $id) {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Not allowed (core_knowledge_value_admin_move)');
		}
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed (core_knowledge_value_admin_move)');
		}
		if ($fieldSrc == $fieldDest) {
			throw $this->createNotFoundException('Can\'t move to the same field (core_knowledge_value_admin_move)');
		}

		$propertyUtils = $this->get(PropertyUtils::NAME);
		$om = $this->getDoctrine()->getManager();

		// Retrieve related entity

		$entityRepository = $this->_retriveRelatedEntityRepository($entityType);
		$entity = $this->_retriveRelatedEntity($entityRepository, $entityId);

		// Process field

		$fieldSrcDef = $this->_retieveFieldDef($entity, $fieldSrc);
		$fieldDestDef = $this->_retieveFieldDef($entity, $fieldDest);

		$fieldSrcType = $fieldSrcDef[AbstractKnowledge::ATTRIB_TYPE];
		$fieldDestType = $fieldDestDef[AbstractKnowledge::ATTRIB_TYPE];

		if ($fieldSrcType != $fieldDestType) {
			throw $this->createNotFoundException('Incompatible field types (core_knowledge_value_admin_move)');
		}

		$entityClass = $this->_computeEntityClass($fieldSrcType);

		$valueRepository = $om->getRepository($entityClass::CLASS_NAME);
		$value = $this->_retrieveValue($valueRepository, $id);

		$value->setParentEntityField($fieldDest);

		// Remove from SRC
		$propertyUtils->removeValue($entity, $fieldSrc.'_value', $value);

		// Dispatch knowledge event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(KnowledgeListener::FIELD_VALUE_REMOVED, new KnowledgeEvent($entity, array( 'field' => $fieldSrc, 'value' => $value )));

		// Add to DEST
		$propertyUtils->addValue($entity, $fieldDest.'_value', $value);

		// Dispatch knowledge event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(KnowledgeListener::FIELD_VALUE_ADDED, new KnowledgeEvent($entity, array( 'field' => $fieldDest, 'value' => $value )));

		$om->flush();

		// Reload values

		$values = $propertyUtils->getValue($entity, $fieldSrc.'_values');

		$commentableUtils = $this->get(CommentableUtils::NAME);
		$votableUtils = $this->get(VotableUtils::NAME);

		return array(
			'knowledge'       => $entity,
			'field'           => $fieldSrc,
			'values'          => $values,
			'commentContexts' => $commentableUtils->getCommentContexts($values),
			'voteContexts'    => $votableUtils->getVoteContexts($values, $this->getUser()),
		);
	}

	/**
	 * @Route("/{entityType}/{entityId}/{field}.xhr", requirements={"entityType" = "\d+","entityId" = "\d+", "field" = "\w+"}, name="core_knowledge_field_show")
	 * @Template("LadbCoreBundle:Knowledge:field-show-xhr.html.twig")
	 */
	public function showFieldAction(Request $request, $entityType, $entityId, $field) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed.');
		}

		$knowledgeUtils = $this->get(KnowledgeUtils::NAME);
		$propertyUtils = $this->get(PropertyUtils::NAME);

		// Retrieve related entity

		$entityRepository = $this->_retriveRelatedEntityRepository($entityType);
		$entity = $this->_retriveRelatedEntity($entityRepository, $entityId);

		// Process field

		$fieldDef = $this->_retieveFieldDef($entity, $field);

		$fieldType = $this->_getFieldType($fieldDef);
		$fieldChoices = $this->_getFieldChoices($fieldDef);
		$fieldConstraints = $this->_getFieldConstraints($fieldDef, $entity);

		$entityClass = $this->_computeEntityClass($fieldType);
		$formTypeFqcn = $this->_computeFormTypeFqcn($fieldType);

		$values = $propertyUtils->getValue($entity, $field.'_values');
		$value = new $entityClass();

		$form = null;
		if ($this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
			$form = $this->createForm($formTypeFqcn, $value, array( 'choices' => $fieldChoices, 'dataConstraints' => $fieldConstraints ));
		}

		$commentableUtils = $this->get(CommentableUtils::NAME);
		$votableUtils = $this->get(VotableUtils::NAME);

		return array(
			'knowledge'       => $entity,
			'field'           => $field,
			'values'          => $values,
			'commentContexts' => $commentableUtils->getCommentContexts($values),
			'voteContexts'    => $votableUtils->getVoteContexts($values, $this->getUser()),
			'form'            => is_null($form) ? null : $form->createView(),
			'sourcesHistory'  => $knowledgeUtils->getValueSourcesHistory(),
		);
	}

}
