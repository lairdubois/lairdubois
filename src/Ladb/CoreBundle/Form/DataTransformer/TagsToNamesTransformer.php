<?php

namespace Ladb\CoreBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Doctrine\Common\Persistence\ObjectManager;
use Ladb\CoreBundle\Entity\Tag;

class TagsToNamesTransformer implements DataTransformerInterface {

	private $om;

	public function __construct(ObjectManager $om) {
		$this->om = $om;
	}

	/**
	 * Transforms an object (tag) to a string (name).
	 */
	public function transform($tags) {
		if (null === $tags) {
			return '';
		}

		if (!$tags instanceof \Doctrine\Common\Collections\Collection) {
			throw new UnexpectedTypeException($tags, '\Doctrine\Common\Collections\Collection');
		}

		$namesArray = array();
		foreach ($tags as $tag) {
			$namesArray[] = $tag->getName();
		}
		return implode(',', $namesArray);
	}

	/**
	 * Transforms a string (name) to an object (tag).
	 */
	public function reverseTransform($labelsString) {
		if (!$labelsString) {
			return array();
		}
		$labelsString = htmlspecialchars_decode($labelsString, ENT_QUOTES);

		$tags = array();
		$namesArray = preg_split("/[,;]+/", $labelsString);
		$repository = $this->om->getRepository(Tag::CLASS_NAME);
		foreach ($namesArray as $name) {
			if (!preg_match("/^[ a-zA-Z0-9ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ-]{2,}$/", $name)) {
				continue;
			}
			$name = \Gedmo\Sluggable\Util\Urlizer::urlize($name);
			if (strlen($name) == 0) {
				continue;
			}
			$tag = $repository->findOneByName($name);
			if (is_null($tag)) {
				$tag = new Tag();
				$tag->setName($name);
			} elseif (in_array($tag, $tags)) {
				continue;
			}
			$tags[] = $tag;
		}

		return $tags;
	}

}