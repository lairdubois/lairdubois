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
	const TYPE = 111;

	const STRIPPED_NAME = 'school';

	const FIELD_NAME = 'name';
	const FIELD_LOGO = 'logo';
	const FIELD_PHOTO = 'photo';
	const FIELD_WEBSITE  = 'website';
	const FIELD_ADDRESS  = 'address';
	const FIELD_PHONE  = 'phone';
	const FIELD_DESCRIPTION  = 'description';

	public static $FIELD_DEFS = array(
		School::FIELD_NAME        => array(School::ATTRIB_TYPE => Text::TYPE_STRIPPED_NAME, School::ATTRIB_MULTIPLE => true, School::ATTRIB_MANDATORY => true, School::ATTRIB_CONSTRAINTS => array(array('\\Ladb\\CoreBundle\\Validator\\Constraints\\UniqueWood', array('excludedId' => '@getId'))), School::ATTRIB_DATA_CONSTRAINTS => array(array('\\Ladb\\CoreBundle\\Validator\\Constraints\\OneThing', array('message' => 'N\'indiquez qu\'un seul Nom français par proposition.')))),
		School::FIELD_LOGO        => array(School::ATTRIB_TYPE => Picture::TYPE_STRIPPED_NAME, School::ATTRIB_MULTIPLE => false, School::ATTRIB_MANDATORY => true),
		School::FIELD_PHOTO       => array(School::ATTRIB_TYPE => Picture::TYPE_STRIPPED_NAME, School::ATTRIB_MULTIPLE => false),
		School::FIELD_WEBSITE     => array(School::ATTRIB_TYPE => Url::TYPE_STRIPPED_NAME, School::ATTRIB_MULTIPLE => false),
		School::FIELD_ADDRESS     => array(School::ATTRIB_TYPE => Location::TYPE_STRIPPED_NAME, School::ATTRIB_MULTIPLE => false, School::ATTRIB_LINKED_FIELDS => array('latitude', 'longitude', 'geographicalAreas', 'postalCode', 'locality', 'country')),
		School::FIELD_PHONE       => array(School::ATTRIB_TYPE => Phone::TYPE_STRIPPED_NAME, School::ATTRIB_MULTIPLE => false),
		School::FIELD_DESCRIPTION => array(School::ATTRIB_TYPE => Longtext::TYPE_STRIPPED_NAME, School::ATTRIB_MULTIPLE => false),
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
	 * @ORM\JoinTable(name="tbl_knowledge2_provider_value_logo")
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
	 * @ORM\JoinTable(name="tbl_knowledge2_provider_value_photo")
	 * @ORM\OrderBy({"voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $photoValues;


	/**
	 * @ORM\Column(type="string", nullable=true, length=255)
	 */
	private $website;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Url", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_provider_value_website")
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
	 * @ORM\JoinTable(name="tbl_knowledge2_provider_value_address")
	 * @ORM\OrderBy({"voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $addressValues;


	/**
	 * @ORM\Column(type="string", nullable=true, length=20)
	 */
	private $phone;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Phone", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_provider_value_phone")
	 * @ORM\OrderBy({"voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $phoneValues;


	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	private $description;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Longtext", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_provider_value_description")
	 * @ORM\OrderBy({"voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $descriptionValues;


	/////

	public function __construct() {
		$this->nameValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->logoValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->photoValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->websiteValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->addressValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->phoneValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->descriptionValues = new \Doctrine\Common\Collections\ArrayCollection();
	}

	/////

	// Type /////

	public function getType() {
		return School::TYPE;
	}

	// IsRejected /////

	public function getIsRejected() {
		return $this->getNameRejected() || $this->getLogoRejected();
	}

	// SignRejected /////

	public function getNameRejected() {
		return $this->nameRejected;
	}

	public function getLogoRejected() {
		return $this->logoRejected;
	}

	// LogoRejected /////

	public function setLogoRejected($logoRejected) {
		$this->logoRejected = $logoRejected;
		return $this;
	}

	public function setSignRejected($signRejected) {
		$this->signRejected = $signRejected;
		return $this;
	}

	// FieldDefs /////

	public function getBody() {
		if (!empty($this->getDescription())) {
			return $this->getDescription();
		}
		$terms = array($this->getName());
		if (!empty($this->getStore())) {
			$terms[] = $this->getStore();
		}
		if (!empty($this->getProducts())) {
			$terms[] = $this->getProducts();
		}
		if (!empty($this->getServices())) {
			$terms[] = $this->getServices();
		}
		return implode($terms, ',');
	}

	// IsAffiliate /////

	public function getDescription() {
		return $this->description;
	}

	public function setDescription($description) {
		$this->description = $description;
		return $this;
	}

	// Store /////

	public function getName() {
		return $this->name;
	}

	public function setName($name) {
		$this->name = $name;
		$this->setTitle($name);
		return $this;
	}

	// Sign /////

	public function getStore() {
		return $this->store;
	}

	public function getProducts() {
		return $this->products;
	}

	// SignValues /////

	public function getServices() {
		return $this->services;
	}

	public function setStore($store) {
		$this->store = $store;
		return $this;
	}

	public function setProducts($products) {
		$this->products = $products;
		return $this;
	}

	public function setServices($services) {
		$this->services = $services;
		return $this;
	}

	// SignRejected /////

	public function getStrippedName() {
		return School::STRIPPED_NAME;
	}

	public function getFieldDefs() {
		return School::$FIELD_DEFS;
	}

	// Logo /////

	public function getIsAffiliate() {
		return $this->isAffiliate;
	}

	public function setIsAffiliate($isAffiliate) {
		$this->isAffiliate = $isAffiliate;
		return $this;
	}

	// LogoValues /////

	public function getSign() {
		return $this->sign;
	}

	public function setSign($sign) {
		$this->sign = $sign;
		return $this;
	}

	public function addSignValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Sign $signValue) {
		if (!$this->signValues->contains($signValue)) {
			$this->signValues[] = $signValue;
		}
		return $this;
	}

	public function removeSignValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Sign $signValue) {
		$this->signValues->removeElement($signValue);
	}

	// LogoRejected /////

	public function getSignValues() {
		return $this->signValues;
	}

	public function setSignValues($signValues) {
		$this->signValues = $signValues;
	}

	// Photo /////

	public function setLogo($logo) {
		return $this->setMainPicture($logo);
	}

	public function getLogo() {
		return $this->getMainPicture();
	}

	// PhotoValues /////

	public function addLogoValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Picture $logoValue) {
		if (!$this->logoValues->contains($logoValue)) {
			$this->logoValues[] = $logoValue;
		}
		return $this;
	}

	public function removeLogoValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Picture $logoValue) {
		$this->logoValues->removeElement($logoValue);
	}

	public function getLogoValues() {
		return $this->logoValues;
	}

	public function setLogoValues($logoValues) {
		$this->logoValues = $logoValues;
	}

	// Website /////

	public function getPhoto() {
		return $this->photo;
	}

	public function setPhoto($photo) {
		return $this->photo = $photo;
	}

	// WebsiteValues /////

	public function addPhotoValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Picture $photoValue) {
		if (!$this->photoValues->contains($photoValue)) {
			$this->photoValues[] = $photoValue;
		}
		return $this;
	}

	public function removePhotoValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Picture $photoValue) {
		$this->photoValues->removeElement($photoValue);
	}

	public function getPhotoValues() {
		return $this->photoValues;
	}

	public function setPhotoValues($photoValues) {
		$this->photoValues = $photoValues;
	}

	// Address /////

	public function getWebsite() {
		return $this->website;
	}

	public function setWebsite($website) {
		$this->website = $website;
		return $this;
	}

	// Location /////

	public function addWebsiteValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Url $websiteValue) {
		if (!$this->websiteValues->contains($websiteValue)) {
			$this->websiteValues[] = $websiteValue;
		}
		return $this;
	}

	public function removeWebsiteValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Url $websiteValue) {
		$this->websiteValues->removeElement($websiteValue);
	}

	// GeographicalAreas /////

	public function getWebsiteValues() {
		return $this->websiteValues;
	}

	public function setWebsiteValues($websiteValues) {
		$this->websiteValues = $websiteValues;
	}

	// PostalCode /////

	public function setLocation($location) {
		return $this->setAddress($location);
	}

	public function getLocation() {
		return $this->getAddress();
	}

	// Locality /////

	public function getAddress() {
		return $this->address;
	}

	public function setAddress($address) {
		$this->address = $address;
		return $this;
	}

	// Country /////

	public function getGeographicalAreas() {
		return $this->geographicalAreas;
	}

	public function setGeographicalAreas($geographicalAreas = null) {
		$this->geographicalAreas = $geographicalAreas;
		return $this;
	}

	// AddressValues /////

	public function getPostalCode() {
		return $this->postalCode;
	}

	public function setPostalCode($postalCode = null) {
		$this->postalCode = $postalCode;
		return $this;
	}

	public function getLocality() {
		return $this->locality;
	}

	public function setLocality($locality = null) {
		$this->locality = $locality;
		return $this;
	}

	// Phone /////

	public function getCountry() {
		return $this->country;
	}

	public function setCountry($country = null) {
		$this->country = $country;
		return $this;
	}

	// PhoneValues /////

	public function addAddressValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Location $addressValue) {
		if (!$this->addressValues->contains($addressValue)) {
			$this->addressValues[] = $addressValue;
		}
		return $this;
	}

	public function removeAddressValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Location $addressValue) {
		$this->addressValues->removeElement($addressValue);
	}

	public function getAddressValues() {
		return $this->addressValues;
	}

	public function setAddressValues($addressValues) {
		$this->addressValues = $addressValues;
	}

	// Description /////

	public function getPhone() {
		return $this->phone;
	}

	public function setPhone($phone) {
		$this->phone = $phone;
		return $this;
	}

	// DescriptionValues /////

	public function addPhoneValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Phone $phoneValue) {
		if (!$this->phoneValues->contains($phoneValue)) {
			$this->phoneValues[] = $phoneValue;
		}
		return $this;
	}

	public function removePhoneValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Phone $phoneValue) {
		$this->phoneValues->removeElement($phoneValue);
	}

	public function getPhoneValues() {
		return $this->phoneValues;
	}

	public function setPhoneValues($phoneValues) {
		$this->phoneValues = $phoneValues;
	}

	// InsStoreSelling /////

	public function addDescriptionValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Longtext $descriptionValue) {
		if (!$this->descriptionValues->contains($descriptionValue)) {
			$this->descriptionValues[] = $descriptionValue;
		}
		return $this;
	}

	public function removeDescriptionValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Longtext $descriptionValue) {
		$this->descriptionValues->removeElement($descriptionValue);
	}

	// InStoreSellingValues /////

	public function getDescriptionValues() {
		return $this->descriptionValues;
	}

	public function setDescriptionValues($descriptionValues) {
		$this->descriptionValues = $descriptionValues;
	}

	public function getInStoreSelling() {
		return $this->inStoreSelling;
	}

	public function setInStoreSelling($inStoreSelling) {
		$this->inStoreSelling = $inStoreSelling;
		return $this;
	}

	// MailOrderSelling /////

	public function addInStoreSellingValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Integer $inStoreSellingValues) {
		if (!$this->inStoreSellingValues->contains($inStoreSellingValues)) {
			$this->inStoreSellingValues[] = $inStoreSellingValues;
		}
		return $this;
	}

	public function removeInStoreSellingValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Integer $inStoreSellingValues) {
		$this->inStoreSellingValues->removeElement($inStoreSellingValues);
	}

	// MailOrderSellingValues /////

	public function getInStoreSellingValues() {
		return $this->inStoreSellingValues;
	}

	public function setInStoreSellingValues($inStoreSellingValues) {
		$this->inStoreSellingValues = $inStoreSellingValues;
	}

	public function getMailOrderSelling() {
		return $this->mailOrderSelling;
	}

	public function setMailOrderSelling($mailOrderSelling) {
		$this->mailOrderSelling = $mailOrderSelling;
		return $this;
	}

	// SaleToIndividuals /////

	public function addMailOrderSellingValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Integer $mailOrderSellingValues) {
		if (!$this->mailOrderSellingValues->contains($mailOrderSellingValues)) {
			$this->mailOrderSellingValues[] = $mailOrderSellingValues;
		}
		return $this;
	}

	public function removeMailOrderSellingValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Integer $mailOrderSellingValues) {
		$this->mailOrderSellingValues->removeElement($mailOrderSellingValues);
	}

	// SaleToIndividualsValues /////

	public function getMailOrderSellingValues() {
		return $this->mailOrderSellingValues;
	}

	public function setMailOrderSellingValues($mailOrderSellingValues) {
		$this->mailOrderSellingValues = $mailOrderSellingValues;
	}

	public function getSaleToIndividuals() {
		return $this->saleToIndividuals;
	}

	public function setSaleToIndividuals($saleToIndividuals) {
		$this->saleToIndividuals = $saleToIndividuals;
		return $this;
	}

	// Products /////

	public function addSaleToIndividualsValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Integer $saleToIndividualsValues) {
		if (!$this->saleToIndividualsValues->contains($saleToIndividualsValues)) {
			$this->saleToIndividualsValues[] = $saleToIndividualsValues;
		}
		return $this;
	}

	public function removeSaleToIndividualsValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Integer $saleToIndividualsValues) {
		$this->saleToIndividualsValues->removeElement($saleToIndividualsValues);
	}

	// ProductsValues /////

	public function getSaleToIndividualsValues() {
		return $this->saleToIndividualsValues;
	}

	public function setSaleToIndividualsValues($saleToIndividualsValues) {
		$this->saleToIndividualsValues = $saleToIndividualsValues;
	}

	public function addProductsValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Integer $productsValue) {
		if (!$this->productsValues->contains($productsValue)) {
			$this->productsValues[] = $productsValue;
		}
		return $this;
	}

	public function removeProductsValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Integer $productsValue) {
		$this->productsValues->removeElement($productsValue);
	}

	// Services /////

	public function getProductsValues() {
		return $this->productsValues;
	}

	public function setProductsValues($productsValues) {
		$this->productsValues = $productsValues;
	}

	// ServicesValues /////

	public function addServicesValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Integer $servicesValue) {
		if (!$this->servicesValues->contains($servicesValue)) {
			$this->servicesValues[] = $servicesValue;
		}
		return $this;
	}

	public function removeServicesValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Integer $servicesValue) {
		$this->servicesValues->removeElement($servicesValue);
	}

	public function getServicesValues() {
		return $this->servicesValues;
	}

	public function setServicesValues($servicesValues) {
		$this->servicesValues = $servicesValues;
	}

	// Woods /////

	public function getWoods() {
		return $this->woods;
	}

	public function setWoods($woods) {
		$this->woods = $woods;
		return $this;
	}

	// WoodsValues /////

	public function addWoodsValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Text $woodsValue) {
		if (!$this->woodsValues->contains($woodsValue)) {
			$this->woodsValues[] = $woodsValue;
		}
		return $this;
	}

	public function removeWoodsValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Text $woodsValue) {
		$this->woodsValues->removeElement($woodsValue);
	}

	public function getWoodsValues() {
		return $this->woodsValues;
	}

	public function setWoodsValues($woodsValues) {
		$this->woodsValues = $woodsValues;
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