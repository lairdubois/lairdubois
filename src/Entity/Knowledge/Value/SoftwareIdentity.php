<?php

namespace App\Entity\Knowledge\Value;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints as LadbAssert;

/**
 * @ORM\Table("tbl_knowledge2_value_software_identity")
 * @ORM\Entity(repositoryClass="App\Repository\Knowledge\Value\SoftwareIdentityRepository")
 * @ladbAssert\ValidSoftwareIdentityValue()
 */
class SoftwareIdentity extends BaseValue {

	const CLASS_NAME = 'App\Entity\Knowledge\Value\SoftwareIdentity';
	const TYPE = 21;

	const TYPE_STRIPPED_NAME = 'software-identity';

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
	private $name;

	/**
	 * @ORM\Column(type="boolean", name="is_addon")
	 */
	private $isAddOn = false;

	/**
	 * @ORM\Column(type="string", length=100, nullable=true, name="host_software_name")
	 * @Assert\Length(max=100)
	 * @Assert\Regex("/^[ a-zA-Z0-9ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ\-.'’’&]+$/")
	 */
	private $hostSoftwareName;

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

	// IsAddOn /////

	public function setIsAddOn($isAddOn) {
		$this->isAddOn = $isAddOn;
		return $this;
	}

	public function getIsAddOn() {
		return $this->isAddOn;
	}

	// HostSoftwareName /////

	public function setHostSoftwareName($hostSoftwareName) {
		$this->hostSoftwareName = $hostSoftwareName;
		return $this;
	}

	public function getHostSoftwareName() {
		return $this->hostSoftwareName;
	}

}