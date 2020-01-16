<?php

namespace Ladb\CoreBundle\Controller\Collection;

use Ladb\CoreBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Ladb\CoreBundle\Entity\Collection\Collection;
use Ladb\CoreBundle\Model\CollectionnableInterface;
use Ladb\CoreBundle\Utils\TypableUtils;

abstract class AbstractCollectionBasedController extends AbstractController {

	protected function _retrieveCollection($id) {
		$om = $this->getDoctrine()->getManager();
		$collectionRepository = $om->getRepository(Collection::CLASS_NAME);

		$id = intval($id);

		$collection = $collectionRepository->findOneById($id);
		if (is_null($collection)) {
			throw $this->createNotFoundException('Unable to find Collection entity (id='.$id.').');
		}

		return $collection;
	}

	protected function _retrieveRelatedEntity($entityType, $entityId) {
		$typableUtils = $this->get(TypableUtils::NAME);
		try {
			$entity = $typableUtils->findTypable(intval($entityType), intval($entityId));
		} catch (\Exception $e) {
			throw $this->createNotFoundException($e->getMessage());
		}
		if (!($entity instanceof CollectionnableInterface)) {
			throw $this->createNotFoundException('Entity must implements CollectionnableInterface.');
		}
		return $entity;
	}

}
