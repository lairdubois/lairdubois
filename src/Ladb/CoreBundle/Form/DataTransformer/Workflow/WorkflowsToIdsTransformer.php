<?php

namespace Ladb\CoreBundle\Form\DataTransformer\Workflow;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Doctrine\Common\Persistence\ObjectManager;
use Ladb\CoreBundle\Entity\Workflow\Workflow;

class WorkflowsToIdsTransformer implements DataTransformerInterface {

	private $om;

	public function __construct(ObjectManager $om) {
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
		$repository = $this->om->getRepository( Workflow::CLASS_NAME);
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