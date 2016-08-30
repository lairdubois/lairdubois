<?php

namespace Ladb\CoreBundle\Entity\Knowledge\Value;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Ladb\CoreBundle\Validator\Constraints as LadbAssert;
use Ladb\CoreBundle\Model\LocalisableExtendedInterface;

/**
 * @ORM\Table("tbl_knowledge2_value_location")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Knowledge\Value\LocationRepository")
 * @ladbAssert\ValidLocationValue()
 */
class Location extends BaseValue implements LocalisableExtendedInterface {

	const CLASS_NAME = 'LadbCoreBundle:Knowledge\Value\Location';
	const TYPE = 14;

	const TYPE_STRIPPED_NAME = 'location';

	/**
	 * @ORM\Column(type="string", length=255, nullable=false)
	 */
	protected $data;

	/**
	 * @ORM\Column(type="string", length=255, nullable=false)
	 * @Assert\NotBlank
	 * @Assert\Length(max=255)
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

	/**
	 * @ORM\Column(type="string", nullable=true)
	 */
	private $postalCode;

	/**
	 * @ORM\Column(type="string", nullable=true)
	 */
	private $locality;

	/**
	 * @ORM\Column(type="string", nullable=true)
	 */
	private $country;

	/**
	 * @ORM\Column(type="string", nullable=true)
	 */
	private $geographicalAreas;

	/////

	// Type /////

	public function getType() {
		return self::TYPE;
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
		return $this;
	}

	public function getLatitude() {
		return $this->latitude;
	}

	// Longitude /////

	public function setLongitude($longitude = null) {
		$this->longitude = $longitude;
		return $this;
	}

	public function getLongitude() {
		return $this->longitude;
	}

	// PostalCode /////

	public function setPostalCode($postalCode = null) {
		$this->postalCode = $postalCode;
		return $this;
	}

	public function getPostalCode() {
		return $this->postalCode;
	}

	// Locality /////

	public function setLocality($locality = null) {
		$this->locality = $locality;
		return $this;
	}

	public function getLocality() {
		return $this->locality;
	}

	// Country /////

	public function setCountry($country = null) {
		$this->country = $country;
		return $this;
	}

	public function getCountry() {
		return $this->country;
	}

	// GeographicalAreas /////

	public function setGeographicalAreas($geographicalAreas = null) {
		$this->geographicalAreas = $geographicalAreas;
		return $this;
	}

	public function getGeographicalAreas() {
		return $this->geographicalAreas;
	}

	// FormattedAddress /////

	public function setFormattedAddress($formattedAddress = null) {
		return $this->setData($formattedAddress);
	}

	public function getFormattedAddress() {
		return $this->getData();
	}

}