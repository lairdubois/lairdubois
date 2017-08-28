<?php

namespace Ladb\CoreBundle\Entity\Knowledge;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Ladb\CoreBundle\Model\LocalisableInterface;
use Ladb\CoreBundle\Model\LocalisableTrait;
use Ladb\CoreBundle\Entity\Knowledge\Value\Url;
use Ladb\CoreBundle\Entity\Knowledge\Value\Text;
use Ladb\CoreBundle\Entity\Knowledge\Value\Longtext;
use Ladb\CoreBundle\Entity\Knowledge\Value\Sign;
use Ladb\CoreBundle\Entity\Knowledge\Value\Integer;
use Ladb\CoreBundle\Entity\Knowledge\Value\Picture;
use Ladb\CoreBundle\Entity\Knowledge\Value\Location;
use Ladb\CoreBundle\Entity\Knowledge\Value\Phone;

/**
 * Ladb\CoreBundle\Entity\Knowledge\School
 *
 * @ORM\Table("tbl_knowledge2_school")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Knowledge\SchoolRepository")
 */
class School extends AbstractKnowledge implements LocalisableInterface {

	use LocalisableTrait;

	const CLASS_NAME = 'LadbCoreBundle:Knowledge\School';
	const TYPE = 115;

	const STRIPPED_NAME = 'school';

	const FIELD_NAME = 'name';
	const FIELD_LOGO = 'logo';
	const FIELD_PHOTO = 'photo';
	const FIELD_WEBSITE = 'website';
	const FIELD_ADDRESS = 'address';
	const FIELD_PHONE = 'phone';
	const FIELD_DESCRIPTION = 'description';
	const FIELD_PUBLIC = 'public';
	const FIELD_DIPLOMAS = 'diplomas';
	const FIELD_TRAINING_TYPES  = 'training_types';

	public static $FIELD_DEFS = array(
		School::FIELD_NAME           => array(School::ATTRIB_TYPE => Text::TYPE_STRIPPED_NAME, School::ATTRIB_MULTIPLE => false, School::ATTRIB_MANDATORY => true, School::ATTRIB_CONSTRAINTS => array(array('\\Ladb\\CoreBundle\\Validator\\Constraints\\UniqueWood', array('excludedId' => '@getId'))), School::ATTRIB_DATA_CONSTRAINTS => array(array('\\Ladb\\CoreBundle\\Validator\\Constraints\\OneThing', array('message' => 'N\'indiquez qu\'un seul Nom français par proposition.')))),
		School::FIELD_LOGO           => array(School::ATTRIB_TYPE => Picture::TYPE_STRIPPED_NAME, School::ATTRIB_MULTIPLE => false, School::ATTRIB_MANDATORY => true),
		School::FIELD_PHOTO          => array(School::ATTRIB_TYPE => Picture::TYPE_STRIPPED_NAME, School::ATTRIB_MULTIPLE => false),
		School::FIELD_WEBSITE        => array(School::ATTRIB_TYPE => Url::TYPE_STRIPPED_NAME, School::ATTRIB_MULTIPLE => false),
		School::FIELD_ADDRESS        => array(School::ATTRIB_TYPE => Location::TYPE_STRIPPED_NAME, School::ATTRIB_MULTIPLE => false, School::ATTRIB_LINKED_FIELDS => array('latitude', 'longitude', 'geographicalAreas', 'postalCode', 'locality', 'country')),
		School::FIELD_PHONE          => array(School::ATTRIB_TYPE => Phone::TYPE_STRIPPED_NAME, School::ATTRIB_MULTIPLE => false),
		School::FIELD_DESCRIPTION    => array(School::ATTRIB_TYPE => Longtext::TYPE_STRIPPED_NAME, School::ATTRIB_MULTIPLE => false),
		School::FIELD_PUBLIC         => array(School::ATTRIB_TYPE => Integer::TYPE_STRIPPED_NAME, School::ATTRIB_MULTIPLE => false, School::ATTRIB_CHOICES => array(1 => 'Oui', 0 => 'Non')),
		School::FIELD_DIPLOMAS       => array(School::ATTRIB_TYPE => Text::TYPE_STRIPPED_NAME, School::ATTRIB_MULTIPLE => true, School::ATTRIB_FILTER_QUERY => '@diplomas:"%q%"', Wood::ATTRIB_DATA_CONSTRAINTS => array(array('\\Ladb\\CoreBundle\\Validator\\Constraints\\OneThing', array('message' => 'N\'indiquez qu\'un seul diplôme par proposition.')))),
		School::FIELD_TRAINING_TYPES => array(School::ATTRIB_TYPE => Integer::TYPE_STRIPPED_NAME, School::ATTRIB_MULTIPLE => true, School::ATTRIB_CHOICES => array(0 => 'Continue', 1 => 'Alternance', 2 => 'Apprentissage', 4 => 'Professionnelle'), School::ATTRIB_USE_CHOICES_VALUE => true, School::ATTRIB_FILTER_QUERY => '@products:"%q%"'),
	);

	/**
	 * @ORM\Column(type="string", nullable=true, length=100)
	 */
	private $name;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Text", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_school_value_name")
	 * @ORM\OrderBy({"voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $nameValues;

	/**
	 * @ORM\Column(type="boolean", nullable=false, name="name_rejected")
	 */
	private $nameRejected = false;


	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Picture", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_school_value_logo")
	 * @ORM\OrderBy({"voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $logoValues;

	/**
	 * @ORM\Column(type="boolean", nullable=false, name="logo_rejected")
	 */
	private $logoRejected = false;


	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\Picture", cascade={"persist"})
	 * @ORM\JoinColumn(name="photo_id", nullable=true)
	 * @Assert\Type(type="Ladb\CoreBundle\Entity\Core\Picture")
	 */
	private $photo;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Picture", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_school_value_photo")
	 * @ORM\OrderBy({"voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $photoValues;


	/**
	 * @ORM\Column(type="string", nullable=true, length=255)
	 */
	private $website;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Url", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_school_value_website")
	 * @ORM\OrderBy({"voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $websiteValues;


	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	private $address;

	/**
	 * @ORM\Column(type="float", nullable=true)
	 */
	private $latitude;

	/**
	 * @ORM\Column(type="float", nullable=true)
	 */
	private $longitude;

	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	private $geographicalAreas;

	/**
	 * @ORM\Column(type="string", nullable=true, length=20)
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
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Location", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_school_value_address")
	 * @ORM\OrderBy({"voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $addressValues;


	/**
	 * @ORM\Column(type="string", nullable=true, length=20)
	 */
	private $phone;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Phone", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_school_value_phone")
	 * @ORM\OrderBy({"voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $phoneValues;


	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	private $description;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Longtext", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_school_value_description")
	 * @ORM\OrderBy({"voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $descriptionValues;


	/**
	 * @ORM\Column(type="boolean", nullable=true)
	 */
	private $public;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Integer", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_school_value_public")
	 * @ORM\OrderBy({"voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $publicValues;


	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	private $diplomas;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Text", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_school_value_diplomas")
	 * @ORM\OrderBy({"voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $diplomasValues;


	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	private $trainingTypes;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Integer", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_school_value_training_types")
	 * @ORM\OrderBy({"voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $trainingTypesValues;


	/////

	public function __construct() {
		$this->nameValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->logoValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->photoValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->websiteValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->addressValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->phoneValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->publicValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->diplomasValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->trainingTypesValues = new \Doctrine\Common\Collections\ArrayCollection();
	}

	/////

	// IsRejected /////

	public function getIsRejected() {
		return $this->getNameRejected() || $this->getLogoRejected();
	}

	// Type /////

	public function getType() {
		return School::TYPE;
	}

	// Body /////

	public function getBody() {
		if (!empty($this->getDescription())) {
			return $this->getDescription();
		}
		$terms = array($this->getName());
		return implode($terms, ',');
	}

	// StrippedName /////

	public function getStrippedName() {
		return School::STRIPPED_NAME;
	}

	// FieldDefs /////

	public function getFieldDefs() {
		return School::$FIELD_DEFS;
	}

	// Name /////

	public function setName($name) {
		$this->name = $name;
		if (!is_null($name)) {
			$this->setTitle(explode(',', $name)[0]);
		} else {
			$this->setTitle(null);
		}
		return $this;
	}

	public function getName() {
		return $this->name;
	}

	// NameValues /////

	public function addNameValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Text $nameValue) {
		if (!$this->nameValues->contains($nameValue)) {
			$this->nameValues[] = $nameValue;
		}
		return $this;
	}

	public function removeNameValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Text $nameValue) {
		$this->nameValues->removeElement($nameValue);
	}

	public function setNameValues($nameValues) {
		$this->nameValues = $nameValues;
	}

	public function getNameValues() {
		return $this->nameValues;
	}

	// NameRejected /////

	public function setNameRejected($nameRejected) {
		$this->nameRejected = $nameRejected;
		return $this;
	}

	public function getNameRejected() {
		return $this->nameRejected;
	}

	// Logo /////

	public function setLogo($logo) {
		return $this->setMainPicture($logo);
	}

	public function getLogo() {
		return $this->getMainPicture();
	}

	// LogoValues /////

	public function addLogoValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Picture $logoValue) {
		if (!$this->logoValues->contains($logoValue)) {
			$this->logoValues[] = $logoValue;
		}
		return $this;
	}

	public function removeLogoValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Picture $logoValue) {
		$this->logoValues->removeElement($logoValue);
	}

	public function setLogoValues($logoValues) {
		$this->logoValues = $logoValues;
	}

	public function getLogoValues() {
		return $this->logoValues;
	}

	// LogoRejected /////

	public function setLogoRejected($logoRejected) {
		$this->logoRejected = $logoRejected;
		return $this;
	}

	public function getLogoRejected() {
		return $this->logoRejected;
	}

	// Photo /////

	public function setPhoto($photo) {
		return $this->photo = $photo;
	}

	public function getPhoto() {
		return $this->photo;
	}

	// PhotoValues /////

	public function addPhotoValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Picture $photoValue) {
		if (!$this->photoValues->contains($photoValue)) {
			$this->photoValues[] = $photoValue;
		}
		return $this;
	}

	public function removePhotoValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Picture $photoValue) {
		$this->photoValues->removeElement($photoValue);
	}

	public function setPhotoValues($photoValues) {
		$this->photoValues = $photoValues;
	}

	public function getPhotoValues() {
		return $this->photoValues;
	}

	// Website /////

	public function setWebsite($website) {
		$this->website = $website;
		return $this;
	}

	public function getWebsite() {
		return $this->website;
	}

	// WebsiteValues /////

	public function addWebsiteValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Url $websiteValue) {
		if (!$this->websiteValues->contains($websiteValue)) {
			$this->websiteValues[] = $websiteValue;
		}
		return $this;
	}

	public function removeWebsiteValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Url $websiteValue) {
		$this->websiteValues->removeElement($websiteValue);
	}

	public function setWebsiteValues($websiteValues) {
		$this->websiteValues = $websiteValues;
	}

	public function getWebsiteValues() {
		return $this->websiteValues;
	}

	// Address /////

	public function setAddress($address) {
		$this->address = $address;
		return $this;
	}

	public function getAddress() {
		return $this->address;
	}

	// Location /////

	public function setLocation($location) {
		return $this->setAddress($location);
	}

	public function getLocation() {
		return $this->getAddress();
	}

	// GeographicalAreas /////

	public function setGeographicalAreas($geographicalAreas = null) {
		$this->geographicalAreas = $geographicalAreas;
		return $this;
	}

	public function getGeographicalAreas() {
		return $this->geographicalAreas;
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

	// AddressValues /////

	public function addAddressValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Location $addressValue) {
		if (!$this->addressValues->contains($addressValue)) {
			$this->addressValues[] = $addressValue;
		}
		return $this;
	}

	public function removeAddressValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Location $addressValue) {
		$this->addressValues->removeElement($addressValue);
	}

	public function setAddressValues($addressValues) {
		$this->addressValues = $addressValues;
	}

	public function getAddressValues() {
		return $this->addressValues;
	}

	// Phone /////

	public function setPhone($phone) {
		$this->phone = $phone;
		return $this;
	}

	public function getPhone() {
		return $this->phone;
	}

	// PhoneValues /////

	public function addPhoneValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Phone $phoneValue) {
		if (!$this->phoneValues->contains($phoneValue)) {
			$this->phoneValues[] = $phoneValue;
		}
		return $this;
	}

	public function removePhoneValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Phone $phoneValue) {
		$this->phoneValues->removeElement($phoneValue);
	}

	public function setPhoneValues($phoneValues) {
		$this->phoneValues = $phoneValues;
	}

	public function getPhoneValues() {
		return $this->phoneValues;
	}

	// Description /////

	public function setDescription($description) {
		$this->description = $description;
		return $this;
	}

	public function getDescription() {
		return $this->description;
	}

	// DescriptionValues /////

	public function addDescriptionValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Longtext $descriptionValue) {
		if (!$this->descriptionValues->contains($descriptionValue)) {
			$this->descriptionValues[] = $descriptionValue;
		}
		return $this;
	}

	public function removeDescriptionValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Longtext $descriptionValue) {
		$this->descriptionValues->removeElement($descriptionValue);
	}

	public function setDescriptionValues($descriptionValues) {
		$this->descriptionValues = $descriptionValues;
	}

	public function getDescriptionValues() {
		return $this->descriptionValues;
	}

	// Public /////

	public function setPublic($public) {
		$this->public = $public;
		return $this;
	}

	public function getPublic() {
		return $this->public;
	}

	// PublicValues /////

	public function addPublicValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Integer $publicValue) {
		if (!$this->publicValues->contains($publicValue)) {
			$this->publicValues[] = $publicValue;
		}
		return $this;
	}

	public function removePublicValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Integer $publicValue) {
		$this->publicValues->removeElement($publicValue);
	}

	public function setPublicValues($publicValues) {
		$this->publicValues = $publicValues;
	}

	public function getPublicValues() {
		return $this->publicValues;
	}

	// Diplomas /////

	public function setDiplomas($diplomas) {
		$this->diplomas = $diplomas;
		return $this;
	}

	public function getDiplomas() {
		return $this->diplomas;
	}

	// DiplomasValues /////

	public function addDiplomasValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Text $diplomasValue) {
		if (!$this->diplomasValues->contains($diplomasValue)) {
			$this->diplomasValues[] = $diplomasValue;
		}
		return $this;
	}

	public function removeDiplomasValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Text $diplomasValue) {
		$this->diplomasValues->removeElement($diplomasValue);
	}

	public function setDiplomasValues($diplomasValues) {
		$this->diplomasValues = $diplomasValues;
	}

	public function getDiplomasValues() {
		return $this->diplomasValues;
	}

	// TrainingTypes /////

	public function setTrainingTypes($trainingTypes) {
		$this->trainingTypes = $trainingTypes;
		return $this;
	}

	public function getTrainingTypes() {
		return $this->trainingTypes;
	}

	// TrainingTypesValues /////

	public function addTrainingTypesValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Integer $trainingTypesValue) {
		if (!$this->trainingTypesValues->contains($trainingTypesValue)) {
			$this->trainingTypesValues[] = $trainingTypesValue;
		}
		return $this;
	}

	public function removeTrainingTypesValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Integer $trainingTypesValue) {
		$this->trainingTypesValues->removeElement($trainingTypesValue);
	}

	public function setTrainingTypesValues($trainingTypesValues) {
		$this->trainingTypesValues = $trainingTypesValues;
	}

	public function getTrainingTypesValues() {
		return $this->trainingTypesValues;
	}

}