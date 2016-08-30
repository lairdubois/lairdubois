<?php

namespace Ladb\CoreBundle\Entity\Find\Content;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Ladb\CoreBundle\Model\MultiPicturedInterface;
use Ladb\CoreBundle\Model\LocalisableInterface;

/**
 * @ORM\Table("tbl_find_content_gallery")
 * @ORM\Entity
 */
class Gallery extends AbstractContent implements MultiPicturedInterface, LocalisableInterface {

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Picture", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_find_data_gallery_picture")
	 * @ORM\OrderBy({"sortIndex" = "ASC"})
	 * @Assert\Count(min=1, max=5, groups={"gallery"})
	 */
	private $pictures;

	/**
	 * @ORM\Column(type="string", length=100, nullable=false)
	 * @Assert\NotBlank(groups={"gallery"})
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

	// Pictures /////

	public function addPicture(\Ladb\CoreBundle\Entity\Picture $picture) {
		if (!$this->pictures->contains($picture)) {
			$this->pictures[] = $picture;
		}
		return $this;
	}

	public function removePicture(\Ladb\CoreBundle\Entity\Picture $picture) {
		$this->pictures->removeElement($picture);
	}

	public function getPictures() {
		return $this->pictures;
	}

	public function resetPictures() {
		$this->pictures->clear();
	}

	// Location /////

	public function setLocation($location) {
		$this->location = $location;
		return $this;
	}

	public function getLocation() {
		return $this->location;
	}

	// Latitude /////

	public function setLatitude($latitude = null) {
		$this->latitude = $latitude;
	}

	public function getLatitude() {
		return $this->latitude;
	}

	// Longitude /////

	public function setLongitude($longitude = null) {
		$this->longitude = $longitude;
	}

	public function getLongitude() {
		return $this->longitude;
	}

	// GeoPoint /////

	public function getGeoPoint() {
		if (!is_null($this->latitude) && !is_null($this->longitude)) {
			return array( $this->longitude, $this->latitude );
		}
		return null;
	}

}