<?php

namespace Ladb\CoreBundle\Entity\Wonder;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Ladb\CoreBundle\Validator\Constraints as LadbAssert;
use Ladb\CoreBundle\Model\EmbeddableInterface;
use Ladb\CoreBundle\Model\BodiedInterface;

/**
 * @ORM\Table("tbl_wonder_plan")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Wonder\PlanRepository")
 * @LadbAssert\PlanResourcesMaxSize()
 */
class Plan extends AbstractWonder implements BodiedInterface {

	const CLASS_NAME = 'LadbCoreBundle:Wonder\Plan';
	const STRIPPED_NAME = 'plan';
	const TYPE = 105;

	const KIND_UNKNOW = 0;
	const KIND_AUTOCAD = 1;
	const KIND_SKETCHUP = 2;
	const KIND_PDF = 3;
	const KIND_GEOGEBRA = 4;
	const KIND_SVG = 5;
	const KIND_FREECAD = 6;
	const KIND_STL = 7;
	const KIND_123DESIGN = 8;
	const KIND_LIBREOFFICE = 9;

	/**
	 * @ORM\Column(type="text", nullable=false)
	 * @Assert\NotBlank()
	 * @Assert\Length(min=5, max=4000)
	 */
	protected $body;

	/**
	 * @ORM\Column(type="text", nullable=false)
	 */
	private $htmlBody;

	/**
	 * @ORM\Column(type="simple_array")
	 */
	private $kinds = array( Plan::KIND_UNKNOW );

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Picture", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_wonder_plan_picture")
	 * @ORM\OrderBy({"sortIndex" = "ASC"})
	 * @Assert\Count(min=1, max=5)
	 */
	protected $pictures;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Resource", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_wonder_plan_resource")
	 * @Assert\Count(min=1, max=10)
	 */
	private $resources;

	/**
	 * @ORM\Column(type="integer", name="zip_archive_size")
	 */
	private $zipArchiveSize = 0;

	/**
	 * @ORM\Column(type="integer", name="creation_count")
	 */
	private $creationCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Wonder\Creation", mappedBy="plans")
	 */
	private $creations;

	/**
	 * @ORM\Column(type="integer", name="workshop_count")
	 */
	private $workshopCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Wonder\Workshop", mappedBy="plans")
	 */
	private $workshops;

	/**
	 * @ORM\Column(type="integer", name="howto_count")
	 */
	private $howtoCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Howto\Howto", mappedBy="plans")
	 */
	private $howtos;

	/**
	 * @ORM\Column(type="integer", name="rebound_count")
	 */
	private $reboundCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Wonder\Plan", mappedBy="inspirations")
	 */
	private $rebounds;

	/**
	 * @ORM\Column(type="integer", name="inspiration_count")
	 */
	private $inspirationCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Wonder\Plan", inversedBy="rebounds", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_wonder_plan_inspiration",
	 *      	joinColumns={ @ORM\JoinColumn(name="plan_id", referencedColumnName="id") },
	 *      	inverseJoinColumns={ @ORM\JoinColumn(name="rebound_plan_id", referencedColumnName="id") }
	 *      )
	 * @Assert\Count(min=0, max=4)
	 */
	private $inspirations;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Tag", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_wonder_plan_tag")
	 * @Assert\Count(min=2)
	 */
	protected $tags;

	/**
	 * @ORM\Column(type="integer", name="download_count")
	 */
	private $downloadCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Referer\Referral", cascade={"persist", "remove"})
	 * @ORM\JoinTable(name="tbl_wonder_plan_referral", inverseJoinColumns={@ORM\JoinColumn(name="referral_id", referencedColumnName="id", unique=true)})
	 */
	protected $referrals;

	/////

	public function __construct() {
		parent::__construct();
		$this->resources = new \Doctrine\Common\Collections\ArrayCollection();
		$this->creations = new \Doctrine\Common\Collections\ArrayCollection();
		$this->workshops = new \Doctrine\Common\Collections\ArrayCollection();
		$this->howtos = new \Doctrine\Common\Collections\ArrayCollection();
		$this->inspirations = new \Doctrine\Common\Collections\ArrayCollection();
	}

	/////

	// HtmlBody /////

	public function setHtmlBody($htmlBody) {
		$this->htmlBody = $htmlBody;
		return $this;
	}

	public function getHtmlBody() {
		return $this->htmlBody;
	}

	// Type /////

	public function getType() {
		return Plan::TYPE;
	}

	// Kind /////

	public function setKinds($kind) {
		$this->kinds = $kind;
		return $this;
	}

	public function getKinds() {
		return $this->kinds;
	}

	public function getKindStrippedNames() {
		$kindStrippedNames = array();
		foreach ($this->kinds as $kind) {
			switch ($kind) {
				case Plan::KIND_AUTOCAD:
					$kindStrippedNames[] = 'autocad';
					break;
				case Plan::KIND_SKETCHUP:
					$kindStrippedNames[] = 'sketchup';
					break;
				case Plan::KIND_PDF:
					$kindStrippedNames[] = 'pdf';
					break;
				case Plan::KIND_GEOGEBRA:
					$kindStrippedNames[] = 'geogebra';
					break;
				case Plan::KIND_SVG:
					$kindStrippedNames[] = 'svg';
					break;
				case Plan::KIND_FREECAD:
					$kindStrippedNames[] = 'freecad';
					break;
				case Plan::KIND_STL:
					$kindStrippedNames[] = 'stl';
					break;
				case Plan::KIND_123DESIGN:
					$kindStrippedNames[] = '123ddesign';
					break;
				case Plan::KIND_LIBREOFFICE:
					$kindStrippedNames[] = 'libreoffice';
					break;
				default:
					$kindStrippedNames[] = '';
			}
		}
		return $kindStrippedNames;
	}

	public function getKindExternUrls() {
		$kindExternUrls = array();
		foreach ($this->kinds as $kind) {
			switch ($kind) {
				case Plan::KIND_AUTOCAD:
					$kindExternUrls[] = 'www.freecadweb.org';
					break;
				case Plan::KIND_SKETCHUP:
					$kindExternUrls[] = 'www.sketchup.com';
					break;
				case Plan::KIND_PDF:
					$kindExternUrls[] = 'get.adobe.com/fr/reader/';
					break;
				case Plan::KIND_GEOGEBRA:
					$kindExternUrls[] = 'www.geogebra.org';
					break;
				case Plan::KIND_SVG:
					$kindExternUrls[] = 'fr.wikipedia.org/wiki/Scalable_Vector_Graphics';
					break;
				case Plan::KIND_FREECAD:
					$kindExternUrls[] = 'www.freecadweb.org';
					break;
				case Plan::KIND_STL:
				case Plan::KIND_123DESIGN:
					$kindExternUrls[] = 'www.123dapp.com/design';
					break;
				case Plan::KIND_LIBREOFFICE:
					$kindExternUrls[] = 'fr.libreoffice.org';
					break;
				default:
					$kindExternUrls[] = '';
			}
		}
		return $kindExternUrls;
	}

	// Resource /////

	public function setResource(\Ladb\CoreBundle\Entity\Resource $resource = null) {
		$this->resource = $resource;
		return $this;
	}

	public function getResource() {
		return $this->resource;
	}

	// Resources /////

	public function addResource(\Ladb\CoreBundle\Entity\Resource $resource) {
		if (!$this->resources->contains($resource)) {
			$this->resources[] = $resource;
		}
		return $this;
	}

	public function removeResource(\Ladb\CoreBundle\Entity\Resource $resource) {
		$this->resources->removeElement($resource);
	}

	public function getResources() {
		return $this->resources;
	}

	public function getMaxResourceCount() {
		return 10;
	}

	// ResourceSizeSum /////

	public function setZipArchiveSize($zipArchiveSize) {
		return $this->zipArchiveSize = $zipArchiveSize;
	}

	public function getZipArchiveSize() {
		return $this->zipArchiveSize;
	}

	// CreationCount /////

	public function incrementCreationCount($by = 1) {
		return $this->creationCount += intval($by);
	}

	public function getCreationCount() {
		return $this->creationCount;
	}

	// Creations /////

	public function getCreations() {
		return $this->creations;
	}

	// WorkshopCount /////

	public function incrementWorkshopCount($by = 1) {
		return $this->workshopCount += intval($by);
	}

	public function getWorkshopCount() {
		return $this->workshopCount;
	}

	// Workshops /////

	public function getWorkshops() {
		return $this->workshops;
	}

	// HowtoCount /////

	public function incrementHowtoCount($by = 1) {
		return $this->howtoCount += intval($by);
	}

	public function getHowtoCount() {
		return $this->howtoCount;
	}

	// Howtos /////

	public function getHowtos() {
		return $this->howtos;
	}

	// ReboundCount /////

	public function incrementReboundCount($by = 1) {
		return $this->reboundCount += intval($by);
	}

	public function getReboundCount() {
		return $this->reboundCount;
	}

	// Rebounds /////

	public function getRebounds() {
		return $this->rebounds;
	}

	// InspirationCount /////

	public function getInspirationCount() {
		return $this->inspirationCount;
	}

	// Inspirations /////

	public function addInspiration(\Ladb\CoreBundle\Entity\Wonder\Plan $inspiration) {
		if (!$this->inspirations->contains($inspiration)) {
			$this->inspirations[] = $inspiration;
			$this->inspirationCount = count($this->inspirations);
			if (!$this->getIsDraft()) {
				$inspiration->incrementReboundCount();
			}
		}
		return $this;
	}

	public function removeInspiration(\Ladb\CoreBundle\Entity\Wonder\Plan $inspiration) {
		if ($this->inspirations->removeElement($inspiration)) {
			$this->inspirationCount = count($this->inspirations);
			if (!$this->getIsDraft()) {
				$inspiration->incrementReboundCount(-1);
			}
		}
	}

	public function getInspirations() {
		return $this->inspirations;
	}

	// DownloadCount /////

	public function incrementDownloadCount($by = 1) {
		return $this->downloadCount += intval($by);
	}

	public function setDownloadCount($downloadCount) {
		$this->downloadCount = $downloadCount;
		return $this;
	}

	public function getDownloadCount() {
		return $this->downloadCount;
	}

}
