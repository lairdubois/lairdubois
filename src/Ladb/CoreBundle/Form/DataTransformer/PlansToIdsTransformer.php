<?php

namespace Ladb\CoreBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Doctrine\Common\Persistence\ObjectManager;

class PlansToIdsTransformer implements DataTransformerInterface {

	private $om;

	public function __construct(ObjectManager $om) {
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
		foreach ($plans as $plans) {
			$idsArray[] = $plans->getId();
		}
		return implode(',', $idsArray);
	}

	public function reverseTransform($idsString) {
		if (!$idsString) {
			return array();
		}

		$plans = array();
		$idsStrings = preg_split("/[,]+/", $idsString);
		$repository = $this->om->getRepository('LadbCoreBundle:Wonder\Plan');
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