<?php

namespace Ladb\CoreBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Doctrine\Common\Persistence\ObjectManager;
use Ladb\CoreBundle\Entity\Wonder\Creation;

class CreationsToIdsTransformer implements DataTransformerInterface {

	private $om;

	public function __construct(ObjectManager $om) {
		$this->om = $om;
	}

	public function transform($creations) {
		if (null === $creations) {
			return '';
		}

		if (!$creations instanceof \Doctrine\Common\Collections\Collection) {
			throw new UnexpectedTypeException($creations, '\Doctrine\Common\Collections\Collection');
		}

		$idsArray = array();
		foreach ($creations as $creations) {
			$idsArray[] = $creations->getId();
		}
		return implode(',', $idsArray);
	}

	public function reverseTransform($idsString) {
		if (!$idsString) {
			return array();
		}

		$creations = array();
		$idsStrings = preg_split("/[,]+/", $idsString);
		$repository = $this->om->getRepository(Creation::CLASS_NAME);
		foreach ($idsStrings as $idString) {
			$id = intval($idString);
			if ($id == 0) {
				continue;
			}
			$creation = $repository->find($id);
			if (is_null($creation)) {
				throw new TransformationFailedException();
			}
			$creations[] = $creation;
		}

		return $creations;
	}

}