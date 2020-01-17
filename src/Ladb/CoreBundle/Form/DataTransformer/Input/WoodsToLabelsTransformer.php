<?php

namespace Ladb\CoreBundle\Form\DataTransformer\Input;

use Ladb\CoreBundle\Entity\Input\Wood;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Doctrine\Common\Persistence\ObjectManager;

class WoodsToLabelsTransformer implements DataTransformerInterface {

	private $om;

	public function __construct(ObjectManager $om) {
		$this->om = $om;
	}

	public function transform($woods) {
		if (null === $woods) {
			return '';
		}

		if (!$woods instanceof \Doctrine\Common\Collections\Collection) {
			throw new UnexpectedTypeException($woods, '\Doctrine\Common\Collections\Collection');
		}

		$labelsArray = array();
		foreach ($woods as $wood) {
			$labelsArray[] = $wood->getLabel();
		}
		return implode(',', $labelsArray);
	}

	public function reverseTransform($labelsString) {
		if (!$labelsString) {
			return array();
		}
		$labelsString = htmlspecialchars_decode($labelsString, ENT_QUOTES);

		$woods = array();
		$labelsArray = preg_split("/[,;]+/", $labelsString);
		$repository = $this->om->getRepository('LadbCoreBundle:Input\Wood');
		foreach ($labelsArray as $label) {
			if (!preg_match("/^[ a-zA-Z0-9ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ'-]{2,}$/", $label)) {
				continue;
			}
			$label = ucfirst(strtolower(trim($label)));
			if (strlen($label) == 0) {
				continue;
			}
			$wood = $repository->findOneByLabel($label);
			if (is_null($wood)) {
				$wood = new Wood();
				$wood->setLabel($label);
			} elseif (in_array($wood, $woods)) {
				continue;
			}
			$woods[] = $wood;
		}

		return $woods;
	}

}