<?php

namespace Ladb\CoreBundle\Entity\Knowledge;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ladb\CoreBundle\Entity\Knowledge\Value\Decimal;
use Symfony\Component\Validator\Constraints as Assert;
use Ladb\CoreBundle\Entity\Knowledge\Value\Pdf;
use Ladb\CoreBundle\Entity\Knowledge\Value\Video;
use Ladb\CoreBundle\Entity\Knowledge\Value\Url;
use Ladb\CoreBundle\Model\ReviewableInterface;
use Ladb\CoreBundle\Model\ReviewableTrait;
use Ladb\CoreBundle\Entity\Knowledge\Value\Text;
use Ladb\CoreBundle\Entity\Knowledge\Value\Longtext;
use Ladb\CoreBundle\Entity\Knowledge\Value\Integer;
use Ladb\CoreBundle\Entity\Knowledge\Value\Picture;

/**
 * Ladb\CoreBundle\Entity\Knowledge\Tool
 *
 * @ORM\Table("tbl_knowledge2_tool")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Knowledge\ToolRepository")
 */
class Tool extends AbstractKnowledge implements ReviewableInterface {

	use ReviewableTrait;

	const CLASS_NAME = 'LadbCoreBundle:Knowledge\Tool';
	const TYPE = 124;

	const STRIPPED_NAME = 'tool';

	const FIELD_NAME = 'name';
	const FIELD_PHOTO = 'photo';
	const FIELD_MANUAL = 'manual';
	const FIELD_ENGLISH_NAME = 'english_name';
	const FIELD_PRODUCT_NAME = 'product_name';
	const FIELD_BRAND = 'brand';
	const FIELD_DESCRIPTION = 'description';
	const FIELD_FAMILY = 'family';
	const FIELD_POWER_SUPPLY = 'power_supply';
	const FIELD_POWER = 'power';
	const FIELD_VOLTAGE = 'voltage';
	const FIELD_WEIGHT = 'weight';
	const FIELD_DOCS = 'docs';
	const FIELD_CATALOG_LINK = 'catalog_link';
	const FIELD_VIDEO = 'video';
	const FIELD_UTILIZATION = 'utilization';

	public static $FIELD_DEFS = array(
		Tool::FIELD_NAME         => array(Tool::ATTRIB_TYPE => Text::TYPE_STRIPPED_NAME, Tool::ATTRIB_MULTIPLE => false, Tool::ATTRIB_MANDATORY => true, Tool::ATTRIB_FILTER_QUERY => '@name:"%q%"'),
		Tool::FIELD_PHOTO        => array(Tool::ATTRIB_TYPE => Picture::TYPE_STRIPPED_NAME, Tool::ATTRIB_MULTIPLE => false, Tool::ATTRIB_MANDATORY => true, Tool::ATTRIB_POST_PROCESSOR => \Ladb\CoreBundle\Entity\Core\Picture::POST_PROCESSOR_SQUARE),
		Tool::FIELD_MANUAL       => array(Tool::ATTRIB_TYPE => Pdf::TYPE_STRIPPED_NAME, Tool::ATTRIB_MULTIPLE => false),
		Tool::FIELD_ENGLISH_NAME => array(Tool::ATTRIB_TYPE => Text::TYPE_STRIPPED_NAME, Tool::ATTRIB_MULTIPLE => true, Tool::ATTRIB_DATA_CONSTRAINTS => array(array('\\Ladb\\CoreBundle\\Validator\\Constraints\\OneThing', array('message' => 'N\'indiquez qu\'un seul nom anglais par proposition.')))),
		Tool::FIELD_PRODUCT_NAME => array(Tool::ATTRIB_TYPE => Text::TYPE_STRIPPED_NAME, Tool::ATTRIB_MULTIPLE => true),
		Tool::FIELD_BRAND        => array(Tool::ATTRIB_TYPE => Text::TYPE_STRIPPED_NAME, Tool::ATTRIB_MULTIPLE => false, Tool::ATTRIB_FILTER_QUERY => '@brand:"%q%"'),
		Tool::FIELD_DESCRIPTION  => array(Tool::ATTRIB_TYPE => Longtext::TYPE_STRIPPED_NAME, Tool::ATTRIB_MULTIPLE => false),
		Tool::FIELD_FAMILY       => array(Tool::ATTRIB_TYPE => Integer::TYPE_STRIPPED_NAME, Tool::ATTRIB_MULTIPLE => false, Tool::ATTRIB_CHOICES => array(1 => 'Outil à main', 2 => 'Outil de mesure', 3 => 'Electroportatif', 4 => 'Machine stationnaire', 6 => 'Machine semi-stationnaire', 5 => 'Gabarit'), Tool::ATTRIB_USE_CHOICES_VALUE => true, Tool::ATTRIB_FILTER_QUERY => '@family:"%q%"'),
		Tool::FIELD_POWER_SUPPLY => array(Tool::ATTRIB_TYPE => Integer::TYPE_STRIPPED_NAME, Tool::ATTRIB_MULTIPLE => false, Tool::ATTRIB_CHOICES => array(1 => 'Filaire', 2 => 'Batterie')),
		Tool::FIELD_POWER        => array(Tool::ATTRIB_TYPE => Integer::TYPE_STRIPPED_NAME, Tool::ATTRIB_MULTIPLE => false, Tool::ATTRIB_SUFFIX => 'W'),
		Tool::FIELD_VOLTAGE      => array(Tool::ATTRIB_TYPE => Integer::TYPE_STRIPPED_NAME, Tool::ATTRIB_MULTIPLE => false, Tool::ATTRIB_SUFFIX => 'V'),
		Tool::FIELD_WEIGHT       => array(Tool::ATTRIB_TYPE => Decimal::TYPE_STRIPPED_NAME, Tool::ATTRIB_MULTIPLE => false, Tool::ATTRIB_SUFFIX => 'kg'),
		Tool::FIELD_DOCS         => array(Tool::ATTRIB_TYPE => Url::TYPE_STRIPPED_NAME, Tool::ATTRIB_MULTIPLE => true),
		Tool::FIELD_CATALOG_LINK => array(Tool::ATTRIB_TYPE => Url::TYPE_STRIPPED_NAME, Tool::ATTRIB_MULTIPLE => true, Tool::ATTRIB_CONSTRAINTS => array(array('\\Ladb\\CoreBundle\\Validator\\Constraints\\ExcludeDomainsLink', array('excludedDomainPaterns' => array('/amazon./i', '/fnac./i', '/manomano./i'), 'message' => 'Les liens vers les sites de revendeurs ou distributeurs et les liens affiliés ne sont pas autorisés ici.')))),
		Tool::FIELD_VIDEO        => array(Tool::ATTRIB_TYPE => Video::TYPE_STRIPPED_NAME, Tool::ATTRIB_MULTIPLE => false),
		Tool::FIELD_UTILIZATION  => array(Tool::ATTRIB_TYPE => Text::TYPE_STRIPPED_NAME, Tool::ATTRIB_MULTIPLE => true, Tool::ATTRIB_DATA_CONSTRAINTS => array(array('\\Ladb\\CoreBundle\\Validator\\Constraints\\OneThing', array('message' => 'N\'indiquez qu\'une seule utilisation par proposition.'))), Tool::ATTRIB_FILTER_QUERY => '@utilization:"%q%"'),
	);

	/**
	 * @ORM\Column(type="string", nullable=true)
	 */
	private $name;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Text", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_tool_value_name")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $nameValues;

	/**
	 * @ORM\Column(name="name_rejected", type="boolean", nullable=false)
	 */
	private $nameRejected = false;


	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Picture", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_tool_value_photo")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $photoValues;

	/**
	 * @ORM\Column(name="photo_rejected", type="boolean", nullable=false)
	 */
	private $photoRejected = false;


	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\Resource", cascade={"persist"})
	 * @ORM\JoinColumn(name="manual_id", nullable=true)
	 * @Assert\Type(type="Ladb\CoreBundle\Entity\Core\Resource")
	 */
	private $manual;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Pdf", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_tool_value_manual")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $manualValues;


	/**
	 * @ORM\Column(type="string", nullable=true)
	 */
	private $brand;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Text", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_tool_value_brand")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $brandValues;


	/**
	 * @ORM\Column(name="english_name", type="string", nullable=true)
	 */
	private $englishName;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Text", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_tool_value_english_name")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $englishNameValues;


	/**
	 * @ORM\Column(name="product_name", type="string", nullable=true)
	 */
	private $productName;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Text", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_tool_value_product_name")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $productNameValues;


	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	private $description;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Longtext", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_tool_value_description")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $descriptionValues;


	/**
	 * @ORM\Column(type="string", nullable=true, length=100)
	 */
	private $family;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Integer", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_tool_value_family")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $familyValues;


	/**
	 * @ORM\Column(name="power_supply", type="integer", nullable=true)
	 */
	private $powerSupply;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Integer", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_tool_value_power_supply")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $powerSupplyValues;


	/**
	 * @ORM\Column(type="integer", nullable=true)
	 */
	private $power;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Integer", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_tool_value_power")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $powerValues;


	/**
	 * @ORM\Column(type="integer", nullable=true)
	 */
	private $voltage;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Integer", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_tool_value_voltage")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $voltageValues;


	/**
	 * @ORM\Column(type="decimal", precision=10, scale=3, nullable=true)
	 */
	private $weight;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Decimal", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_tool_value_weight")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $weightValues;


	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	private $docs;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Url", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_tool_value_docs")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $docsValues;


	/**
	 * @ORM\Column(name="catalog_link", type="text", nullable=true)
	 */
	private $catalogLink;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Url", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_tool_value_catalog_link")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $catalogLinkValues;


	/**
	 * @ORM\Column(type="string", nullable=true, length=255, name="video")
	 */
	private $video;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Video", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_tool_value_video")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $videoValues;


	/**
	 * @ORM\Column(type="string", nullable=true)
	 */
	private $utilization;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Text", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_tool_value_utilization")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $utilizationValues;


	/**
	 * @ORM\Column(name="review_count", type="integer")
	 */
	private $reviewCount = 0;

	/**
	 * @ORM\Column(name="average_rating", type="float")
	 */
	private $averageRating = 0;

	/////

	public function __construct() {
		$this->nameValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->photoValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->manualValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->brandValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->englishNameValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->productNameValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->descriptionValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->familyValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->powerSupplyValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->powerValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->voltageValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->weightValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->docsValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->catalogLinkValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->videoValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->utilizationValues = new \Doctrine\Common\Collections\ArrayCollection();
	}

	/////

	private function _updateTitle() {
		$words = array();
		$name = $this->getName();
		if (!is_null($name)) {
			$words[] = explode(',', $name)[0];
		}
		$productName = $this->getProductName();
		if (!is_null($productName)) {
			$words[] = explode(',', $productName)[0];
		}
		if (!empty($words)) {
			$this->setTitle(implode(' ', $words));
		} else {
			$this->setTitle(null);
		}
	}

	// IsRejected /////

	public function getIsRejected() {
		return $this->getNameRejected() || $this->getPhotoRejected();
	}

	// Type /////

	public function getType() {
		return Tool::TYPE;
	}

	// Body /////

	public function getBody() {
		$terms = array($this->getTitle());
		return implode($terms, ',');
	}

	// StrippedName /////

	public function getStrippedName() {
		return Tool::STRIPPED_NAME;
	}

	// FieldDefs /////

	public function getFieldDefs() {
		return Tool::$FIELD_DEFS;
	}

	// Name /////

	public function setName($name) {
		$this->name = $name;
		$this->_updateTitle();
		return $this;
	}

	public function getName() {
		return $this->name;
	}

	public function getNameKeyword() {
		return $this->getName();
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

	// Photo /////

	public function setPhoto($photo) {
		return $this->setMainPicture($photo);
	}

	public function getPhoto() {
		return $this->getMainPicture();
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

	// PhotoRejected /////

	public function setPhotoRejected($photoRejected) {
		$this->photoRejected = $photoRejected;
		return $this;
	}

	public function getPhotoRejected() {
		return $this->photoRejected;
	}

	// Manuals /////

	public function setManual($manual) {
		$this->manual = $manual;
		return $this;
	}

	public function getManual() {
		return $this->manual;
	}

	// ManualValues /////

	public function addManualValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Pdf $manual) {
		if (!$this->manualValues->contains($manual)) {
			$this->manualValues[] = $manual;
		}
		return $this;
	}

	public function removeManualValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Pdf $manual) {
		$this->manualValues->removeElement($manual);
	}

	public function setManualValues($manualValues) {
		$this->manualValues = $manualValues;
	}

	public function getManualValues() {
		return $this->manualValues;
	}

	// ManualCount /////

	public function getManualCount() {
		return $this->getManualValues()->count();
	}

	// Brand /////

	public function setBrand($brand) {
		$this->brand = $brand;
		return $this;
	}

	public function getBrand() {
		return $this->brand;
	}

	// BrandValues /////

	public function addBrandValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Text $brand) {
		if (!$this->brandValues->contains($brand)) {
			$this->brandValues[] = $brand;
		}
		return $this;
	}

	public function removeBrandValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Text $brand) {
		$this->brandValues->removeElement($brand);
	}

	public function setBrandValues($brandValues) {
		$this->brandValues = $brandValues;
	}

	public function getBrandValues() {
		return $this->brandValues;
	}

	// EnglishName /////

	public function setEnglishName($englishName) {
		$this->englishName = $englishName;
		$this->_updateTitle();
		return $this;
	}

	public function getEnglishName() {
		return $this->englishName;
	}

	// EnglishNameValues /////

	public function addEnglishNameValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Text $englishName) {
		if (!$this->englishNameValues->contains($englishName)) {
			$this->englishNameValues[] = $englishName;
		}
		return $this;
	}

	public function removeEnglishNameValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Text $englishName) {
		$this->englishNameValues->removeElement($englishName);
	}

	public function setEnglishNameValues($englishNameValues) {
		$this->englishNameValues = $englishNameValues;
	}

	public function getEnglishNameValues() {
		return $this->englishNameValues;
	}

	// ProductName /////

	public function setProductName($productName) {
		$this->productName = $productName;
		$this->_updateTitle();
		return $this;
	}

	public function getProductName() {
		return $this->productName;
	}

	// ProductNameValues /////

	public function addProductNameValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Text $productName) {
		if (!$this->productNameValues->contains($productName)) {
			$this->productNameValues[] = $productName;
		}
		return $this;
	}

	public function removeProductNameValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Text $productName) {
		$this->productNameValues->removeElement($productName);
	}

	public function setProductNameValues($productNameValues) {
		$this->productNameValues = $productNameValues;
	}

	public function getProductNameValues() {
		return $this->productNameValues;
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

	// Family /////

	public function setFamily($family) {
		$this->family = $family;
		return $this;
	}

	public function getFamily() {
		return $this->family;
	}

	// FamilyValues /////

	public function addFamilyValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Integer $familyValue) {
		if (!$this->familyValues->contains($familyValue)) {
			$this->familyValues[] = $familyValue;
		}
		return $this;
	}

	public function removeFamilyValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Integer $familyValue) {
		$this->familyValues->removeElement($familyValue);
	}

	public function setFamilyValues($familyValues) {
		$this->familyValues = $familyValues;
	}

	public function getFamilyValues() {
		return $this->familyValues;
	}

	// PowerSupply /////

	public function setPowerSupply($powerSupply) {
		$this->powerSupply = $powerSupply;
		return $this;
	}

	public function getPowerSupply() {
		return $this->powerSupply;
	}

	// PowerSupplyValues /////

	public function addPowerSupplyValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Integer $powerSupplyValue) {
		if (!$this->powerSupplyValues->contains($powerSupplyValue)) {
			$this->powerSupplyValues[] = $powerSupplyValue;
		}
		return $this;
	}

	public function removePowerSupplyValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Integer $powerSupplyValue) {
		$this->powerSupplyValues->removeElement($powerSupplyValue);
	}

	public function setPowerSupplyValues($powerSupplyValues) {
		$this->powerSupplyValues = $powerSupplyValues;
	}

	public function getPowerSupplyValues() {
		return $this->powerSupplyValues;
	}

	// Power /////

	public function setPower($power) {
		$this->power = $power;
		return $this;
	}

	public function getPower() {
		return $this->power;
	}

	// PowerValues /////

	public function addPowerValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Integer $powerValue) {
		if (!$this->powerValues->contains($powerValue)) {
			$this->powerValues[] = $powerValue;
		}
		return $this;
	}

	public function removePowerValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Integer $powerValue) {
		$this->powerValues->removeElement($powerValue);
	}

	public function setPowerValues($powerValues) {
		$this->powerValues = $powerValues;
	}

	public function getPowerValues() {
		return $this->powerValues;
	}

	// Voltage /////

	public function setVoltage($voltage) {
		$this->voltage = $voltage;
		return $this;
	}

	public function getVoltage() {
		return $this->voltage;
	}

	// VoltageValues /////

	public function addVoltageValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Integer $voltageValue) {
		if (!$this->voltageValues->contains($voltageValue)) {
			$this->voltageValues[] = $voltageValue;
		}
		return $this;
	}

	public function removeVoltageValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Integer $voltageValue) {
		$this->voltageValues->removeElement($voltageValue);
	}

	public function setVoltageValues($voltageValues) {
		$this->voltageValues = $voltageValues;
	}

	public function getVoltageValues() {
		return $this->voltageValues;
	}

	// Weight /////

	public function setWeight($weight) {
		$this->weight = $weight;
		return $this;
	}

	public function getWeight() {
		return $this->weight;
	}

	// WeightValues /////

	public function addWeightValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Decimal $weightValue) {
		if (!$this->weightValues->contains($weightValue)) {
			$this->weightValues[] = $weightValue;
		}
		return $this;
	}

	public function removeWeightValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Decimal $weightValue) {
		$this->weightValues->removeElement($weightValue);
	}

	public function setWeightValues($weightValues) {
		$this->weightValues = $weightValues;
	}

	public function getWeightValues() {
		return $this->weightValues;
	}

	// Docs /////

	public function setDocs($docs) {
		$this->docs = $docs;
		return $this;
	}

	public function getDocs() {
		return $this->docs;
	}

	// DocsValues /////

	public function addDocsValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Url $docsValue) {
		if (!$this->docsValues->contains($docsValue)) {
			$this->docsValues[] = $docsValue;
		}
		return $this;
	}

	public function removeDocsValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Url $docsValue) {
		$this->docsValues->removeElement($docsValue);
	}

	public function setDocsValues($docsValues) {
		$this->docsValues = $docsValues;
	}

	public function getDocsValues() {
		return $this->docsValues;
	}

	// CatalogLink /////

	public function setCatalogLink($catalogLink) {
		$this->catalogLink = $catalogLink;
		return $this;
	}

	public function getCatalogLink() {
		return $this->catalogLink;
	}

	// CatalogLinkValues /////

	public function addCatalogLinkValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Url $catalogLinkValue) {
		if (!$this->catalogLinkValues->contains($catalogLinkValue)) {
			$this->catalogLinkValues[] = $catalogLinkValue;
		}
		return $this;
	}

	public function removeCatalogLinkValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Url $catalogLinkValue) {
		$this->catalogLinkValues->removeElement($catalogLinkValue);
	}

	public function setCatalogLinkValues($catalogLinkValues) {
		$this->catalogLinkValues = $catalogLinkValues;
	}

	public function getCatalogLinkValues() {
		return $this->catalogLinkValues;
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

	public function addVideoValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Video $videoValue) {
		if (!$this->videoValues->contains($videoValue)) {
			$this->videoValues[] = $videoValue;
		}
		return $this;
	}

	public function removeVideoValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Video $videoValue) {
		$this->videoValues->removeElement($videoValue);
	}

	public function setVideoValues($videoValues) {
		$this->videoValues = $videoValues;
	}

	public function getVideoValues() {
		return $this->videoValues;
	}

	// Utilization /////

	public function setUtilization($utilization) {
		$this->utilization = $utilization;
		return $this;
	}

	public function getUtilization() {
		return $this->utilization;
	}

	// UtilizationValues /////

	public function addUtilizationValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Text $utilizationValue) {
		if (!$this->utilizationValues->contains($utilizationValue)) {
			$this->utilizationValues[] = $utilizationValue;
		}
		return $this;
	}

	public function removeUtilizationValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Text $utilizationValue) {
		$this->utilizationValues->removeElement($utilizationValue);
	}

	public function setUtilizationValues($utilizationValues) {
		$this->utilizationValues = $utilizationValues;
	}

	public function getUtilizationValues() {
		return $this->utilizationValues;
	}

}