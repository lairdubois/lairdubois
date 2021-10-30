<?php

namespace App\Form\DataTransformer\Knowledge;

use App\Entity\Knowledge\School;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

class SchoolsToIdsTransformer implements DataTransformerInterface {

	private $om;

	public function __construct(EntityManagerInterface $om) {
		$this->om = $om;
	}

	public function transform($schools) {
		if (null === $schools) {
			return '';
		}

		if (!$schools instanceof \Doctrine\Common\Collections\Collection) {
			throw new UnexpectedTypeException($schools, '\Doctrine\Common\Collections\Collection');
		}

		$idsArray = array();
		foreach ($schools as $school) {
			$idsArray[] = $school->getId();
		}
		return implode(',', $idsArray);
	}

	public function reverseTransform($idsString) {
		if (!$idsString) {
			return array();
		}

		$schools = array();
		$idsStrings = preg_split("/[,]+/", $idsString);
		$repository = $this->om->getRepository(School::class);
		foreach ($idsStrings as $idString) {
			$id = intval($idString);
			if ($id == 0) {
				continue;
			}
			$school = $repository->find($id);
			if (is_null($school)) {
				throw new TransformationFailedException();
			}
			$schools[] = $school;
		}

		return $schools;
	}

}