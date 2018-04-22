<?php

namespace Ladb\CoreBundle\Entity\Knowledge\Value;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Ladb\CoreBundle\Validator\Constraints as LadbAssert;

/**
 * @ORM\Table("tbl_knowledge2_value_price")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Knowledge\Value\PriceRepository")
 */
class Price extends BaseValue {

	const CLASS_NAME = 'LadbCoreBundle:Knowledge\Value\Price';
	const TYPE = 20;

	const TYPE_STRIPPED_NAME = 'price';

	/**
	 * @ORM\Column(type="string", length=20)
	 */
	protected $data;

	/**
	 * @ORM\Column(type="float")
	 * @Assert\NotBlank
	 */
	protected $rawPrice;

	/**
	 * @ORM\Column(type="string", length=3)
	 * @Assert\NotBlank
	 */
	protected $currency = 'EUR';

	/////

	// Type /////

	public function getType() {
		return self::TYPE;
	}

	// RawPrice /////

	public function getRawPrice() {
		return $this->rawPrice;
	}

	public function setRawPrice($rawPrice) {
		$this->rawPrice = $rawPrice;
		return $this;
	}

	// Currency /////

	public function getCurrency() {
		return $this->currency;
	}

	public function setCurrency($currency) {
		$this->currency = $currency;
		return $this;
	}

}