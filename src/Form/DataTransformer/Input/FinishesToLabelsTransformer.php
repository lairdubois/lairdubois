<?php

namespace App\Form\DataTransformer\Input;

use App\Entity\Input\Finish;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

class FinishesToLabelsTransformer implements DataTransformerInterface {

	private $om;

	public function __construct(EntityManagerInterface $om) {
		$this->om = $om;
	}

	public function transform($finishes) {
		if (null === $finishes) {
			return '';
		}

		if (!$finishes instanceof \Doctrine\Common\Collections\Collection) {
			throw new UnexpectedTypeException($finishes, '\Doctrine\Common\Collections\Collection');
		}

		$labelsArray = array();
		foreach ($finishes as $finish) {
			$labelsArray[] = $finish->getLabel();
		}
		return implode(',', $labelsArray);
	}

	public function reverseTransform($labelsString) {
		if (!$labelsString) {
			return array();
		}
		$labelsString = htmlspecialchars_decode($labelsString, ENT_QUOTES);

		$finishes = array();
		$labelsArray = preg_split("/[,;]+/", $labelsString);
		$repository = $this->om->getRepository(Finish::class);
		foreach ($labelsArray as $label) {
			if (!preg_match("/^[ a-zA-Z0-9ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ'-]{2,}$/", $label)) {
				continue;
			}
			$label = ucfirst(strtolower(trim($label)));
			if (strlen($label) == 0) {
				continue;
			}
			$finish = $repository->findOneByLabel($label);
			if (is_null($finish)) {
				$finish = new Finish();
				$finish->setLabel($label);
			} elseif (in_array($finish, $finishes)) {
				continue;
			}
			$finishes[] = $finish;
		}

		return $finishes;
	}

}