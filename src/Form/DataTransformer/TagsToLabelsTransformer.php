<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Core\Tag;

class TagsToLabelsTransformer implements DataTransformerInterface {

	private $om;

	public function __construct(ObjectManager $om) {
		$this->om = $om;
	}

	/**
	 * Transforms an object (tag) to a string (label).
	 */
	public function transform($tags) {
		if (null === $tags) {
			return '';
		}

		if (!$tags instanceof \Doctrine\Common\Collections\Collection) {
			throw new UnexpectedTypeException($tags, '\Doctrine\Common\Collections\Collection');
		}

		$labelsArray = array();
		foreach ($tags as $tag) {
			$labelsArray[] = $tag->getLabel();
		}
		return implode(',', $labelsArray);
	}

	/**
	 * Transforms a string (label) to an object (tag).
	 */
	public function reverseTransform($labelsString) {
		if (!$labelsString) {
			return array();
		}
		$labelsString = htmlspecialchars_decode($labelsString, ENT_QUOTES);

		$tags = array();
		$labelsArray = preg_split("/[,;]+/", $labelsString);
		$repository = $this->om->getRepository(Tag::CLASS_NAME);
		foreach ($labelsArray as $label) {
			if (!preg_match("/^[ a-zA-Z0-9ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ'-]{2,}$/", $label)) {
				continue;
			}
			$label = \Gedmo\Sluggable\Util\Urlizer::urlize($label);
			if (strlen($label) == 0) {
				continue;
			}
			$tag = $repository->findOneByLabel($label);
			if (is_null($tag)) {
				$tag = new Tag();
				$tag->setLabel($label);
			} elseif (in_array($tag, $tags)) {
				continue;
			}
			$tags[] = $tag;
		}

		return $tags;
	}

}