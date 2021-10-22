<?php

namespace App\Entity\Find\Content;

use Doctrine\ORM\Mapping as ORM;
use App\Model\LocalisableTrait;
use App\Model\MultiPicturedTrait;
use Symfony\Component\Validator\Constraints as Assert;
use App\Model\MultiPicturedInterface;
use App\Model\LocalisableInterface;

/**
 * @ORM\Table("tbl_find_content_gallery")
 * @ORM\Entity
 */
class Gallery extends AbstractContent implements MultiPicturedInterface, LocalisableInterface {

	use MultiPicturedTrait, LocalisableTrait;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Core\Picture", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_find_data_gallery_picture")
	 * @ORM\OrderBy({"sortIndex" = "ASC"})
	 * @Assert\Count(min=1, max=5, groups={"gallery"})
	 */
	private $pictures;

	/**
	 * @ORM\Column(type="string", length=100, nullable=true)
	 */
	private $location;

	/**
	 * @ORM\Column(type="float", nullable=true)
	 */
	private $latitude;

	/**
	 * @ORM\Column(type="float", nullable=true)
	 */
	private $longitude;

	/////

	public function __construct() {
		$this->pictures = new \Doctrine\Common\Collections\ArrayCollection();
	}

}