<?php

namespace Ladb\CoreBundle\Form\DataTransformer\Howto;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Doctrine\Common\Persistence\ObjectManager;

class HowtosToIdsTransformer implements DataTransformerInterface {

	private $om;

	public function __construct(ObjectManager $om) {
		$this->om = $om;
	}

	public function transform($howtos) {
		if (null === $howtos) {
			return '';
		}

		if (!$howtos instanceof \Doctrine\Common\Collections\Collection) {
			throw new UnexpectedTypeException($howtos, '\Doctrine\Common\Collections\Collection');
		}

		$idsArray = array();
		foreach ($howtos as $howto) {
			$idsArray[] = $howto->getId();
		}
		return implode(',', $idsArray);
	}

	public function reverseTransform($idsString) {
		if (!$idsString) {
			return array();
		}

		$howtos = array();
		$idsStrings = preg_split("/[,]+/", $idsString);
		$repository = $this->om->getRepository('LadbCoreBundle:Howto\Howto');
		foreach ($idsStrings as $idString) {
			$id = intval($idString);
			if ($id == 0) {
				continue;
			}
			$howto =$repository->find($id);
			if (is_null($howto)) {
				throw new TransformationFailedException();
			}
			$howtos[] = $howto;
		}

		return $howtos;
	}

}