<?php

namespace Ladb\CoreBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Doctrine\Common\Persistence\ObjectManager;

class ResourceToIdTransformer implements DataTransformerInterface {

	private $om;

	public function __construct(ObjectManager $om) {
		$this->om = $om;
	}

	public function transform($resource) {
		if (null === $resource) {
			return '';
		}

		if (!$resource instanceof \Ladb\CoreBundle\Entity\Resource) {
			throw new UnexpectedTypeException($resource, '\Ladb\CoreBundle\Entity\Resource');
		}

		return $resource->getId();
	}

	public function reverseTransform($idString) {
		if (!$idString) {
			return null;
		}

		$id = intval($idString);
		if ($id == 0) {
			throw new TransformationFailedException();
		}
		$resource = $this->om
			->getRepository('LadbCoreBundle:Resource')
			->find($id);
		if (is_null($resource)) {
			throw new TransformationFailedException();
		}

		return $resource;
	}

}