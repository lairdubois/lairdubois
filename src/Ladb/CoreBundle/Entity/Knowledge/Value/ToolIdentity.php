<?php

namespace Ladb\CoreBundle\Entity\Knowledge\Value;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Ladb\CoreBundle\Validator\Constraints as LadbAssert;

/**
 * @ORM\Table("tbl_knowledge2_value_tool_identity")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Knowledge\Value\ToolIdentityRepository")
 * @ladbAssert\ValidToolIdentityValue()
 */
class ToolIdentity extends BaseValue {

	const CLASS_NAME = 'LadbCoreBundle:Knowledge\Value\ToolIdentity';
	const TYPE = 26;

	const TYPE_STRIPPED_NAME = 'tool-identity';

	/**
	 * @ORM\Column(type="string", length=100)
	 */
	protected $data;

	/**
	 * @ORM\Column(type="string", length=100)
	 * @Assert\NotBlank
	 * @Assert\Length(max=100)
	 * @Assert\Regex("/^[ a-zA-Z0-9ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ'’’#,.:«»&+-]+$/")
	 */
	private $name;

	/**
	 * @ORM\Column(type="boolean", name="is_product")
	 */
	private $isProduct = false;

	/**
	 * @ORM\Column(type="string", length=100, nullable=true)
	 * @Assert\Length(max=100)
	 * @Assert\Regex("/^[ a-zA-Z0-9ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ'’’#,.:«»&+-]+$/")
	 */
	private $productName;

	/////

	// Type /////

	public function getType() {
		return self::TYPE;
	}

	// Title /////

	public function getTitle() {
		return $this->getData();
	}

	// Name /////

	public function setName($name) {
		$this->name = $name;
		return $this;
	}

	public function getName() {
		return $this->name;
	}

	// IsProduct /////

	public function setIsProduct($isProduct) {
		$this->isProduct = $isProduct;
		return $this;
	}

	public function getIsProduct() {
		return $this->isProduct;
	}

	// ProductName /////

	public function setProductName($productName) {
		$this->productName = $productName;
		return $this;
	}

	public function getProductName() {
		return $this->productName;
	}

}