<?php

namespace App\Entity\Knowledge\Value;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_knowledge2_value_pdf")
 * @ORM\Entity(repositoryClass="App\Repository\Knowledge\Value\PdfRepository")
 */
class Pdf extends BaseValue {

	const TYPE = 26;

	const TYPE_STRIPPED_NAME = 'pdf';

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Core\Resource", cascade={"persist"})
	 * @ORM\JoinColumn(name="resource_id", nullable=false)
	 * @Assert\NotNull(groups={"mandatory"})
	 * @Assert\Type(type="App\Entity\Core\Resource")
	 */
	protected $data;

	/////

	// Type /////

	public function getType() {
		return self::TYPE;
	}

	// MainPicture /////

	public function getMainPicture() {
		return $this->getData();
	}

	/////

	// IsDisplayGrid /////

	public function getIsDisplayGrid() {
		return true;
	}

}