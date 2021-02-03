<?php

namespace Ladb\CoreBundle\Entity\Knowledge;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ladb\CoreBundle\Entity\Knowledge\Value\Pdf;
use Ladb\CoreBundle\Entity\Knowledge\Value\ToolIdentity;
use Ladb\CoreBundle\Entity\Knowledge\Value\Video;
use Symfony\Component\Validator\Constraints as Assert;
use Ladb\CoreBundle\Entity\Knowledge\Value\Price;
use Ladb\CoreBundle\Entity\Knowledge\Value\Url;
use Ladb\CoreBundle\Model\ReviewableInterface;
use Ladb\CoreBundle\Model\ReviewableTrait;
use Ladb\CoreBundle\Entity\Knowledge\Value\Text;
use Ladb\CoreBundle\Entity\Knowledge\Value\Longtext;
use Ladb\CoreBundle\Entity\Knowledge\Value\Integer;
use Ladb\CoreBundle\Entity\Knowledge\Value\Picture;
use Ladb\CoreBundle\Entity\Knowledge\Value\Language;
use Ladb\CoreBundle\Entity\Knowledge\Value\Isbn;

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

	const FIELD_IDENTITY = 'identity';
	const FIELD_PHOTO = 'photo';
	const FIELD_MANUAL = 'manual';
	const FIELD_BRAND = 'brand';
	const FIELD_MODEL = 'model';
	const FIELD_FAMILY = 'family';
	const FIELD_DESCRIPTION = 'description';
	const FIELD_POWER_SUPPLY = 'power_supply';
	const FIELD_POWER = 'power';
	const FIELD_WEIGHT = 'weight';
	const FIELD_VIDEO = 'video';
	const FIELD_DOCS_LINK = 'docs_link';
	const FIELD_CATALOG_LINK = 'catalog_link';

	public static $FIELD_DEFS = array(
		Tool::FIELD_IDENTITY     => array(Tool::ATTRIB_TYPE => ToolIdentity::TYPE_STRIPPED_NAME, Tool::ATTRIB_MULTIPLE => false, Tool::ATTRIB_MANDATORY => true, Tool::ATTRIB_CONSTRAINTS => array(array('\\Ladb\\CoreBundle\\Validator\\Constraints\\UniqueTool', array('excludedId' => '@getId'))), Tool::ATTRIB_LINKED_FIELDS => array('name', 'isProduct', 'productName')),
		Tool::FIELD_PHOTO        => array(Tool::ATTRIB_TYPE => Picture::TYPE_STRIPPED_NAME, Tool::ATTRIB_MULTIPLE => false, Tool::ATTRIB_MANDATORY => true, Tool::ATTRIB_POST_PROCESSOR => \Ladb\CoreBundle\Entity\Core\Picture::POST_PROCESSOR_SQUARE),
		Tool::FIELD_MANUAL       => array(Tool::ATTRIB_TYPE => Pdf::TYPE_STRIPPED_NAME, Tool::ATTRIB_MULTIPLE => false),
		Tool::FIELD_BRAND        => array(Tool::ATTRIB_TYPE => Text::TYPE_STRIPPED_NAME, Tool::ATTRIB_MULTIPLE => false, Tool::ATTRIB_FILTER_QUERY => '@brand:"%q%"'),
		Tool::FIELD_MODEL        => array(Tool::ATTRIB_TYPE => Text::TYPE_STRIPPED_NAME, Tool::ATTRIB_MULTIPLE => false, Tool::ATTRIB_FILTER_QUERY => '@model:"%q%"'),
		Tool::FIELD_FAMILY       => array(Tool::ATTRIB_TYPE => Integer::TYPE_STRIPPED_NAME, Tool::ATTRIB_MULTIPLE => false, Tool::ATTRIB_CHOICES => array(1 => 'Outil à main', 2 => 'Electroportatif', 3 => 'Machine stationnaire', 4 => 'Gabarit')),
		Tool::FIELD_DESCRIPTION  => array(Tool::ATTRIB_TYPE => Longtext::TYPE_STRIPPED_NAME, Tool::ATTRIB_MULTIPLE => false),
		Tool::FIELD_POWER_SUPPLY => array(Tool::ATTRIB_TYPE => Integer::TYPE_STRIPPED_NAME, Tool::ATTRIB_MULTIPLE => false, Tool::ATTRIB_CHOICES => array(1 => 'Filaire', 2 => 'Batterie')),
		Tool::FIELD_POWER        => array(Tool::ATTRIB_TYPE => Integer::TYPE_STRIPPED_NAME, Tool::ATTRIB_MULTIPLE => false, Tool::ATTRIB_SUFFIX => 'W'),
		Tool::FIELD_WEIGHT       => array(Tool::ATTRIB_TYPE => Integer::TYPE_STRIPPED_NAME, Tool::ATTRIB_MULTIPLE => false, Tool::ATTRIB_SUFFIX => 'kg'),
		Tool::FIELD_VIDEO        => array(Tool::ATTRIB_TYPE => Video::TYPE_STRIPPED_NAME, Tool::ATTRIB_MULTIPLE => false),
		Tool::FIELD_DOCS_LINK    => array(Tool::ATTRIB_TYPE => Url::TYPE_STRIPPED_NAME, Tool::ATTRIB_MULTIPLE => true),
		Tool::FIELD_CATALOG_LINK => array(Tool::ATTRIB_TYPE => Url::TYPE_STRIPPED_NAME, Tool::ATTRIB_MULTIPLE => true, Tool::ATTRIB_CONSTRAINTS => array(array('\\Ladb\\CoreBundle\\Validator\\Constraints\\ExcludeDomainsLink', array('excludedDomainPaterns' => array('/amazon./i', '/fnac./i', '/manomano./i'), 'message' => 'Les liens vers les sites de revendeurs ou distributeurs et les liens affiliés ne sont pas autorisés ici.')))),
	);

	/**
	 * @ORM\Column(type="string", nullable=true, length=100)
	 */
	private $name;

	/**
	 * @ORM\Column(type="boolean", name="is_product")
	 */
	private $isProduct = false;

	/**
	 * @ORM\Column(type="string", nullable=true, length=100, name="product_name")
	 */
	private $productName;

	/**
	 * @ORM\Column(type="string", nullable=true, length=100)
	 */
	private $identity;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\ToolIdentity", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_tool_value_identity")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $identityValues;

	/**
	 * @ORM\Column(type="boolean", nullable=false, name="identity_rejected")
	 */
	private $identityRejected = false;


	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Picture", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_tool_value_photo")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $photoValues;

	/**
	 * @ORM\Column(type="boolean", nullable=false, name="photo_rejected")
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
	 * @ORM\Column(type="integer", nullable=true)
	 */
	private $family;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Integer", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_tool_value_family")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $familyValues;


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
	 * @ORM\Column(name="review_count", type="integer")
	 */
	private $reviewCount = 0;

	/**
	 * @ORM\Column(name="average_rating", type="float")
	 */
	private $averageRating = 0;

	/////

	public function __construct() {
		$this->identityValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->photoValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->manualValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->brandValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->familyValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->descriptionValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->videoValues = new \Doctrine\Common\Collections\ArrayCollection();
	}

	/////

	// IsRejected /////

	public function getIsRejected() {
		return $this->getIdentityRejected() || $this->getPhotoRejected();
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
		$this->setTitle($name);
		return $this;
	}

	public function getName() {
		return $this->name;
	}

	// IsProduct /////

	public function setIsProduct($isProduct) {
		$this->isProduct = $isProduct;
		return $this;
	}

	public function getIsProduct() {
		return $this->isProduct;
	}

	// productName /////

	public function setProductName($productName) {
		$this->productName = $productName;
		return $this;
	}

	public function getProductName() {
		return $this->productName;
	}

	// Identity /////

	public function setIdentity($identity) {
		$this->identity = $identity;
		if (!is_null($identity)) {
			$this->setTitle(explode(',', $identity)[0]);
		} else {
			$this->setTitle(null);
		}
		return $this;
	}

	public function getIdentity() {
		return $this->identity;
	}

	// IdentityValues /////

	public function addIdentityValue(\Ladb\CoreBundle\Entity\Knowledge\Value\ToolIdentity $identityValue) {
		if (!$this->identityValues->contains($identityValue)) {
			$this->identityValues[] = $identityValue;
		}
		return $this;
	}

	public function removeIdentityValue(\Ladb\CoreBundle\Entity\Knowledge\Value\ToolIdentity $identityValue) {
		$this->identityValues->removeElement($identityValue);
	}

	public function setIdentityValues($identityValues) {
		$this->identityValues = $identityValues;
	}

	public function getIdentityValues() {
		return $this->identityValues;
	}

	// IdentityRejected /////

	public function setIdentityRejected($identityRejected) {
		$this->identityRejected = $identityRejected;
		return $this;
	}

	public function getIdentityRejected() {
		return $this->identityRejected;
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

}