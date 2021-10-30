<?php

namespace App\Form\DataTransformer\Qa;

use App\Entity\Qa\Question;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

class QuestionsToIdsTransformer implements DataTransformerInterface {

	private $om;

	public function __construct(EntityManagerInterface $om) {
		$this->om = $om;
	}

	public function transform($questions) {
		if (null === $questions) {
			return '';
		}

		if (!$questions instanceof \Doctrine\Common\Collections\Collection) {
			throw new UnexpectedTypeException($questions, '\Doctrine\Common\Collections\Collection');
		}

		$idsArray = array();
		foreach ($questions as $question) {
			$idsArray[] = $question->getId();
		}
		return implode(',', $idsArray);
	}

	public function reverseTransform($idsString) {
		if (!$idsString) {
			return array();
		}

		$questions = array();
		$idsStrings = preg_split("/[,]+/", $idsString);
		$repository = $this->om->getRepository(Question::class);
		foreach ($idsStrings as $idString) {
			$id = intval($idString);
			if ($id == 0) {
				continue;
			}
			$question = $repository->find($id);
			if (is_null($question)) {
				throw new TransformationFailedException();
			}
			$questions[] = $question;
		}

		return $questions;
	}

}