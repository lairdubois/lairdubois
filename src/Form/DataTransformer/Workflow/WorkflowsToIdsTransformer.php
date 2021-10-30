<?php

namespace App\Form\DataTransformer\Workflow;

use App\Entity\Workflow\Workflow;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

class WorkflowsToIdsTransformer implements DataTransformerInterface {

	private $om;

	public function __construct(EntityManagerInterface $om) {
		$this->om = $om;
	}

	public function transform($workflows) {
		if (null === $workflows) {
			return '';
		}

		if (!$workflows instanceof \Doctrine\Common\Collections\Collection) {
			throw new UnexpectedTypeException($workflows, '\Doctrine\Common\Collections\Collection');
		}

		$idsArray = array();
		foreach ($workflows as $workflow) {
			$idsArray[] = $workflow->getId();
		}
		return implode(',', $idsArray);
	}

	public function reverseTransform($idsString) {
		if (!$idsString) {
			return array();
		}

		$workflows = array();
		$idsStrings = preg_split("/[,]+/", $idsString);
		$repository = $this->om->getRepository(Workflow::class);
		foreach ($idsStrings as $idString) {
			$id = intval($idString);
			if ($id == 0) {
				continue;
			}
			$workflow = $repository->find($id);
			if (is_null($workflow)) {
				throw new TransformationFailedException();
			}
			$workflows[] = $workflow;
		}

		return $workflows;
	}

}