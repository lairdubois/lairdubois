<?php

namespace Ladb\CoreBundle\Form\DataTransformer\Input;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Doctrine\Common\Persistence\ObjectManager;
use Ladb\CoreBundle\Entity\Input\Skill;

class SkillsToLabelsTransformer implements DataTransformerInterface {

	private $om;

	public function __construct(ObjectManager $om) {
		$this->om = $om;
	}

	public function transform($skills) {
		if (null === $skills) {
			return '';
		}

		if (!$skills instanceof \Doctrine\Common\Collections\Collection) {
			throw new UnexpectedTypeException($skills, '\Doctrine\Common\Collections\Collection');
		}

		$labelsArray = array();
		foreach ($skills as $skill) {
			$labelsArray[] = $skill->getLabel();
		}
		return implode(',', $labelsArray);
	}

	public function reverseTransform($labelsString) {
		if (!$labelsString) {
			return array();
		}
		$labelsString = htmlspecialchars_decode($labelsString, ENT_QUOTES);

		$skills = array();
		$labelsArray = preg_split("/[,;]+/", $labelsString);
		$repository = $this->om->getRepository('LadbCoreBundle:Input\Skill');
		foreach ($labelsArray as $label) {
			if (!preg_match("/^[ a-zA-Z0-9ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ'-]{2,}$/", $label)) {
				continue;
			}
			$label = ucfirst(strtolower(trim($label)));
			if (strlen($label) == 0) {
				continue;
			}
			$skill = $repository->findOneByLabel($label);
			if (is_null($skill)) {
				$skill = new Skill();
				$skill->setLabel($label);
			} elseif (in_array($skill, $skills)) {
				continue;
			}
			$skills[] = $skill;
		}

		return $skills;
	}

}