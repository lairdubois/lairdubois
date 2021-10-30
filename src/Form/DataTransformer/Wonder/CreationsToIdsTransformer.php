<?php

namespace App\Form\DataTransformer\Wonder;

use App\Entity\Wonder\Creation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

class CreationsToIdsTransformer implements DataTransformerInterface {

	private $om;

	public function __construct(EntityManagerInterface $om) {
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
		foreach ($creations as $creation) {
			$idsArray[] = $creation->getId();
		}
		return implode(',', $idsArray);
	}

	public function reverseTransform($idsString) {
		if (!$idsString) {
			return array();
		}

		$creations = array();
		$idsStrings = preg_split("/[,]+/", $idsString);
		$repository = $this->om->getRepository(Creation::class);
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