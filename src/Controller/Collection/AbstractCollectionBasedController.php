<?php

namespace App\Controller\Collection;

use App\Controller\AbstractController;
use App\Entity\Collection\Collection;
use App\Model\CollectionnableInterface;
use App\Utils\TypableUtils;

abstract class AbstractCollectionBasedController extends AbstractController {

    public static function getSubscribedServices() {
        return array_merge(parent::getSubscribedServices(), array(
            '?'.TypableUtils::class,
        ));
    }

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
		$typableUtils = $this->get(TypableUtils::class);
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
