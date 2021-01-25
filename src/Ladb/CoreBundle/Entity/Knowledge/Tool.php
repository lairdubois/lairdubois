<?php

namespace Ladb\CoreBundle\Entity\Knowledge;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ladb\CoreBundle\Entity\Knowledge\Value\Pdf;
use Ladb\CoreBundle\Entity\Knowledge\Value\ToolIdentity;
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
	const FIELD_BRAND = 'brand';
	const FIELD_USER_GUIDE = 'user_guide';

	public static $FIELD_DEFS = array(
		Tool::FIELD_IDENTITY   => array(Tool::ATTRIB_TYPE => ToolIdentity::TYPE_STRIPPED_NAME, Tool::ATTRIB_MULTIPLE => false, Tool::ATTRIB_MANDATORY => true, Tool::ATTRIB_CONSTRAINTS => array(array('\\Ladb\\CoreBundle\\Validator\\Constraints\\UniqueTool', array('excludedId' => '@getId'))), Software::ATTRIB_LINKED_FIELDS => array('name', 'isProduct', 'productName')),
		Tool::FIELD_PHOTO      => array(Tool::ATTRIB_TYPE => Picture::TYPE_STRIPPED_NAME, Tool::ATTRIB_MULTIPLE => false, Tool::ATTRIB_MANDATORY => true, Tool::ATTRIB_POST_PROCESSOR => \Ladb\CoreBundle\Entity\Core\Picture::POST_PROCESSOR_SQUARE),
		Tool::FIELD_BRAND      => array(Tool::ATTRIB_TYPE => Text::TYPE_STRIPPED_NAME, Tool::ATTRIB_MULTIPLE => false, Tool::ATTRIB_FILTER_QUERY => '@brand:"%q%"'),
		Tool::FIELD_USER_GUIDE => array(Tool::ATTRIB_TYPE => Pdf::TYPE_STRIPPED_NAME, Tool::ATTRIB_MULTIPLE => false),
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
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\Resource", cascade={"persist"})
	 * @ORM\JoinColumn(name="user_guide_id", nullable=true)
	 * @Assert\Type(type="Ladb\CoreBundle\Entity\Core\Resource")
	 */
	private $userGuide;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Pdf", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_tool_value_user_guide")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $userGuideValues;


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
		$this->brandValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->userGuideValues = new \Doctrine\Common\Collections\ArrayCollection();
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

	// UserGuides /////

	public function setUserGuide($userGuide) {
		$this->userGuide = $userGuide;
		return $this;
	}

	public function getUserGuide() {
		return $this->userGuide;
	}

	// UserGuideValues /////

	public function addUserGuideValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Pdf $userGuide) {
		if (!$this->userGuideValues->contains($userGuide)) {
			$this->userGuideValues[] = $userGuide;
		}
		return $this;
	}

	public function removeUserGuideValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Pdf $userGuide) {
		$this->userGuideValues->removeElement($userGuide);
	}

	public function setUserGuideValues($userGuideValues) {
		$this->userGuideValues = $userGuideValues;
	}

	public function getUserGuideValues() {
		return $this->userGuideValues;
	}

}