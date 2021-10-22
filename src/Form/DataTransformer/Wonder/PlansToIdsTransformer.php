<?php

namespace App\Form\DataTransformer\Wonder;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Wonder\Plan;

class PlansToIdsTransformer implements DataTransformerInterface {

	private $om;

	public function __construct(ManagerRegistry $om) {
		$this->om = $om;
	}

	public function transform($plans) {
		if (null === $plans) {
			return '';
		}

		if (!$plans instanceof \Doctrine\Common\Collections\Collection) {
			throw new UnexpectedTypeException($plans, '\Doctrine\Common\Collections\Collection');
		}

		$idsArray = array();
		foreach ($plans as $plan) {
			$idsArray[] = $plan->getId();
		}
		return implode(',', $idsArray);
	}

	public function reverseTransform($idsString) {
		if (!$idsString) {
			return array();
		}

		$plans = array();
		$idsStrings = preg_split("/[,]+/", $idsString);
		$repository = $this->om->getRepository(Plan::CLASS_NAME);
		foreach ($idsStrings as $idString) {
			$id = intval($idString);
			if ($id == 0) {
				continue;
			}
			$plan = $repository->find($id);
			if (is_null($plan)) {
				throw new TransformationFailedException();
			}
			$plans[] = $plan;
		}

		return $plans;
	}

}