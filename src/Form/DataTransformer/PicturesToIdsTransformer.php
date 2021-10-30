<?php

namespace App\Form\DataTransformer;

use App\Entity\Core\Picture;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

class PicturesToIdsTransformer implements DataTransformerInterface {

	private $om;

	public function __construct(EntityManagerInterface $om) {
		$this->om = $om;
	}

	public function transform($pictures) {
		if (null === $pictures) {
			return '';
		}

		if (!$pictures instanceof \Doctrine\Common\Collections\Collection) {
			throw new UnexpectedTypeException($pictures, '\Doctrine\Common\Collections\Collection');
		}

		$idsArray = array();
		foreach ($pictures as $pictures) {
			$idsArray[] = $pictures->getId();
		}
		return implode(',', $idsArray);
	}

	public function reverseTransform($idsString) {
		if (!$idsString) {
			return array();
		}

		$pictures = array();
		$idsStrings = preg_split("/[,]+/", $idsString);
		$repository = $this->om->getRepository(Picture::class);
		$sortIndex = 0;
		foreach ($idsStrings as $idString) {
			$id = intval($idString);
			if ($id == 0) {
				continue;
			}
			$picture = $repository->find($id);
			$picture->setSortIndex($sortIndex++);
			if (is_null($picture)) {
				throw new TransformationFailedException();
			}
			$pictures[] = $picture;
		}

		return $pictures;
	}

}