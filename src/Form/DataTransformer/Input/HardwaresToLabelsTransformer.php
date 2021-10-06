<?php

namespace App\Form\DataTransformer\Input;

use App\Entity\Input\Hardware;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Doctrine\Persistence\ObjectManager;

class HardwaresToLabelsTransformer implements DataTransformerInterface {

	private $om;

	public function __construct(ObjectManager $om) {
		$this->om = $om;
	}

	public function transform($hardwares) {
		if (null === $hardwares) {
			return '';
		}

		if (!$hardwares instanceof \Doctrine\Common\Collections\Collection) {
			throw new UnexpectedTypeException($hardwares, '\Doctrine\Common\Collections\Collection');
		}

		$labelsArray = array();
		foreach ($hardwares as $hardware) {
			$labelsArray[] = $hardware->getLabel();
		}
		return implode(',', $labelsArray);
	}

	public function reverseTransform($labelsString) {
		if (!$labelsString) {
			return array();
		}
		$labelsString = htmlspecialchars_decode($labelsString, ENT_QUOTES);

		$hardwares = array();
		$labelsArray = preg_split("/[,;]+/", $labelsString);
		$repository = $this->om->getRepository('App\Entity\Input\Hardware');
		foreach ($labelsArray as $label) {
			if (!preg_match("/^[ a-zA-Z0-9ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ'-]{2,}$/", $label)) {
				continue;
			}
			$label = ucfirst(strtolower(trim($label)));
			if (strlen($label) == 0) {
				continue;
			}
			$hardware = $repository->findOneByLabel($label);
			if (is_null($hardware)) {
				$hardware = new Hardware();
				$hardware->setLabel($label);
			} elseif (in_array($hardware, $hardwares)) {
				continue;
			}
			$hardwares[] = $hardware;
		}

		return $hardwares;
	}

}