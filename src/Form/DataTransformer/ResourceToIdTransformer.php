<?php

namespace App\Form\DataTransformer;

use App\Entity\Core\Resource;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

class ResourceToIdTransformer implements DataTransformerInterface {

	private $om;

	public function __construct(EntityManagerInterface $om) {
		$this->om = $om;
	}

	public function transform($resource) {
		if (null === $resource) {
			return '';
		}

		if (!$resource instanceof \App\Entity\Core\Resource) {
			throw new UnexpectedTypeException($resource, '\App\Entity\Core\Resource');
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
			->getRepository(Resource::class)
			->find($id);
		if (is_null($resource)) {
			throw new TransformationFailedException();
		}

		return $resource;
	}

}