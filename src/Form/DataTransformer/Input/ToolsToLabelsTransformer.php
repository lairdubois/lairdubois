<?php

namespace App\Form\DataTransformer\Input;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Input\Tool;

class ToolsToLabelsTransformer implements DataTransformerInterface {

	private $om;

	public function __construct(ObjectManager $om) {
		$this->om = $om;
	}

	public function transform($tools) {
		if (null === $tools) {
			return '';
		}

		if (!$tools instanceof \Doctrine\Common\Collections\Collection) {
			throw new UnexpectedTypeException($tools, '\Doctrine\Common\Collections\Collection');
		}

		$labelsArray = array();
		foreach ($tools as $tool) {
			$labelsArray[] = $tool->getLabel();
		}
		return implode(',', $labelsArray);
	}

	public function reverseTransform($labelsString) {
		if (!$labelsString) {
			return array();
		}
		$labelsString = htmlspecialchars_decode($labelsString, ENT_QUOTES);

		$tools = array();
		$labelsArray = preg_split("/[,;]+/", $labelsString);
		$repository = $this->om->getRepository('App\Entity\Input\Tool');
		foreach ($labelsArray as $label) {
			if (!preg_match("/^[ a-zA-Z0-9ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ'-]{2,}$/", $label)) {
				continue;
			}
			$label = ucfirst(strtolower(trim($label)));
			if (strlen($label) == 0) {
				continue;
			}
			$tool = $repository->findOneByLabel($label);
			if (is_null($tool)) {
				$tool = new Tool();
				$tool->setLabel($label);
			} elseif (in_array($tool, $tools)) {
				continue;
			}
			$tools[] = $tool;
		}

		return $tools;
	}

}