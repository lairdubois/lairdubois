<?php

namespace Ladb\CoreBundle\Entity\Knowledge\Value;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_knowledge2_value_file_extension")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Knowledge\Value\FileExtensionRepository")
 */
class FileExtension extends BaseValue {

	const CLASS_NAME = 'LadbCoreBundle:Knowledge\Value\FileExtension';
	const TYPE = 22;

	const TYPE_STRIPPED_NAME = 'file-extension';

	/**
	 * @ORM\Column(type="string", length=10)
	 * @Assert\NotBlank(groups={"mandatory"})
	 * @Assert\Length(max=10)
	 * @Assert\Regex("/^[a-zA-Z0-9]+$/")
	 */
	protected $data;

	/**
	 * @ORM\Column(type="string", length=100, nullable=true)
	 * @Assert\Length(max=100)
	 */
	private $label;

	/////

	// Type /////

	public function getType() {
		return self::TYPE;
	}

	// Title /////

	public function getTitle() {
		return $this->getData();
	}

	// Label /////

	public function getLabel() {
		return $this->label;
	}

	public function setLabel($label) {
		$this->label = $label;
		return $this;
	}

}