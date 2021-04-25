<?php

namespace Ladb\CoreBundle\Entity\Knowledge\Value;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ladb\CoreBundle\Model\LocalisableExtendedTrait;
use Ladb\CoreBundle\Model\LocalisableTrait;
use Symfony\Component\Validator\Constraints as Assert;
use Ladb\CoreBundle\Validator\Constraints as LadbAssert;
use Ladb\CoreBundle\Model\LocalisableExtendedInterface;

/**
 * @ORM\Table("tbl_knowledge2_value_location")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Knowledge\Value\LocationRepository")
 * @ladbAssert\ValidLocationValue()
 */
class Location extends BaseValue implements LocalisableExtendedInterface {

	use LocalisableTrait, LocalisableExtendedTrait;

	const CLASS_NAME = 'LadbCoreBundle:Knowledge\Value\Location';
	const TYPE = 14;

	const TYPE_STRIPPED_NAME = 'location';

	/**
	 * @ORM\Column(type="string", length=255, nullable=false)
	 */
	protected $data;

	/**
	 * @ORM\Column(type="string", length=255, nullable=false)
	 * @Assert\NotBlank(groups={"mandatory"})
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

	// FormattedAddress /////

	public function setFormattedAddress($formattedAddress = null) {
		return $this->setData($formattedAddress);
	}

	public function getFormattedAddress() {
		return $this->getData();
	}

}