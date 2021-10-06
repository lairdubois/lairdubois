<?php

namespace App\Entity\Knowledge;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use App\Model\LocalisableInterface;
use App\Model\LocalisableTrait;
use App\Entity\Knowledge\Value\Url;
use App\Entity\Knowledge\Value\Text;
use App\Entity\Knowledge\Value\Longtext;
use App\Entity\Knowledge\Value\Integer;
use App\Entity\Knowledge\Value\Picture;
use App\Entity\Knowledge\Value\Location;
use App\Entity\Knowledge\Value\Phone;
use App\Entity\Knowledge\Value\Video;

/**
 * App\Entity\Knowledge\School
 *
 * @ORM\Table("tbl_knowledge2_school")
 * @ORM\Entity(repositoryClass="App\Repository\Knowledge\SchoolRepository")
 */
class School extends AbstractKnowledge implements LocalisableInterface {

	use LocalisableTrait;

	const CLASS_NAME = 'App\Entity\Knowledge\School';
	const TYPE = 115;

	const STRIPPED_NAME = 'school';

	const FIELD_NAME = 'name';
	const FIELD_LOGO = 'logo';
	const FIELD_PHOTO = 'photo';
	const FIELD_WEBSITE = 'website';
	const FIELD_ADDRESS = 'address';
	const FIELD_PHONE = 'phone';
	const FIELD_DESCRIPTION = 'description';
	const FIELD_VIDEO = 'video';
	const FIELD_PUBLIC = 'public';
	const FIELD_BIRTH_YEAR = 'birth_year';
	const FIELD_DIPLOMAS = 'diplomas';
	const FIELD_TRAINING_TYPES  = 'training_types';

	public static $FIELD_DEFS = array(
		School::FIELD_NAME           => array(School::ATTRIB_TYPE => Text::TYPE_STRIPPED_NAME, School::ATTRIB_MULTIPLE => false, School::ATTRIB_MANDATORY => true, School::ATTRIB_CONSTRAINTS => array(array('App\\Validator\\Constraints\\UniqueSchool', array('excludedId' => '@getId')))),
		School::FIELD_LOGO           => array(School::ATTRIB_TYPE => Picture::TYPE_STRIPPED_NAME, School::ATTRIB_MULTIPLE => false, School::ATTRIB_MANDATORY => true, School::ATTRIB_QUALITY => \App\Entity\Core\Picture::QUALITY_LD, School::ATTRIB_POST_PROCESSOR => \App\Entity\Core\Picture::POST_PROCESSOR_SQUARE),
		School::FIELD_PHOTO          => array(School::ATTRIB_TYPE => Picture::TYPE_STRIPPED_NAME, School::ATTRIB_MULTIPLE => false),
		School::FIELD_WEBSITE        => array(School::ATTRIB_TYPE => Url::TYPE_STRIPPED_NAME, School::ATTRIB_MULTIPLE => false),
		School::FIELD_ADDRESS        => array(School::ATTRIB_TYPE => Location::TYPE_STRIPPED_NAME, School::ATTRIB_MULTIPLE => false, School::ATTRIB_LINKED_FIELDS => array('latitude', 'longitude', 'geographicalAreas', 'postalCode', 'locality', 'country')),
		School::FIELD_PHONE          => array(School::ATTRIB_TYPE => Phone::TYPE_STRIPPED_NAME, School::ATTRIB_MULTIPLE => false),
		School::FIELD_DESCRIPTION    => array(School::ATTRIB_TYPE => Longtext::TYPE_STRIPPED_NAME, School::ATTRIB_MULTIPLE => false),
		School::FIELD_VIDEO    		 => array(School::ATTRIB_TYPE => Video::TYPE_STRIPPED_NAME, School::ATTRIB_MULTIPLE => false),
		School::FIELD_PUBLIC         => array(School::ATTRIB_TYPE => Integer::TYPE_STRIPPED_NAME, School::ATTRIB_MULTIPLE => false, School::ATTRIB_CHOICES => array(1 => 'Oui', 0 => 'Non')),
		School::FIELD_BIRTH_YEAR     => array(School::ATTRIB_TYPE => Integer::TYPE_STRIPPED_NAME, School::ATTRIB_MULTIPLE => false),
		School::FIELD_DIPLOMAS       => array(School::ATTRIB_TYPE => Text::TYPE_STRIPPED_NAME, School::ATTRIB_MULTIPLE => true, School::ATTRIB_FILTER_QUERY => '@diplomas:"%q%"', Wood::ATTRIB_DATA_CONSTRAINTS => array(array('App\\Validator\\Constraints\\OneThing', array('message' => 'N\'indiquez qu\'un seul diplôme par proposition.')))),
		School::FIELD_TRAINING_TYPES => array(School::ATTRIB_TYPE => Integer::TYPE_STRIPPED_NAME, School::ATTRIB_MULTIPLE => true, School::ATTRIB_CHOICES => array(0 => 'Continue', 1 => 'Alternance', 2 => 'Apprentissage', 4 => 'Professionnelle', 5 => 'Stage court', 6 => 'En ligne'), School::ATTRIB_USE_CHOICES_VALUE => true, School::ATTRIB_FILTER_QUERY => '@training-types:"%q%"'),
	);

	/**
	 * @ORM\Column(type="string", nullable=true, length=100)
	 */
	private $name;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Knowledge\Value\Text", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_school_value_name")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $nameValues;

	/**
	 * @ORM\Column(type="boolean", nullable=false, name="name_rejected")
	 */
	private $nameRejected = false;


	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Knowledge\Value\Picture", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_school_value_logo")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $logoValues;

	/**
	 * @ORM\Column(type="boolean", nullable=false, name="logo_rejected")
	 */
	private $logoRejected = false;


	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Core\Picture", cascade={"persist"})
	 * @ORM\JoinColumn(name="photo_id", nullable=true)
	 * @Assert\Type(type="App\Entity\Core\Picture")
	 */
	private $photo;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Knowledge\Value\Picture", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_school_value_photo")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $photoValues;


	/**
	 * @ORM\Column(type="string", nullable=true, length=255)
	 */
	private $website;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Knowledge\Value\Url", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_school_value_website")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
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
	 * @ORM\ManyToMany(targetEntity="App\Entity\Knowledge\Value\Location", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_school_value_address")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $addressValues;


	/**
	 * @ORM\Column(type="string", nullable=true, length=20)
	 */
	private $phone;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Knowledge\Value\Phone", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_school_value_phone")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $phoneValues;


	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	private $description;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Knowledge\Value\Longtext", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_school_value_description")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $descriptionValues;


	/**
	 * @ORM\Column(type="string", nullable=true, length=255)
	 */
	private $video;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Knowledge\Value\Video", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_school_value_video")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $videoValues;


	/**
	 * @ORM\Column(type="boolean", nullable=true)
	 */
	private $public;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Knowledge\Value\Integer", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_school_value_public")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $publicValues;


	/**
	 * @ORM\Column(type="integer", nullable=true)
	 */
	private $birthYear;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Knowledge\Value\Integer", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_school_value_birth_year")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $birthYearValues;


	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	private $diplomas;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Knowledge\Value\Text", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_school_value_diplomas")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $diplomasValues;


	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	private $trainingTypes;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Knowledge\Value\Integer", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_school_value_training_types")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $trainingTypesValues;


	/**
	 * @ORM\Column(name="testimonial_count", type="integer")
	 */
	private $testimonialCount = 0;

	/**
	 * @ORM\OneToMany(targetEntity="App\Entity\Knowledge\School\Testimonial", mappedBy="school", cascade={"all"})
	 * @ORM\OrderBy({"fromYear" = "DESC", "createdAt" = "DESC"})
	 */
	private $testimonials;

	/**
	 * @ORM\Column(type="integer", name="creation_count")
	 */
	private $creationCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Wonder\Creation", mappedBy="schools")
	 */
	private $creations;

	/**
	 * @ORM\Column(type="integer", name="plan_count")
	 */
	private $planCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Wonder\Plan", mappedBy="schools")
	 */
	private $plans;

	/**
	 * @ORM\Column(type="integer", name="howto_count")
	 */
	private $howtoCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Howto\Howto", mappedBy="schools")
	 */
	private $howtos;

	/////

	public function __construct() {
		$this->nameValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->logoValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->photoValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->websiteValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->addressValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->phoneValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->descriptionValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->videoValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->publicValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->birthYearValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->diplomasValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->trainingTypesValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->testimonials = new \Doctrine\Common\Collections\ArrayCollection();
		$this->creations = new \Doctrine\Common\Collections\ArrayCollection();
		$this->plans = new \Doctrine\Common\Collections\ArrayCollection();
		$this->howtos = new \Doctrine\Common\Collections\ArrayCollection();
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

	public function addNameValue(\App\Entity\Knowledge\Value\Text $nameValue) {
		if (!$this->nameValues->contains($nameValue)) {
			$this->nameValues[] = $nameValue;
		}
		return $this;
	}

	public function removeNameValue(\App\Entity\Knowledge\Value\Text $nameValue) {
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

	public function addLogoValue(\App\Entity\Knowledge\Value\Picture $logoValue) {
		if (!$this->logoValues->contains($logoValue)) {
			$this->logoValues[] = $logoValue;
		}
		return $this;
	}

	public function removeLogoValue(\App\Entity\Knowledge\Value\Picture $logoValue) {
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

	public function addPhotoValue(\App\Entity\Knowledge\Value\Picture $photoValue) {
		if (!$this->photoValues->contains($photoValue)) {
			$this->photoValues[] = $photoValue;
		}
		return $this;
	}

	public function removePhotoValue(\App\Entity\Knowledge\Value\Picture $photoValue) {
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

	public function addWebsiteValue(\App\Entity\Knowledge\Value\Url $websiteValue) {
		if (!$this->websiteValues->contains($websiteValue)) {
			$this->websiteValues[] = $websiteValue;
		}
		return $this;
	}

	public function removeWebsiteValue(\App\Entity\Knowledge\Value\Url $websiteValue) {
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

	public function addAddressValue(\App\Entity\Knowledge\Value\Location $addressValue) {
		if (!$this->addressValues->contains($addressValue)) {
			$this->addressValues[] = $addressValue;
		}
		return $this;
	}

	public function removeAddressValue(\App\Entity\Knowledge\Value\Location $addressValue) {
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

	public function addPhoneValue(\App\Entity\Knowledge\Value\Phone $phoneValue) {
		if (!$this->phoneValues->contains($phoneValue)) {
			$this->phoneValues[] = $phoneValue;
		}
		return $this;
	}

	public function removePhoneValue(\App\Entity\Knowledge\Value\Phone $phoneValue) {
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

	public function addDescriptionValue(\App\Entity\Knowledge\Value\Longtext $descriptionValue) {
		if (!$this->descriptionValues->contains($descriptionValue)) {
			$this->descriptionValues[] = $descriptionValue;
		}
		return $this;
	}

	public function removeDescriptionValue(\App\Entity\Knowledge\Value\Longtext $descriptionValue) {
		$this->descriptionValues->removeElement($descriptionValue);
	}

	public function setDescriptionValues($descriptionValues) {
		$this->descriptionValues = $descriptionValues;
	}

	public function getDescriptionValues() {
		return $this->descriptionValues;
	}

	// Video /////

	public function setVideo($video) {
		$this->video = $video;
		return $this;
	}

	public function getVideo() {
		return $this->video;
	}

	// VideoValues /////

	public function addVideoValue(\App\Entity\Knowledge\Value\Video $videoValue) {
		if (!$this->videoValues->contains($videoValue)) {
			$this->videoValues[] = $videoValue;
		}
		return $this;
	}

	public function removeVideoValue(\App\Entity\Knowledge\Value\Video $videoValue) {
		$this->videoValues->removeElement($videoValue);
	}

	public function setVideoValues($videoValues) {
		$this->videoValues = $videoValues;
	}

	public function getVideoValues() {
		return $this->videoValues;
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

	public function addPublicValue(\App\Entity\Knowledge\Value\Integer $publicValue) {
		if (!$this->publicValues->contains($publicValue)) {
			$this->publicValues[] = $publicValue;
		}
		return $this;
	}

	public function removePublicValue(\App\Entity\Knowledge\Value\Integer $publicValue) {
		$this->publicValues->removeElement($publicValue);
	}

	public function setPublicValues($publicValues) {
		$this->publicValues = $publicValues;
	}

	public function getPublicValues() {
		return $this->publicValues;
	}

	// BirthYear /////

	public function setBirthYear($birthYear) {
		$this->birthYear = $birthYear;
		return $this;
	}

	public function getBirthYear() {
		return $this->birthYear;
	}

	// BirthYearValues /////

	public function addBirthYearValue(\App\Entity\Knowledge\Value\Integer $birthYearValue) {
		if (!$this->birthYearValues->contains($birthYearValue)) {
			$this->birthYearValues[] = $birthYearValue;
		}
		return $this;
	}

	public function removeBirthYearValue(\App\Entity\Knowledge\Value\Integer $birthYearValue) {
		$this->birthYearValues->removeElement($birthYearValue);
	}

	public function setBirthYearValues($birthYearValues) {
		$this->birthYearValues = $birthYearValues;
	}

	public function getBirthYearValues() {
		return $this->birthYearValues;
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

	public function addDiplomasValue(\App\Entity\Knowledge\Value\Text $diplomasValue) {
		if (!$this->diplomasValues->contains($diplomasValue)) {
			$this->diplomasValues[] = $diplomasValue;
		}
		return $this;
	}

	public function removeDiplomasValue(\App\Entity\Knowledge\Value\Text $diplomasValue) {
		$this->diplomasValues->removeElement($diplomasValue);
	}

	public function setDiplomasValues($diplomasValues) {
		$this->diplomasValues = $diplomasValues;
	}

	public function getDiplomasValues() {
		return $this->diplomasValues;
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

	public function addTrainingTypesValue(\App\Entity\Knowledge\Value\Integer $trainingTypesValue) {
		if (!$this->trainingTypesValues->contains($trainingTypesValue)) {
			$this->trainingTypesValues[] = $trainingTypesValue;
		}
		return $this;
	}

	public function removeTrainingTypesValue(\App\Entity\Knowledge\Value\Integer $trainingTypesValue) {
		$this->trainingTypesValues->removeElement($trainingTypesValue);
	}

	public function setTrainingTypesValues($trainingTypesValues) {
		$this->trainingTypesValues = $trainingTypesValues;
	}

	public function getTrainingTypesValues() {
		return $this->trainingTypesValues;
	}

	// TestimonialCount /////

	public function incrementTestimonialCount($by = 1) {
		return $this->testimonialCount += intval($by);
	}

	public function setTestimonialCount($testimonialCount) {
		$this->testimonialCount = $testimonialCount;
		return $this;
	}

	public function getTestimonialCount() {
		return $this->testimonialCount;
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

	// CreationCount /////

	public function incrementCreationCount($by = 1) {
		return $this->creationCount += intval($by);
	}

	public function getCreationCount() {
		return $this->creationCount;
	}

	public function setCreationCount($creationCount) {
		$this->creationCount = $creationCount;
		return $this;
	}

	// Creations /////

	public function getCreations() {
		return $this->creations;
	}

	// PlanCount /////

	public function incrementPlanCount($by = 1) {
		return $this->planCount += intval($by);
	}

	public function getPlanCount() {
		return $this->planCount;
	}

	public function setPlanCount($planCount) {
		$this->planCount = $planCount;
		return $this;
	}

	// Plans /////

	public function getPlans() {
		return $this->plans;
	}

	// HowtoCount /////

	public function incrementHowtoCount($by = 1) {
		return $this->howtoCount += intval($by);
	}

	public function getHowtoCount() {
		return $this->howtoCount;
	}

	public function setHowtoCount($howtoCount) {
		$this->howtoCount = $howtoCount;
		return $this;
	}

	// Howtos /////

	public function getHowtos() {
		return $this->howtos;
	}

}