<?php

namespace App\Entity\Knowledge\Value;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_knowledge2_value_picture")
 * @ORM\Entity(repositoryClass="App\Repository\Knowledge\Value\PictureRepository")
 */
class Picture extends BaseValue {

	const TYPE = 12;

	const TYPE_STRIPPED_NAME = 'picture';

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Core\Picture", cascade={"persist"})
	 * @ORM\JoinColumn(name="picture_id", nullable=false)
	 * @Assert\NotNull(groups={"mandatory"})
	 * @Assert\Type(type="App\Entity\Core\Picture")
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