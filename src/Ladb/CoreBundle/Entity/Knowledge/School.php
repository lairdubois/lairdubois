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
	const FIELD_BIRTH_YEAR = 'birth_year';
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
		School::FIELD_BIRTH_YEAR     => array(School::ATTRIB_TYPE => Integer::TYPE_STRIPPED_NAME, School::ATTRIB_MULTIPLE => false),
		School::FIELD_DIPLOMAS       => array(School::ATTRIB_TYPE => Text::TYPE_STRIPPED_NAME, School::ATTRIB_MULTIPLE => true, School::ATTRIB_FILTER_QUERY => '@diplomas:"%q%"', Wood::ATTRIB_DATA_CONSTRAINTS => array(array('\\Ladb\\CoreBundle\\Validator\\Constraints\\OneThing', array('message' => 'N\'indiquez qu\'un seul diplôme par proposition.')))),
		School::FIELD_TRAINING_TYPES => array(School::ATTRIB_TYPE => Integer::TYPE_STRIPPED_NAME, School::ATTRIB_MULTIPLE => true, School::ATTRIB_CHOICES => array(0 => 'Continue', 1 => 'Alternance', 2 => 'Apprentissage', 4 => 'Professionnelle', 5 => 'Stage court'), School::ATTRIB_USE_CHOICES_VALUE => true, School::ATTRIB_FILTER_QUERY => '@training-types:"%q%"'),
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
	 * @ORM\Column(type="integer", nullable=true)
	 */
	private $birthYear;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Integer", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_school_value_birth_year")
	 * @ORM\OrderBy({"voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $birthYearValues;


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


	/**
	 * @ORM\Column(name="testimonial_count", type="integer")
	 */
	private $testimonialCount = 0;

	/**
	 * @ORM\OneToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\School\Testimonial", mappedBy="school", cascade={"all"})
	 * @ORM\OrderBy({"fromYear" = "DESC", "createdAt" = "DESC"})
	 */
	private $testimonials;

	/////

	public function __construct() {
		$this->nameValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->logoValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->photoValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->websiteValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->addressValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->phoneValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->publicValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->birthYearValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->diplomasValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->trainingTypesValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->testimonials = new \Doctrine\Common\Collections\ArrayCollection();
	}

	/////

	// IsRejected /////

	public function getIsRejected() {
		return $this->getNameRejected() || $this->getLogoRejected();
	}

	// Type /////

	public function getNameRejected() {
		return $this->nameRejected;
	}

	// Body /////

	public function setNameRejected($nameRejected) {
		$this->nameRejected = $nameRejected;
		return $this;
	}

	// StrippedName /////

	public function getLogoRejected() {
		return $this->logoRejected;
	}

	// FieldDefs /////

	public function setLogoRejected($logoRejected) {
		$this->logoRejected = $logoRejected;
		return $this;
	}

	// Name /////

	public function getType() {
		return School::TYPE;
	}

	public function getBody() {
		if (!empty($this->getDescription())) {
			return $this->getDescription();
		}
		$terms = array($this->getName());
		return implode($terms, ',');
	}

	// NameValues /////

	public function getDescription() {
		return $this->description;
	}

	public function setDescription($description) {
		$this->description = $description;
		return $this;
	}

	public function getName() {
		return $this->name;
	}

	public function setName($name) {
		$this->name = $name;
		if (!is_null($name)) {
			$this->setTitle(explode(',', $name)[0]);
		} else {
			$this->setTitle(null);
		}
		return $this;
	}

	// NameRejected /////

	public function getStrippedName() {
		return School::STRIPPED_NAME;
	}

	public function getFieldDefs() {
		return School::$FIELD_DEFS;
	}

	// Logo /////

	public function addNameValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Text $nameValue) {
		if (!$this->nameValues->contains($nameValue)) {
			$this->nameValues[] = $nameValue;
		}
		return $this;
	}

	public function removeNameValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Text $nameValue) {
		$this->nameValues->removeElement($nameValue);
	}

	// LogoValues /////

	public function getNameValues() {
		return $this->nameValues;
	}

	public function setNameValues($nameValues) {
		$this->nameValues = $nameValues;
	}

	public function setLogo($logo) {
		return $this->setMainPicture($logo);
	}

	public function getLogo() {
		return $this->getMainPicture();
	}

	// LogoRejected /////

	public function addLogoValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Picture $logoValue) {
		if (!$this->logoValues->contains($logoValue)) {
			$this->logoValues[] = $logoValue;
		}
		return $this;
	}

	public function removeLogoValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Picture $logoValue) {
		$this->logoValues->removeElement($logoValue);
	}

	// Photo /////

	public function getLogoValues() {
		return $this->logoValues;
	}

	public function setLogoValues($logoValues) {
		$this->logoValues = $logoValues;
	}

	// PhotoValues /////

	public function getPhoto() {
		return $this->photo;
	}

	public function setPhoto($photo) {
		return $this->photo = $photo;
	}

	public function addPhotoValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Picture $photoValue) {
		if (!$this->photoValues->contains($photoValue)) {
			$this->photoValues[] = $photoValue;
		}
		return $this;
	}

	public function removePhotoValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Picture $photoValue) {
		$this->photoValues->removeElement($photoValue);
	}

	// Website /////

	public function getPhotoValues() {
		return $this->photoValues;
	}

	public function setPhotoValues($photoValues) {
		$this->photoValues = $photoValues;
	}

	// WebsiteValues /////

	public function getWebsite() {
		return $this->website;
	}

	public function setWebsite($website) {
		$this->website = $website;
		return $this;
	}

	public function addWebsiteValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Url $websiteValue) {
		if (!$this->websiteValues->contains($websiteValue)) {
			$this->websiteValues[] = $websiteValue;
		}
		return $this;
	}

	public function removeWebsiteValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Url $websiteValue) {
		$this->websiteValues->removeElement($websiteValue);
	}

	// Address /////

	public function getWebsiteValues() {
		return $this->websiteValues;
	}

	public function setWebsiteValues($websiteValues) {
		$this->websiteValues = $websiteValues;
	}

	// Location /////

	public function setLocation($location) {
		return $this->setAddress($location);
	}

	public function getLocation() {
		return $this->getAddress();
	}

	// GeographicalAreas /////

	public function getAddress() {
		return $this->address;
	}

	public function setAddress($address) {
		$this->address = $address;
		return $this;
	}

	// PostalCode /////

	public function getGeographicalAreas() {
		return $this->geographicalAreas;
	}

	public function setGeographicalAreas($geographicalAreas = null) {
		$this->geographicalAreas = $geographicalAreas;
		return $this;
	}

	// Locality /////

	public function getPostalCode() {
		return $this->postalCode;
	}

	public function setPostalCode($postalCode = null) {
		$this->postalCode = $postalCode;
		return $this;
	}

	// Country /////

	public function getLocality() {
		return $this->locality;
	}

	public function setLocality($locality = null) {
		$this->locality = $locality;
		return $this;
	}

	// AddressValues /////

	public function getCountry() {
		return $this->country;
	}

	public function setCountry($country = null) {
		$this->country = $country;
		return $this;
	}

	public function addAddressValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Location $addressValue) {
		if (!$this->addressValues->contains($addressValue)) {
			$this->addressValues[] = $addressValue;
		}
		return $this;
	}

	public function removeAddressValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Location $addressValue) {
		$this->addressValues->removeElement($addressValue);
	}

	// Phone /////

	public function getAddressValues() {
		return $this->addressValues;
	}

	public function setAddressValues($addressValues) {
		$this->addressValues = $addressValues;
	}

	// PhoneValues /////

	public function getPhone() {
		return $this->phone;
	}

	public function setPhone($phone) {
		$this->phone = $phone;
		return $this;
	}

	public function addPhoneValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Phone $phoneValue) {
		if (!$this->phoneValues->contains($phoneValue)) {
			$this->phoneValues[] = $phoneValue;
		}
		return $this;
	}

	public function removePhoneValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Phone $phoneValue) {
		$this->phoneValues->removeElement($phoneValue);
	}

	// Description /////

	public function getPhoneValues() {
		return $this->phoneValues;
	}

	public function setPhoneValues($phoneValues) {
		$this->phoneValues = $phoneValues;
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

	public function getDescriptionValues() {
		return $this->descriptionValues;
	}

	public function setDescriptionValues($descriptionValues) {
		$this->descriptionValues = $descriptionValues;
	}

	// Public /////

	public function getPublic() {
		return $this->public;
	}

	public function setPublic($public) {
		$this->public = $public;
		return $this;
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

	public function getPublicValues() {
		return $this->publicValues;
	}

	public function setPublicValues($publicValues) {
		$this->publicValues = $publicValues;
	}

	// BirthYear /////

	public function getBirthYear() {
		return $this->birthYear;
	}

	public function setBirthYear($birthYear) {
		$this->birthYear = $birthYear;
		return $this;
	}

	// BirthYearValues /////

	public function addBirthYearValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Integer $birthYearValue) {
		if (!$this->birthYearValues->contains($birthYearValue)) {
			$this->birthYearValues[] = $birthYearValue;
		}
		return $this;
	}

	public function removeBirthYearValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Integer $birthYearValue) {
		$this->birthYearValues->removeElement($birthYearValue);
	}

	public function getBirthYearValues() {
		return $this->birthYearValues;
	}

	public function setBirthYearValues($birthYearValues) {
		$this->birthYearValues = $birthYearValues;
	}

	// Diplomas /////

	public function getDiplomas() {
		return $this->diplomas;
	}

	public function setDiplomas($diplomas) {
		$this->diplomas = $diplomas;
		return $this;
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

	public function getDiplomasValues() {
		return $this->diplomasValues;
	}

	public function setDiplomasValues($diplomasValues) {
		$this->diplomasValues = $diplomasValues;
	}

	// TrainingTypes /////

	public function getTrainingTypes() {
		return $this->trainingTypes;
	}

	public function setTrainingTypes($trainingTypes) {
		$this->trainingTypes = $trainingTypes;
		return $this;
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

	public function getTrainingTypesValues() {
		return $this->trainingTypesValues;
	}

	public function setTrainingTypesValues($trainingTypesValues) {
		$this->trainingTypesValues = $trainingTypesValues;
	}

	// TestimonialCount /////

	public function incrementTestimonialCount($by = 1) {
		return $this->testimonialCount += intval($by);
	}

	public function getTestimonialCount() {
		return $this->testimonialCount;
	}

	public function setTestimonialCount($testimonialCount) {
		$this->testimonialCount = $testimonialCount;
		return $this;
	}

	// Testimonials /////

	public function addTestimonial($testimonial) {
		if (!$this->testimonials->contains($testimonial)) {
			$this->testimonials[] = $testimonial;
		}
		return $this;
	}

	public function removeTestimonial($testimonial) {
		$this->testimonials->removeElement($testimonial);
	}

	public function getTestimonials() {
		return $this->testimonials;
	}

}