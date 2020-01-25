<?php

namespace Ladb\CoreBundle\Entity\Wonder;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Ladb\CoreBundle\Validator\Constraints as LadbAssert;
use Ladb\CoreBundle\Entity\Core\Resource;
use Ladb\CoreBundle\Model\HtmlBodiedInterface;
use Ladb\CoreBundle\Model\HtmlBodiedTrait;
use Ladb\CoreBundle\Model\InspirableInterface;
use Ladb\CoreBundle\Model\InspirableTrait;

/**
 * @ORM\Table("tbl_wonder_plan")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Wonder\PlanRepository")
 * @LadbAssert\PlanResourcesMaxSize()
 */
class Plan extends AbstractWonder implements HtmlBodiedInterface, InspirableInterface {

	use HtmlBodiedTrait;
	use InspirableTrait;

	const CLASS_NAME = 'LadbCoreBundle:Wonder\Plan';
	const STRIPPED_NAME = 'plan';
	const TYPE = 105;

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
	private $kinds = array( Resource::KIND_UNKNOW );

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Core\Picture", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_wonder_plan_picture")
	 * @ORM\OrderBy({"sortIndex" = "ASC"})
	 * @Assert\Count(min=1, max=5)
	 */
	protected $pictures;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Core\Resource", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_wonder_plan_resource")
	 * @Assert\Count(min=1, max=10)
	 */
	private $resources;

	/**
	 * @ORM\Column(type="string", length=255, nullable=true, name="sketchup_3d_warehouse_url")
	 * @Assert\Url()
	 */
	private $sketchup3DWarehouseUrl = null;

	/**
	 * @ORM\Column(type="string", length=255, nullable=true, name="sketchup_3d_warehouse_embed_identifier")
	 */
	private $sketchup3DWarehouseEmbedIdentifier = null;

	/**
	 * @ORM\Column(type="string", length=255, nullable=true, name="a360_url")
	 * @Assert\Regex("/^https:\/\/a360\.co\/[a-zA-Z0-9]+$/", message="Lien public non conforme.")
	 * @Assert\Url()
	 */
	private $a360Url = null;

	/**
	 * @ORM\Column(type="string", length=255, nullable=true, name="a360_embed_identifier")
	 */
	private $a360EmbedIdentifier = null;

	/**
	 * @ORM\Column(type="integer", name="zip_archive_size")
	 */
	private $zipArchiveSize = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Core\Tag", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_wonder_plan_tag")
	 * @Assert\Count(min=2)
	 */
	protected $tags;

	/**
	 * @ORM\Column(type="integer", name="download_count")
	 */
	private $downloadCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Core\Referer\Referral", cascade={"persist", "remove"})
	 * @ORM\JoinTable(name="tbl_wonder_plan_referral", inverseJoinColumns={@ORM\JoinColumn(name="referral_id", referencedColumnName="id", unique=true)})
	 * @ORM\OrderBy({"accessCount" = "DESC"})
	 */
	protected $referrals;

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
	 * @ORM\Column(type="integer", name="question_count")
	 */
	private $questionCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Qa\Question", inversedBy="plans", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_wonder_plan_question")
	 * @Assert\Count(min=0, max=4)
	 */
	private $questions;

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
	 * @ORM\Column(type="integer", name="workflow_count")
	 */
	private $workflowCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Workflow\Workflow", mappedBy="plans")
	 */
	private $workflows;

	/**
	 * @ORM\Column(type="integer", name="school_count")
	 */
	private $schoolCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\School", inversedBy="plans", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_wonder_plan_school")
	 * @Assert\Count(min=0, max=10)
	 */
	private $schools;

	/////

	public function __construct() {
		parent::__construct();
		$this->resources = new \Doctrine\Common\Collections\ArrayCollection();
		$this->inspirations = new \Doctrine\Common\Collections\ArrayCollection();
		$this->questions = new \Doctrine\Common\Collections\ArrayCollection();
		$this->creations = new \Doctrine\Common\Collections\ArrayCollection();
		$this->workshops = new \Doctrine\Common\Collections\ArrayCollection();
		$this->howtos = new \Doctrine\Common\Collections\ArrayCollection();
		$this->workflows = new \Doctrine\Common\Collections\ArrayCollection();
		$this->schools = new \Doctrine\Common\Collections\ArrayCollection();
	}

	/////

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

	// Resources /////

	public function addResource(\Ladb\CoreBundle\Entity\Core\Resource $resource) {
		if (!$this->resources->contains($resource)) {
			$this->resources[] = $resource;
		}
		return $this;
	}

	public function removeResource(\Ladb\CoreBundle\Entity\Core\Resource $resource) {
		$this->resources->removeElement($resource);
	}

	public function getResources() {
		return $this->resources;
	}

	public function getMaxResourceCount() {
		return 10;
	}

	// ResouceFileExtensions /////

	public function getResourceFileExtensions() {
		$fileExtensions = array();
		foreach ($this->getResources() as $resource) {
			if (!in_array($resource->getFileExtension(), $fileExtensions)) {
				$fileExtensions[] = $resource->getFileExtension();
			}
		}
		return $fileExtensions;
	}

	// Sketchup3DWarehouseUrl /////

	public function setSketchup3DWarehouseUrl($sketchup3DWarehouseUrl) {
		return $this->sketchup3DWarehouseUrl = $sketchup3DWarehouseUrl;
	}

	public function getSketchup3DWarehouseUrl() {
		return $this->sketchup3DWarehouseUrl;
	}

	// Sketchup3DWarehouseIdentifier /////

	public function setSketchup3DWarehouseEmbedIdentifier($sketchup3DWarehouseEmbedIdentifier) {
		return $this->sketchup3DWarehouseEmbedIdentifier = $sketchup3DWarehouseEmbedIdentifier;
	}

	public function getSketchup3DWarehouseEmbedIdentifier() {
		return $this->sketchup3DWarehouseEmbedIdentifier;
	}

	// A360Url /////

	public function setA360Url($a360Url) {
		return $this->a360Url = $a360Url;
	}

	public function getA360Url() {
		return $this->a360Url;
	}

	// A360Identifier /////

	public function setA360EmbedIdentifier($a360EmbedIdentifier) {
		return $this->a360EmbedIdentifier = $a360EmbedIdentifier;
	}

	public function getA360EmbedIdentifier() {
		return $this->a360EmbedIdentifier;
	}

	// ZipArchiveSize /////

	public function setZipArchiveSize($zipArchiveSize) {
		return $this->zipArchiveSize = $zipArchiveSize;
	}

	public function getZipArchiveSize() {
		return $this->zipArchiveSize;
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

	// LinkedEntities /////

	public function getLinkedEntities() {
		return array_merge(
			$this->inspirations->getValues(),
			$this->questions->getValues(),
			$this->schools->getValues()
		);
	}

	// QuestionCount /////

	public function incrementQuestionCount($by = 1) {
		return $this->questionCount += intval($by);
	}

	public function getQuestionCount() {
		return $this->questionCount;
	}

	// Questions /////

	public function addQuestion(\Ladb\CoreBundle\Entity\Qa\Question $question) {
		if (!$this->questions->contains($question)) {
			$this->questions[] = $question;
			$this->questionCount = count($this->questions);
			if (!$this->getIsDraft()) {
				$question->incrementPlanCount();
			}
		}
		return $this;
	}

	public function removeQuestion(\Ladb\CoreBundle\Entity\Qa\Question $question) {
		if ($this->questions->removeElement($question)) {
			$this->questionCount = count($this->questions);
			if (!$this->getIsDraft()) {
				$question->incrementPlanCount(-1);
			}
		}
	}

	public function getQuestions() {
		return $this->questions;
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

	// WorkflowCount /////

	public function incrementWorkflowCount($by = 1) {
		return $this->workflowCount += intval($by);
	}

	public function getWorkflowCount() {
		return $this->workflowCount;
	}

	// Workflows /////

	public function getWorkflows() {
		return $this->workflows;
	}

	// SchoolCount /////

	public function incrementSchoolCount($by = 1) {
		return $this->schoolCount += intval($by);
	}

	public function getSchoolCount() {
		return $this->schoolCount;
	}

	// Schools /////

	public function addSchool(\Ladb\CoreBundle\Entity\Knowledge\School $school) {
		if (!$this->schools->contains($school)) {
			$this->schools[] = $school;
			$this->schoolCount = count($this->schools);
			if (!$this->getIsDraft()) {
				$school->incrementPlanCount();
			}
		}
		return $this;
	}

	public function removeSchool(\Ladb\CoreBundle\Entity\Knowledge\School $school) {
		if ($this->schools->removeElement($school)) {
			$this->schoolCount = count($this->schools);
			if (!$this->getIsDraft()) {
				$school->incrementPlanCount(-1);
			}
		}
	}

	public function getSchools() {
		return $this->schools;
	}

}
