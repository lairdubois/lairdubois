<?php

namespace App\Entity\Core\Block;

use Doctrine\ORM\Mapping as ORM;
use App\Model\MultiPicturedTrait;
use Symfony\Component\Validator\Constraints as Assert;
use App\Model\MultiPicturedInterface;

/**
 * @ORM\Table("tbl_core_block_gallery")
 * @ORM\Entity
 */
class Gallery extends AbstractBlock implements MultiPicturedInterface {

	use MultiPicturedTrait;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Core\Picture", cascade={"persist"}, fetch="EAGER")
	 * @ORM\JoinTable(name="tbl_core_block_gallery_picture")
	 * @ORM\OrderBy({"sortIndex" = "ASC"})
	 * @Assert\Count(min=1, max=20)
	 */
	private $pictures;

	/////

	public function __construct() {
		$this->pictures = new \Doctrine\Common\Collections\ArrayCollection();
	}

	// StrippedName /////

	public function getStrippedName() {
		return 'gallery';
	}

}