<?php

namespace App\Entity\Knowledge\Value;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints as LadbAssert;

/**
 * @ORM\Table("tbl_knowledge2_value_sign")
 * @ORM\Entity(repositoryClass="App\Repository\Knowledge\Value\SignRepository")
 * @ladbAssert\ValidSignValue()
 */
class Sign extends BaseValue {

	const CLASS_NAME = 'App\Entity\Knowledge\Value\Sign';
	const TYPE = 16;

	const TYPE_STRIPPED_NAME = 'sign';

	/**
	 * @ORM\Column(type="string", length=100)
	 */
	protected $data;

	/**
	 * @ORM\Column(type="string", length=100)
	 * @Assert\NotBlank(groups={"mandatory"})
	 * @Assert\Length(max=100)
	 * @Assert\Regex("/^[ a-zA-Z0-9ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ\-.'’’&]+$/")
	 */
	private $brand;

	/**
	 * @ORM\Column(type="boolean", name="is_affiliate")
	 */
	private $isAffiliate = false;

	/**
	 * @ORM\Column(type="string", length=100, nullable=true)
	 * @Assert\Length(max=100)
	 * @Assert\Regex("/^[ a-zA-Z0-9ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ\-.'’’&]+$/")
	 */
	private $store;

	/////

	// Type /////

	public function getType() {
		return self::TYPE;
	}

	// Title /////

	public function getTitle() {
		return $this->getData();
	}

	// Brand /////

	public function setBrand($brand) {
		$this->brand = $brand;
		return $this;
	}

	public function getBrand() {
		return $this->brand;
	}

	// IsAffiliate /////

	public function setIsAffiliate($isAffiliate) {
		$this->isAffiliate = $isAffiliate;
		return $this;
	}

	public function getIsAffiliate() {
		return $this->isAffiliate;
	}

	// Store /////

	public function setStore($store) {
		$this->store = $store;
		return $this;
	}

	public function getStore() {
		return $this->store;
	}

}