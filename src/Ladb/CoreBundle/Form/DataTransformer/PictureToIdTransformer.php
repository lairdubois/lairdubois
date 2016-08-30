<?php

namespace Ladb\CoreBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Doctrine\Common\Persistence\ObjectManager;

class PictureToIdTransformer implements DataTransformerInterface {

	private $om;

	public function __construct(ObjectManager $om) {
		$this->om = $om;
	}

	public function transform($picture) {
		if (null === $picture) {
			return '';
		}

		if (!$picture instanceof \Ladb\CoreBundle\Entity\Picture) {
			throw new UnexpectedTypeException($picture, '\Ladb\CoreBundle\Entity\Picture');
		}

		return $picture->getId();
	}

	public function reverseTransform($idString) {
		if (!$idString) {
			return null;
		}

		$id = intval($idString);
		if ($id == 0) {
			throw new TransformationFailedException();
		}
		$picture = $this->om
			->getRepository('LadbCoreBundle:Picture')
			->find($id);
		if (is_null($picture)) {
			throw new TransformationFailedException();
		}

		return $picture;
	}

}