<?php

namespace Ladb\CoreBundle\Entity\Knowledge;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ladb\CoreBundle\Entity\Knowledge\Value\LinkableText;
use Ladb\CoreBundle\Entity\Knowledge\Value\Video;
use Symfony\Component\Validator\Constraints as Assert;
use Ladb\CoreBundle\Model\ReviewableInterface;
use Ladb\CoreBundle\Model\ReviewableTrait;
use Ladb\CoreBundle\Entity\Knowledge\Value\FileExtension;
use Ladb\CoreBundle\Entity\Knowledge\Value\SoftwareIdentity;
use Ladb\CoreBundle\Entity\Knowledge\Value\Language;
use Ladb\CoreBundle\Entity\Knowledge\Value\Integer;
use Ladb\CoreBundle\Entity\Knowledge\Value\Url;
use Ladb\CoreBundle\Entity\Knowledge\Value\Longtext;
use Ladb\CoreBundle\Entity\Knowledge\Value\Text;
use Ladb\CoreBundle\Entity\Knowledge\Value\Picture;

/**
 * Ladb\CoreBundle\Entity\Knowledge\Software
 *
 * @ORM\Table("tbl_knowledge2_software")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Knowledge\SoftwareRepository")
 */
class Software extends AbstractKnowledge implements ReviewableInterface {

	use ReviewableTrait;

	const CLASS_NAME = 'LadbCoreBundle:Knowledge\Software';
	const TYPE = 121;

	const STRIPPED_NAME = 'software';

	const FIELD_IDENTITY = 'identity';
	const FIELD_ICON = 'icon';
	const FIELD_SCREENSHOT = 'screenshot';
	const FIELD_AUTHORS = 'authors';
	const FIELD_PUBLISHER = 'publisher';
	const FIELD_LAST_VERSION  = 'last_version';
	const FIELD_WEBSITE  = 'website';
	const FIELD_DESCRIPTION  = 'description';
	const FIELD_OPEN_SOURCE  = 'open_source';
	const FIELD_SOURCE_CODE_REPOSITORY  = 'source_code_repository';
	const FIELD_DOCS  = 'docs';
	const FIELD_VIDEO  = 'video';
	const FIELD_OPERATING_SYSTEMS  = 'operating_systems';
	const FIELD_LICENSE_TYPE  = 'license_type';
	const FIELD_PRICINGS  = 'pricings';
	const FIELD_FEATURES  = 'features';
	const FIELD_LANGUAGES  = 'languages';
	const FIELD_SUPPORTED_FILES  = 'supported_files';

	public static $FIELD_DEFS = array(
		Software::FIELD_IDENTITY                     => array(Software::ATTRIB_TYPE => SoftwareIdentity::TYPE_STRIPPED_NAME, Software::ATTRIB_MULTIPLE => false, Software::ATTRIB_MANDATORY => true, Software::ATTRIB_CONSTRAINTS => array(array('\\Ladb\\CoreBundle\\Validator\\Constraints\\UniqueSoftware', array('excludedId' => '@getId'))), Software::ATTRIB_LINKED_FIELDS => array('name', 'isAddOn', 'hostSoftwareName')),
		Software::FIELD_ICON                         => array(Software::ATTRIB_TYPE => Picture::TYPE_STRIPPED_NAME, Software::ATTRIB_MULTIPLE => false, Software::ATTRIB_MANDATORY => true, Software::ATTRIB_QUALITY => \Ladb\CoreBundle\Entity\Core\Picture::QUALITY_LD, Software::ATTRIB_POST_PROCESSOR => \Ladb\CoreBundle\Entity\Core\Picture::POST_PROCESSOR_SQUARE),
		Software::FIELD_SCREENSHOT                   => array(Software::ATTRIB_TYPE => Picture::TYPE_STRIPPED_NAME, Software::ATTRIB_MULTIPLE => false),
		Software::FIELD_AUTHORS                      => array(Software::ATTRIB_TYPE => Text::TYPE_STRIPPED_NAME, Software::ATTRIB_MULTIPLE => true, Software::ATTRIB_FILTER_QUERY => '@authors:"%q%"', Software::ATTRIB_DATA_CONSTRAINTS => array(array('\\Ladb\\CoreBundle\\Validator\\Constraints\\OneThing', array('message' => 'N\'indiquez qu\'un seul auteur par proposition.')))),
		Software::FIELD_PUBLISHER                    => array(Software::ATTRIB_TYPE => Text::TYPE_STRIPPED_NAME, Software::ATTRIB_MULTIPLE => false, Software::ATTRIB_FILTER_QUERY => '@publisher:"%q%"'),
		Software::FIELD_LAST_VERSION                 => array(Software::ATTRIB_TYPE => Text::TYPE_STRIPPED_NAME, Software::ATTRIB_MULTIPLE => false),
		Software::FIELD_WEBSITE                      => array(Software::ATTRIB_TYPE => Url::TYPE_STRIPPED_NAME, Software::ATTRIB_MULTIPLE => false),
		Software::FIELD_DESCRIPTION                  => array(Software::ATTRIB_TYPE => Longtext::TYPE_STRIPPED_NAME, Software::ATTRIB_MULTIPLE => false),
		Software::FIELD_OPEN_SOURCE                  => array(Software::ATTRIB_TYPE => Integer::TYPE_STRIPPED_NAME, Software::ATTRIB_MULTIPLE => false, Software::ATTRIB_CHOICES => array(1 => 'Oui', 0 => 'Non')),
		Software::FIELD_SOURCE_CODE_REPOSITORY       => array(Software::ATTRIB_TYPE => Url::TYPE_STRIPPED_NAME, Software::ATTRIB_MULTIPLE => false),
		Software::FIELD_DOCS				         => array(Software::ATTRIB_TYPE => Url::TYPE_STRIPPED_NAME, Software::ATTRIB_MULTIPLE => true),
		Software::FIELD_VIDEO				         => array(Software::ATTRIB_TYPE => Video::TYPE_STRIPPED_NAME, Software::ATTRIB_MULTIPLE => false),
		Software::FIELD_OPERATING_SYSTEMS            => array(Software::ATTRIB_TYPE => Integer::TYPE_STRIPPED_NAME, Software::ATTRIB_MULTIPLE => true, Software::ATTRIB_CHOICES => array(0 => 'Windows', 1 => 'Mac', 2 => 'Linux', 3 => 'Android', 4 => 'iOS'), Software::ATTRIB_USE_CHOICES_VALUE => true, Software::ATTRIB_FILTER_QUERY => '@os:"%q%"'),
		Software::FIELD_LICENSE_TYPE                 => array(Software::ATTRIB_TYPE => LinkableText::TYPE_STRIPPED_NAME, Software::ATTRIB_MULTIPLE => false),
		Software::FIELD_PRICINGS                     => array(Software::ATTRIB_TYPE => Integer::TYPE_STRIPPED_NAME, Software::ATTRIB_MULTIPLE => true, Software::ATTRIB_CHOICES => array(0 => 'Gratuit', 1 => 'Gratuit pour usage personnel', 2 => 'Gratuit avec fonctionnalités limitées', 3 => 'Payant', 4 => 'Financement participatif'), Software::ATTRIB_USE_CHOICES_VALUE => true, Software::ATTRIB_FILTER_QUERY => '@pricings:"%q%"'),
		Software::FIELD_FEATURES                     => array(Software::ATTRIB_TYPE => Text::TYPE_STRIPPED_NAME, Software::ATTRIB_MULTIPLE => true, Software::ATTRIB_FILTER_QUERY => '@features:"%q%"', Software::ATTRIB_DATA_CONSTRAINTS => array(array('\\Ladb\\CoreBundle\\Validator\\Constraints\\OneThing', array('message' => 'N\'indiquez qu\'une seule fonctionnalité par proposition.')))),
		Software::FIELD_LANGUAGES                    => array(Software::ATTRIB_TYPE => Language::TYPE_STRIPPED_NAME, Software::ATTRIB_MULTIPLE => true, Software::ATTRIB_FILTER_QUERY => '@languages:"%q%"'),
		Software::FIELD_SUPPORTED_FILES              => array(Software::ATTRIB_TYPE => FileExtension::TYPE_STRIPPED_NAME, Software::ATTRIB_MULTIPLE => true, Software::ATTRIB_FILTER_QUERY => '@supported-files:"%q%"'),
	);

	/**
	 * @ORM\Column(type="string", nullable=true, length=100)
	 */
	private $name;

	/**
	 * @ORM\Column(type="boolean", name="is_addon")
	 */
	private $isAddOn = false;

	/**
	 * @ORM\Column(type="string", nullable=true, length=100, name="host_software_name")
	 */
	private $hostSoftwareName;

	/**
	 * @ORM\Column(type="string", nullable=true, length=100)
	 */
	private $identity;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\SoftwareIdentity", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_software_value_identity")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $identityValues;

	/**
	 * @ORM\Column(type="boolean", nullable=false, name="name_rejected")
	 */
	private $identityRejected = false;


	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Picture", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_software_value_icon")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $iconValues;

	/**
	 * @ORM\Column(type="boolean", nullable=false, name="icon_rejected")
	 */
	private $iconRejected = false;


	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\Picture", cascade={"persist"})
	 * @ORM\JoinColumn(name="screenshot_id", nullable=true)
	 * @Assert\Type(type="Ladb\CoreBundle\Entity\Core\Picture")
	 */
	private $screenshot;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Picture", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_software_value_screenshot")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $screenshotValues;


	/**
	 * @ORM\Column(type="string", nullable=true)
	 */
	private $authors;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Text", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_software_value_authors")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $authorsValues;


	/**
	 * @ORM\Column(type="string", nullable=true, name="last_version")
	 */
	private $lastVersion;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Text", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_software_value_last_version")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $lastVersionValues;


	/**
	 * @ORM\Column(type="string", nullable=true)
	 */
	private $publisher;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Text", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_software_value_publisher")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $publisherValues;


	/**
	 * @ORM\Column(type="string", nullable=true, length=2048)
	 */
	private $website;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Url", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_software_value_website")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $websiteValues;


	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	private $description;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Longtext", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_software_value_description")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $descriptionValues;


	/**
	 * @ORM\Column(type="boolean", nullable=true, name="open_source")
	 */
	private $openSource;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Integer", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_software_value_open_source")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $openSourceValues;


	/**
	 * @ORM\Column(type="string", nullable=true, length=2048, name="source_core_repository")
	 */
	private $sourceCodeRepository;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Url", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_software_value_source_code_repository")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $sourceCodeRepositoryValues;


	/**
	 * @ORM\Column(type="text", nullable=true, name="source_docs")
	 */
	private $docs;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Url", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_software_value_docs")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $docsValues;


	/**
	 * @ORM\Column(type="string", nullable=true, length=255, name="video")
	 */
	private $video;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Video", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_software_value_video")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $videoValues;


	/**
	 * @ORM\Column(type="text", nullable=true, name="operating_systems")
	 */
	private $operatingSystems;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Integer", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_software_value_operating_systems")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $operatingSystemsValues;


	/**
	 * @ORM\Column(type="string", nullable=true, name="license_type")
	 */
	private $licenseType;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\LinkableText", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_software_value_license_type")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $licenseTypeValues;


	/**
	 * @ORM\Column(type="string", nullable=true)
	 */
	private $pricings;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Integer", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_software_value_pricings")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $pricingsValues;


	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	private $features;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Text", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_software_value_features")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $featuresValues;


	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	private $languages;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Language", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_software_value_languages")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $languagesValues;


	/**
	 * @ORM\Column(type="text", nullable=true, name="supported_files")
	 */
	private $supportedFiles;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\FileExtension", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_software_value_supported_files")
	 * @ORM\OrderBy({"moderationScore" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $supportedFilesValues;


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
		$this->iconValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->screenshotValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->authorsValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->publisherValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->lastVersionValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->websiteValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->descriptionValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->openSourceValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->sourceCodeRepositoryValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->sourceDocsValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->operatingSystemsValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->licenseTypeValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->pricingsValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->featuresValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->languagesValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->supportedFilesValues = new \Doctrine\Common\Collections\ArrayCollection();
	}

	/////

	// IsRejected /////

	public function getIsRejected() {
		return $this->getIdentityRejected() || $this->getIconRejected();
	}

	// Type /////

	public function getType() {
		return Software::TYPE;
	}

	// Body /////

	public function getBody() {
		if (!empty($this->getDescription())) {
			return $this->getDescription();
		}
		$terms = array($this->getIdentity());
		return implode($terms, ',');
	}

	// StrippedName /////

	public function getStrippedName() {
		return Software::STRIPPED_NAME;
	}

	// FieldDefs /////

	public function getFieldDefs() {
		return Software::$FIELD_DEFS;
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

	// IsAddOn /////

	public function setIsAddOn($isAddOn) {
		$this->isAddOn = $isAddOn;
		return $this;
	}

	public function getIsAddOn() {
		return $this->isAddOn;
	}

	// HostSoftware /////

	public function setHostSoftwareName($hostSoftwareName) {
		$this->hostSoftwareName = $hostSoftwareName;
		return $this;
	}

	public function getHostSoftwareName() {
		return $this->hostSoftwareName;
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

	public function addIdentityValue(\Ladb\CoreBundle\Entity\Knowledge\Value\SoftwareIdentity $identityValue) {
		if (!$this->identityValues->contains($identityValue)) {
			$this->identityValues[] = $identityValue;
		}
		return $this;
	}

	public function removeIdentityValue(\Ladb\CoreBundle\Entity\Knowledge\Value\SoftwareIdentity $identityValue) {
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

	// Icon /////

	public function setIcon($icon) {
		return $this->setMainPicture($icon);
	}

	public function getIcon() {
		return $this->getMainPicture();
	}

	// IconValues /////

	public function addIconValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Picture $iconValue) {
		if (!$this->iconValues->contains($iconValue)) {
			$this->iconValues[] = $iconValue;
		}
		return $this;
	}

	public function removeIconValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Picture $iconValue) {
		$this->iconValues->removeElement($iconValue);
	}

	public function setIconValues($iconValues) {
		$this->iconValues = $iconValues;
	}

	public function getIconValues() {
		return $this->iconValues;
	}

	// IconRejected /////

	public function setIconRejected($iconRejected) {
		$this->iconRejected = $iconRejected;
		return $this;
	}

	public function getIconRejected() {
		return $this->iconRejected;
	}

	// Screenshot /////

	public function setScreenshot($screenshot) {
		return $this->screenshot = $screenshot;
	}

	public function getScreenshot() {
		return $this->screenshot;
	}

	// ScreenshotValues /////

	public function addScreenshotValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Picture $screenshotValue) {
		if (!$this->screenshotValues->contains($screenshotValue)) {
			$this->screenshotValues[] = $screenshotValue;
		}
		return $this;
	}

	public function removeScreenshotValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Picture $screenshotValue) {
		$this->screenshotValues->removeElement($screenshotValue);
	}

	public function setScreenshotValues($screenshotValues) {
		$this->screenshotValues = $screenshotValues;
	}

	public function getScreenshotValues() {
		return $this->screenshotValues;
	}

	// Authors /////

	public function setAuthors($authors) {
		$this->authors = $authors;
		return $this;
	}

	public function getAuthors() {
		return $this->authors;
	}

	// AuthorsValues /////

	public function addAuthorsValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Text $authorValue) {
		if (!$this->authorsValues->contains($authorValue)) {
			$this->authorsValues[] = $authorValue;
		}
		return $this;
	}

	public function removeAuthorsValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Text $authorValue) {
		$this->authorsValues->removeElement($authorValue);
	}

	public function setAuthorsValues($authorsValues) {
		$this->authorsValues = $authorsValues;
	}

	public function getAuthorsValues() {
		return $this->authorsValues;
	}

	// Publisher /////

	public function setPublisher($publisher) {
		$this->publisher = $publisher;
		return $this;
	}

	public function getPublisher() {
		return $this->publisher;
	}

	// PublisherValues /////

	public function addPublisherValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Text $authorValue) {
		if (!$this->publisherValues->contains($authorValue)) {
			$this->publisherValues[] = $authorValue;
		}
		return $this;
	}

	public function removePublisherValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Text $authorValue) {
		$this->publisherValues->removeElement($authorValue);
	}

	public function setPublisherValues($publisherValues) {
		$this->publisherValues = $publisherValues;
	}

	public function getPublisherValues() {
		return $this->publisherValues;
	}

	// LastVersion /////

	public function setLastVersion($lastVersion) {
		$this->lastVersion = $lastVersion;
		return $this;
	}

	public function getLastVersion() {
		return $this->lastVersion;
	}

	// LastVersionValues /////

	public function addLastVersionValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Text $lastVersionValues) {
		if (!$this->lastVersionValues->contains($lastVersionValues)) {
			$this->lastVersionValues[] = $lastVersionValues;
		}
		return $this;
	}

	public function removeLastVersionValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Text $lastVersionValues) {
		$this->lastVersionValues->removeElement($lastVersionValues);
	}

	public function setLastVersionValues($lastVersionValues) {
		$this->lastVersionValues = $lastVersionValues;
	}

	public function getLastVersionValues() {
		return $this->lastVersionValues;
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

	// OpenSource /////

	public function setOpenSource($openSource) {
		$this->openSource = $openSource;
		return $this;
	}

	public function getOpenSource() {
		return $this->openSource;
	}

	// OpenSourceValues /////

	public function addOpenSourceValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Integer $openSourceValue) {
		if (!$this->openSourceValues->contains($openSourceValue)) {
			$this->openSourceValues[] = $openSourceValue;
		}
		return $this;
	}

	public function removeOpenSourceValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Integer $openSourceValue) {
		$this->openSourceValues->removeElement($openSourceValue);
	}

	public function setOpenSourceValues($openSourceValues) {
		$this->openSourceValues = $openSourceValues;
	}

	public function getOpenSourceValues() {
		return $this->openSourceValues;
	}

	// SourceCodeRepository /////

	public function setSourceCodeRepository($sourceCodeRepository) {
		$this->sourceCodeRepository = $sourceCodeRepository;
		return $this;
	}

	public function getSourceCodeRepository() {
		return $this->sourceCodeRepository;
	}

	// SourceCodeRepositoryValues /////

	public function addSourceCodeRepositoryValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Url $sourceCodeRepositoryValue) {
		if (!$this->sourceCodeRepositoryValues->contains($sourceCodeRepositoryValue)) {
			$this->sourceCodeRepositoryValues[] = $sourceCodeRepositoryValue;
		}
		return $this;
	}

	public function removeSourceCodeRepositoryValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Url $sourceCodeRepositoryValue) {
		$this->sourceCodeRepositoryValues->removeElement($sourceCodeRepositoryValue);
	}

	public function setSourceCodeRepositoryValues($sourceCodeRepositoryValues) {
		$this->sourceCodeRepositoryValues = $sourceCodeRepositoryValues;
	}

	public function getSourceCodeRepositoryValues() {
		return $this->sourceCodeRepositoryValues;
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

	// OperatingSystems /////

	public function setOperatingSystems($operatingSystems) {
		$this->operatingSystems = $operatingSystems;
		return $this;
	}

	public function getOperatingSystems() {
		return $this->operatingSystems;
	}

	// OperatingSystemsValues /////

	public function addOperatingSystemsValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Integer $operatingSystemsValue) {
		if (!$this->operatingSystemsValues->contains($operatingSystemsValue)) {
			$this->operatingSystemsValues[] = $operatingSystemsValue;
		}
		return $this;
	}

	public function removeOperatingSystemsValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Integer $operatingSystemsValue) {
		$this->operatingSystemsValues->removeElement($operatingSystemsValue);
	}

	public function setOperatingSystemsValues($operatingSystemsValues) {
		$this->operatingSystemsValues = $operatingSystemsValues;
	}

	public function getOperatingSystemsValues() {
		return $this->operatingSystemsValues;
	}

	// LicenseType /////

	public function setLicenseType($licenseType) {
		$this->licenseType = $licenseType;
		return $this;
	}

	public function getLicenseType() {
		return $this->licenseType;
	}

	// LicenseTypeValues /////

	public function addLicenseTypeValue(\Ladb\CoreBundle\Entity\Knowledge\Value\LinkableText $licenseTypeValue) {
		if (!$this->licenseTypeValues->contains($licenseTypeValue)) {
			$this->licenseTypeValues[] = $licenseTypeValue;
		}
		return $this;
	}

	public function removeLicenseTypeValue(\Ladb\CoreBundle\Entity\Knowledge\Value\LinkableText $licenseTypeValue) {
		$this->licenseTypeValues->removeElement($licenseTypeValue);
	}

	public function setLicenseTypeValues($licenseTypeValues) {
		$this->licenseTypeValues = $licenseTypeValues;
	}

	public function getLicenseTypeValues() {
		return $this->licenseTypeValues;
	}

	// Pricings /////

	public function setPricings($pricings) {
		$this->pricings = $pricings;
		return $this;
	}

	public function getPricings() {
		return $this->pricings;
	}

	// PricingsValues /////

	public function addPricingsValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Integer $pricingsValue) {
		if (!$this->pricingsValues->contains($pricingsValue)) {
			$this->pricingsValues[] = $pricingsValue;
		}
		return $this;
	}

	public function removePricingsValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Integer $pricingsValue) {
		$this->pricingsValues->removeElement($pricingsValue);
	}

	public function setPricingsValues($pricingsValues) {
		$this->pricingsValues = $pricingsValues;
	}

	public function getPricingsValues() {
		return $this->pricingsValues;
	}

	// Features /////

	public function setFeatures($features) {
		$this->features = $features;
		return $this;
	}

	public function getFeatures() {
		return $this->features;
	}

	// FeaturesValues /////

	public function addFeaturesValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Text $featuresValue) {
		if (!$this->featuresValues->contains($featuresValue)) {
			$this->featuresValues[] = $featuresValue;
		}
		return $this;
	}

	public function removeFeaturesValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Text $featuresValue) {
		$this->featuresValues->removeElement($featuresValue);
	}

	public function setFeaturesValues($featuresValues) {
		$this->featuresValues = $featuresValues;
	}

	public function getFeaturesValues() {
		return $this->featuresValues;
	}

	// Languages /////

	public function setLanguages($languages) {
		$this->languages = $languages;
		return $this;
	}

	public function getLanguages() {
		return $this->languages;
	}

	// LanguagesValues /////

	public function addLanguagesValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Language $languagesValue) {
		if (!$this->languagesValues->contains($languagesValue)) {
			$this->languagesValues[] = $languagesValue;
		}
		return $this;
	}

	public function removeLanguagesValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Language $languagesValue) {
		$this->languagesValues->removeElement($languagesValue);
	}

	public function setLanguagesValues($languagesValues) {
		$this->languagesValues = $languagesValues;
	}

	public function getLanguagesValues() {
		return $this->languagesValues;
	}

	// SupportedFiles /////

	public function setSupportedFiles($supportedFiles) {
		$this->supportedFiles = $supportedFiles;
		return $this;
	}

	public function getSupportedFiles() {
		return $this->supportedFiles;
	}

	// SupportedFilesValues /////

	public function addSupportedFilesValue(\Ladb\CoreBundle\Entity\Knowledge\Value\FileExtension $supportedFilesValue) {
		if (!$this->supportedFilesValues->contains($supportedFilesValue)) {
			$this->supportedFilesValues[] = $supportedFilesValue;
		}
		return $this;
	}

	public function removeSupportedFilesValue(\Ladb\CoreBundle\Entity\Knowledge\Value\FileExtension $supportedFilesValue) {
		$this->supportedFilesValues->removeElement($supportedFilesValue);
	}

	public function setSupportedFilesValues($supportedFilesValues) {
		$this->supportedFilesValues = $supportedFilesValues;
	}

	public function getSupportedFilesValues() {
		return $this->supportedFilesValues;
	}

}