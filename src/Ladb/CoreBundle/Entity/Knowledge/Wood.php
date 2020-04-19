<?php

namespace Ladb\CoreBundle\Entity\Knowledge;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ladb\CoreBundle\Entity\Knowledge\Value\Url;
use Symfony\Component\Validator\Constraints as Assert;
use Ladb\CoreBundle\Entity\Knowledge\Value\Text;
use Ladb\CoreBundle\Entity\Knowledge\Value\Integer;
use Ladb\CoreBundle\Entity\Knowledge\Value\Picture;

/**
 * Ladb\CoreBundle\Entity\Knowledge\Wood
 *
 * @ORM\Table("tbl_knowledge2_wood")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Knowledge\WoodRepository")
 */
class Wood extends AbstractKnowledge {

	const CLASS_NAME = 'LadbCoreBundle:Knowledge\Wood';
	const TYPE = 109;

	const STRIPPED_NAME = 'wood';

	const FIELD_NAME = 'name';
	const FIELD_SCIENTIFICNAME = 'scientificname';
	const FIELD_ENGLISHNAME = 'englishname';
	const FIELD_GRAIN = 'grain';
	const FIELD_ENDGRAIN = 'endgrain';
	const FIELD_LUMBER = 'lumber';
	const FIELD_TREE = 'tree';
	const FIELD_LEAF = 'leaf';
	const FIELD_BARK = 'bark';
	const FIELD_FAMILY = 'family';
	const FIELD_DENSITY = 'density';
	const FIELD_AVAILABILITY = 'availability';
	const FIELD_PRICE = 'price';
	const FIELD_ORIGIN = 'origin';
	const FIELD_UTILIZATION = 'utilization';
	const FIELD_DATABASE_LINK = 'database_link';

	public static $FIELD_DEFS = array(
		Wood::FIELD_NAME           => array(Wood::ATTRIB_TYPE => Text::TYPE_STRIPPED_NAME, Wood::ATTRIB_MULTIPLE => true, Wood::ATTRIB_MANDATORY => true, Wood::ATTRIB_CONSTRAINTS => array(array('\\Ladb\\CoreBundle\\Validator\\Constraints\\UniqueWood', array('excludedId' => '@getId'))), Wood::ATTRIB_DATA_CONSTRAINTS => array( array('\\Ladb\\CoreBundle\\Validator\\Constraints\\OneThing', array('message' => 'N\'indiquez qu\'un seul Nom français par proposition.')))),
		Wood::FIELD_SCIENTIFICNAME => array(Wood::ATTRIB_TYPE => Text::TYPE_STRIPPED_NAME, Wood::ATTRIB_MULTIPLE => true, Wood::ATTRIB_DATA_CONSTRAINTS => array(array('\\Ladb\\CoreBundle\\Validator\\Constraints\\OneThing', array('message' => 'N\'indiquez qu\'un seul Nom scientifique par proposition.')))),
		Wood::FIELD_ENGLISHNAME    => array(Wood::ATTRIB_TYPE => Text::TYPE_STRIPPED_NAME, Wood::ATTRIB_MULTIPLE => true, Wood::ATTRIB_DATA_CONSTRAINTS => array(array('\\Ladb\\CoreBundle\\Validator\\Constraints\\OneThing', array('message' => 'N\'indiquez qu\'un seul nom anglais par proposition.')))),
		Wood::FIELD_GRAIN          => array(Wood::ATTRIB_TYPE => Picture::TYPE_STRIPPED_NAME, Wood::ATTRIB_MULTIPLE => false, Wood::ATTRIB_MANDATORY => true),
		Wood::FIELD_ENDGRAIN       => array(Wood::ATTRIB_TYPE => Picture::TYPE_STRIPPED_NAME, Wood::ATTRIB_MULTIPLE => false),
		Wood::FIELD_LUMBER         => array(Wood::ATTRIB_TYPE => Picture::TYPE_STRIPPED_NAME, Wood::ATTRIB_MULTIPLE => false),
		Wood::FIELD_TREE           => array(Wood::ATTRIB_TYPE => Picture::TYPE_STRIPPED_NAME, Wood::ATTRIB_MULTIPLE => false),
		Wood::FIELD_LEAF           => array(Wood::ATTRIB_TYPE => Picture::TYPE_STRIPPED_NAME, Wood::ATTRIB_MULTIPLE => false),
		Wood::FIELD_BARK           => array(Wood::ATTRIB_TYPE => Picture::TYPE_STRIPPED_NAME, Wood::ATTRIB_MULTIPLE => false),
		Wood::FIELD_FAMILY         => array(Wood::ATTRIB_TYPE => Integer::TYPE_STRIPPED_NAME, Wood::ATTRIB_MULTIPLE => false, Wood::ATTRIB_CHOICES => array(1 => 'Feuillus', 2 => 'Résineux')),
		Wood::FIELD_DENSITY        => array(Wood::ATTRIB_TYPE => Integer::TYPE_STRIPPED_NAME, Wood::ATTRIB_MULTIPLE => false, Wood::ATTRIB_SUFFIX => 'kg/m<sup>3</sup>'),
		Wood::FIELD_AVAILABILITY   => array(Wood::ATTRIB_TYPE => Integer::TYPE_STRIPPED_NAME, Wood::ATTRIB_MULTIPLE => false, Wood::ATTRIB_CHOICES => array(1 => 'Menacée', 2 => 'Limitée', 3 => 'Régulière', 4 => 'Importante')),
		Wood::FIELD_PRICE          => array(Wood::ATTRIB_TYPE => Integer::TYPE_STRIPPED_NAME, Wood::ATTRIB_MULTIPLE => false, Wood::ATTRIB_CHOICES => array(1 => 'Modéré', 2 => 'Moyen', 3 => 'Elevé')),
		Wood::FIELD_ORIGIN         => array(Wood::ATTRIB_TYPE => Text::TYPE_STRIPPED_NAME, Wood::ATTRIB_MULTIPLE => true, Wood::ATTRIB_DATA_CONSTRAINTS => array(array('\\Ladb\\CoreBundle\\Validator\\Constraints\\OneThing', array('message' => 'N\'indiquez qu\'une seule provenance par proposition.'))), Wood::ATTRIB_FILTER_QUERY => '@origin:"%q%"'),
		Wood::FIELD_UTILIZATION    => array(Wood::ATTRIB_TYPE => Text::TYPE_STRIPPED_NAME, Wood::ATTRIB_MULTIPLE => true, Wood::ATTRIB_DATA_CONSTRAINTS => array(array('\\Ladb\\CoreBundle\\Validator\\Constraints\\OneThing', array('message' => 'N\'indiquez qu\'une seule utilisation par proposition.'))), Wood::ATTRIB_FILTER_QUERY => '@utilization:"%q%"'),
		Wood::FIELD_DATABASE_LINK  => array(Wood::ATTRIB_TYPE => Url::TYPE_STRIPPED_NAME, Wood::ATTRIB_MULTIPLE => true),
	);

	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	private $name;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Text", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_wood_value_name")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $nameValues;

	/**
	 * @ORM\Column(type="boolean", nullable=false, name="name_rejected")
	 */
	private $nameRejected = false;


	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	private $scientificname;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Text", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_wood_value_scientificname")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $scientificnameValues;


	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	private $englishname;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Text", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_wood_value_englishname")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $englishnameValues;


	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Picture", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_wood_value_grain")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $grainValues;

	/**
	 * @ORM\Column(type="boolean", nullable=false, name="grain_rejected")
	 */
	private $grainRejected = false;


	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\Picture", cascade={"persist"})
	 * @ORM\JoinColumn(name="endgrain_id", nullable=true)
	 * @Assert\Type(type="Ladb\CoreBundle\Entity\Core\Picture")
	 */
	private $endgrain;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Picture", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_wood_value_endgrain")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $endgrainValues;


	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\Picture", cascade={"persist"})
	 * @ORM\JoinColumn(name="lumber_id", nullable=true)
	 * @Assert\Type(type="Ladb\CoreBundle\Entity\Core\Picture")
 	 */
	private $lumber;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Picture", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_wood_value_lumber")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $lumberValues;


	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\Picture", cascade={"persist"})
	 * @ORM\JoinColumn(name="tree_id", nullable=true)
	 * @Assert\Type(type="Ladb\CoreBundle\Entity\Core\Picture")
 	 */
	private $tree;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Picture", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_wood_value_tree")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $treeValues;


	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\Picture", cascade={"persist"})
	 * @ORM\JoinColumn(name="leaf_id", nullable=true)
	 * @Assert\Type(type="Ladb\CoreBundle\Entity\Core\Picture")
 	 */
	private $leaf;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Picture", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_wood_value_leaf")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $leafValues;


	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\Picture", cascade={"persist"})
	 * @ORM\JoinColumn(name="bark_id", nullable=true)
	 * @Assert\Type(type="Ladb\CoreBundle\Entity\Core\Picture")
 	 */
	private $bark;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Picture", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_wood_value_bark")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $barkValues;


	/**
	 * @ORM\Column(type="integer", nullable=true)
	 */
	private $family;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Integer", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_wood_value_family")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $familyValues;


	/**
	 * @ORM\Column(type="integer", nullable=true)
	 */
	private $density;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Integer", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_wood_value_density")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $densityValues;


	/**
	 * @ORM\Column(type="integer", nullable=true)
 	 */
	private $availability;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Integer", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_wood_value_availability")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $availabilityValues;


	/**
	 * @ORM\Column(type="integer", nullable=true)
 	 */
	private $price;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Integer", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_wood_value_price")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $priceValues;


	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	private $origin;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Text", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_wood_value_origin")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $originValues;


	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	private $utilization;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Text", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_wood_value_utilization")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $utilizationValues;


	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	private $databaseLink;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Url", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_wood_value_database_link")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $databaseLinkValues;


	/**
	 * @ORM\Column(name="texture_count", type="integer")
	 */
	private $textureCount = 0;

	/**
	 * @ORM\OneToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Wood\Texture", mappedBy="wood", cascade={"all"})
	 */
	private $textures;

	/////

	public function __construct() {
		$this->nameValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->scientificnameValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->englishnameValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->grainValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->endgrainValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->lumberValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->treeValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->leafValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->barkValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->familyValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->densityValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->availabilityValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->priceValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->originValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->utilizationValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->databaseLinkValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->textures = new \Doctrine\Common\Collections\ArrayCollection();
	}

	/////

	// IsRejected /////

	public function getIsRejected() {
		return $this->getNameRejected() || $this->getGrainRejected();
	}

	// Type /////

	public function getType() {
		return Wood::TYPE;
	}

	// Title /////

	public function getTitleWorkaround() {
		return $this->getTitle();
	}

	// Body /////

	public function getBody() {
		$terms = array($this->getName());
		if (!empty($this->getScientificname())) {
			$terms[] = $this->getScientificname();
		}
		if (!empty($this->getEnglishname())) {
			$terms[] = $this->getEnglishname();
		}
		return implode($terms, ',');
	}

	// StrippedName /////

	public function getStrippedName() {
		return Wood::STRIPPED_NAME;
	}

	// FieldDefs /////

	public function getFieldDefs() {
		return Wood::$FIELD_DEFS;
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

	// Scientificname /////

	public function setScientificname($name) {
		$this->scientificname = ucfirst($name);
		return $this;
	}

	public function getScientificname() {
		return $this->scientificname;
	}

	// ScientificnameValues /////

	public function addScientificnameValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Text $scientificnameValue) {
		if (!$this->scientificnameValues->contains($scientificnameValue)) {
			$this->scientificnameValues[] = $scientificnameValue;
		}
		return $this;
	}

	public function removeScientificnameValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Text $scientificnameValue) {
		$this->scientificnameValues->removeElement($scientificnameValue);
	}

	public function setScientificnameValues($scientificnameValues) {
		$this->scientificnameValues = $scientificnameValues;
	}

	public function getScientificnameValues() {
		return $this->scientificnameValues;
	}

	// Englishname /////

	public function setEnglishname($name) {
		$this->englishname = ucfirst($name);
		return $this;
	}

	public function getEnglishname() {
		return $this->englishname;
	}

	// EnglishnameValues /////

	public function addEnglishnameValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Text $englishnameValue) {
		if (!$this->englishnameValues->contains($englishnameValue)) {
			$this->englishnameValues[] = $englishnameValue;
		}
		return $this;
	}

	public function removeEnglishnameValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Text $englishnameValue) {
		$this->englishnameValues->removeElement($englishnameValue);
	}

	public function setEnglishnameValues($englishnameValues) {
		$this->englishnameValues = $englishnameValues;
	}

	public function getEnglishnameValues() {
		return $this->englishnameValues;
	}

	// Grain /////

	public function setGrain($grain) {
		return $this->setMainPicture($grain);
	}

	public function getGrain() {
		return $this->getMainPicture();
	}

	// GrainValues /////

	public function addGrainValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Picture $grainValue) {
		if (!$this->grainValues->contains($grainValue)) {
			$this->grainValues[] = $grainValue;
		}
		return $this;
	}

	public function removeGrainValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Picture $grainValue) {
		$this->grainValues->removeElement($grainValue);
	}

	public function setGrainValues($grainValues) {
		$this->grainValues = $grainValues;
	}

	public function getGrainValues() {
		return $this->grainValues;
	}

	// GrainRejected /////

	public function setGrainRejected($grainRejected) {
		$this->grainRejected = $grainRejected;
		return $this;
	}

	public function getGrainRejected() {
		return $this->grainRejected;
	}

	// Endgrain /////

	public function setEndgrain($endgrain) {
		$this->endgrain = $endgrain;
		return $this;
	}

	public function getEndgrain() {
		return $this->endgrain;
	}

	// EndgrainValues /////

	public function addEndgrainValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Picture $endgrainValue) {
		if (!$this->endgrainValues->contains($endgrainValue)) {
			$this->endgrainValues[] = $endgrainValue;
		}
		return $this;
	}

	public function removeEndgrainValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Picture $endgrainValue) {
		$this->endgrainValues->removeElement($endgrainValue);
	}

	public function setEndgrainValues($endgrainValues) {
		$this->endgrainValues = $endgrainValues;
	}

	public function getEndgrainValues() {
		return $this->endgrainValues;
	}

	// Lumber /////

	public function setLumber($lumber) {
		$this->lumber = $lumber;
		return $this;
	}

	public function getLumber() {
		return $this->lumber;
	}

	// LumberValues /////

	public function addLumberValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Picture $lumberValue) {
		if (!$this->lumberValues->contains($lumberValue)) {
			$this->lumberValues[] = $lumberValue;
		}
		return $this;
	}

	public function removeLumberValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Picture $lumberValue) {
		$this->lumberValues->removeElement($lumberValue);
	}

	public function setLumberValues($lumberValues) {
		$this->lumberValues = $lumberValues;
	}

	public function getLumberValues() {
		return $this->lumberValues;
	}

	// Tree /////

	public function setTree($tree) {
		$this->tree = $tree;
		return $this;
	}

	public function getTree() {
		return $this->tree;
	}

	// TreeValues /////

	public function addTreeValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Picture $treeValue) {
		if (!$this->treeValues->contains($treeValue)) {
			$this->treeValues[] = $treeValue;
		}
		return $this;
	}

	public function removeTreeValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Picture $treeValue) {
		$this->treeValues->removeElement($treeValue);
	}

	public function setTreeValues($treeValues) {
		$this->treeValues = $treeValues;
	}

	public function getTreeValues() {
		return $this->treeValues;
	}

	// Leaf /////

	public function setLeaf($leaf) {
		$this->leaf = $leaf;
		return $this;
	}

	public function getLeaf() {
		return $this->leaf;
	}

	// LeafValues /////

	public function addLeafValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Picture $leafValue) {
		if (!$this->leafValues->contains($leafValue)) {
			$this->leafValues[] = $leafValue;
		}
		return $this;
	}

	public function removeLeafValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Picture $leafValue) {
		$this->leafValues->removeElement($leafValue);
	}

	public function setLeafValues($leafValues) {
		$this->leafValues = $leafValues;
	}

	public function getLeafValues() {
		return $this->leafValues;
	}

	// Bark /////

	public function setBark($bark) {
		$this->bark = $bark;
		return $this;
	}

	public function getBark() {
		return $this->bark;
	}

	// BarkValues /////

	public function addBarkValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Picture $barkValue) {
		if (!$this->barkValues->contains($barkValue)) {
			$this->barkValues[] = $barkValue;
		}
		return $this;
	}

	public function removeBarkValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Picture $barkValue) {
		$this->barkValues->removeElement($barkValue);
	}

	public function setBarkValues($barkValues) {
		$this->barkValues = $barkValues;
	}

	public function getBarkValues() {
		return $this->barkValues;
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

	public function addFamilyValue($familyValue) {
		if (!$this->familyValues->contains($familyValue)) {
			$this->familyValues[] = $familyValue;
		}
		return $this;
	}

	public function removeFamilyValue($familyValue) {
		$this->familyValues->removeElement($familyValue);
	}

	public function setFamilyValues($familyValues) {
		$this->familyValues = $familyValues;
	}

	public function getFamilyValues() {
		return $this->familyValues;
	}

	// Density /////

	public function setDensity($density) {
		$this->density = $density;
		return $this;
	}

	public function getDensity() {
		return $this->density;
	}

	// DensityValues /////

	public function addDensityValue($densityValue) {
		if (!$this->densityValues->contains($densityValue)) {
			$this->densityValues[] = $densityValue;
		}
		return $this;
	}

	public function removeDensityValue($densityValue) {
		$this->densityValues->removeElement($densityValue);
	}

	public function setDensityValues($densityValues) {
		$this->densityValues = $densityValues;
	}

	public function getDensityValues() {
		return $this->densityValues;
	}

	// Availability /////

	public function setAvailability($availability) {
		$this->availability = $availability;
		return $this;
	}

	public function getAvailability() {
		return $this->availability;
	}

	// AvailabilityValues /////

	public function addAvailabilityValue($availabilityValue) {
		if (!$this->availabilityValues->contains($availabilityValue)) {
			$this->availabilityValues[] = $availabilityValue;
		}
		return $this;
	}

	public function removeAvailabilityValue($availabilityValue) {
		$this->availabilityValues->removeElement($availabilityValue);
	}

	public function setAvailabilityValues($availabilityValues) {
		$this->availabilityValues = $availabilityValues;
	}

	public function getAvailabilityValues() {
		return $this->availabilityValues;
	}

	// Price /////

	public function setPrice($price) {
		$this->price = $price;
		return $this;
	}

	public function getPrice() {
		return $this->price;
	}

	// PriceValues /////

	public function addPriceValue($priceValue) {
		if (!$this->priceValues->contains($priceValue)) {
			$this->priceValues[] = $priceValue;
		}
		return $this;
	}

	public function removePriceValue($priceValue) {
		$this->priceValues->removeElement($priceValue);
	}

	public function setPriceValues($priceValues) {
		$this->priceValues = $priceValues;
	}

	public function getPriceValues() {
		return $this->priceValues;
	}

	// Origin /////

	public function setOrigin($origin) {
		$this->origin = $origin;
		return $this;
	}

	public function getOrigin() {
		return $this->origin;
	}

	// OriginValues /////

	public function addOriginValue($originValue) {
		if (!$this->originValues->contains($originValue)) {
			$this->originValues[] = $originValue;
		}
		return $this;
	}

	public function removeOriginValue($originValue) {
		$this->originValues->removeElement($originValue);
	}

	public function setOriginValues($originValues) {
		$this->originValues = $originValues;
	}

	public function getOriginValues() {
		return $this->originValues;
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

	public function addUtilizationValue($utilizationValue) {
		if (!$this->utilizationValues->contains($utilizationValue)) {
			$this->utilizationValues[] = $utilizationValue;
		}
		return $this;
	}

	public function removeUtilizationValue($utilizationValue) {
		$this->utilizationValues->removeElement($utilizationValue);
	}

	public function setUtilizationValues($utilizationValues) {
		$this->utilizationValues = $utilizationValues;
	}

	public function getUtilizationValues() {
		return $this->utilizationValues;
	}

	// DatabaseLink /////

	public function setDatabaseLink($databaseLink) {
		$this->databaseLink = $databaseLink;
		return $this;
	}

	public function getDatabaseLink() {
		return $this->databaseLink;
	}

	// DatabaseLinkValues /////

	public function addDatabaseLinkValue($databaseLinkValue) {
		if (!$this->databaseLinkValues->contains($databaseLinkValue)) {
			$this->databaseLinkValues[] = $databaseLinkValue;
		}
		return $this;
	}

	public function removeDatabaseLinkValue($databaseLinkValue) {
		$this->databaseLinkValues->removeElement($databaseLinkValue);
	}

	public function setDatabaseLinkValues($databaseLinkValues) {
		$this->databaseLinkValues = $databaseLinkValues;
	}

	public function getDatabaseLinkValues() {
		return $this->databaseLinkValues;
	}

	// TextureCount /////

	public function incrementTextureCount($by = 1) {
		return $this->textureCount += intval($by);
	}

	public function setTextureCount($textureCount) {
		$this->textureCount = $textureCount;
		return $this;
	}

	public function getTextureCount() {
		return $this->textureCount;
	}

	// Textures /////

	public function addTexture($texture) {
		if (!$this->textures->contains($texture)) {
			$this->textures[] = $texture;
		}
		return $this;
	}

	public function removeTexture($texture) {
		$this->textures->removeElement($texture);
	}

	public function getTextures() {
		return $this->textures;
	}

}