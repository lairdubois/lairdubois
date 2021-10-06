<?php

namespace App\Form\DataTransformer\Knowledge;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Knowledge\Provider;

class ProvidersToIdsTransformer implements DataTransformerInterface {

	private $om;

	public function __construct(ObjectManager $om) {
		$this->om = $om;
	}

	public function transform($providers) {
		if (null === $providers) {
			return '';
		}

		if (!$providers instanceof \Doctrine\Common\Collections\Collection) {
			throw new UnexpectedTypeException($providers, '\Doctrine\Common\Collections\Collection');
		}

		$idsArray = array();
		foreach ($providers as $provider) {
			$idsArray[] = $provider->getId();
		}
		return implode(',', $idsArray);
	}

	public function reverseTransform($idsString) {
		if (!$idsString) {
			return array();
		}

		$providers = array();
		$idsStrings = preg_split("/[,]+/", $idsString);
		$repository = $this->om->getRepository( Provider::CLASS_NAME);
		foreach ($idsStrings as $idString) {
			$id = intval($idString);
			if ($id == 0) {
				continue;
			}
			$provider = $repository->find($id);
			if (is_null($provider)) {
				throw new TransformationFailedException();
			}
			$providers[] = $provider;
		}

		return $providers;
	}

}