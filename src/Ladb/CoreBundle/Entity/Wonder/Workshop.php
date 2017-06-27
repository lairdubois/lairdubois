<?php

namespace Ladb\CoreBundle\Entity\Wonder;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ladb\CoreBundle\Model\BlockBodiedTrait;
use Ladb\CoreBundle\Model\LocalisableTrait;
use Symfony\Component\Validator\Constraints as Assert;
use Ladb\CoreBundle\Validator\Constraints as LadbAssert;
use Ladb\CoreBundle\Model\BlockBodiedInterface;
use Ladb\CoreBundle\Model\LocalisableInterface;

/**
 * @ORM\Table("tbl_wonder_workshop")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Wonder\WorkshopRepository")
 * @LadbAssert\BodyBlocks()
 */
class Workshop extends AbstractWonder implements BlockBodiedInterface, LocalisableInterface {

	use BlockBodiedTrait, LocalisableTrait;

	const CLASS_NAME = 'LadbCoreBundle:Wonder\Workshop';
	const TYPE = 101;
	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Core\Picture", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_wonder_workshop_picture")
	 * @ORM\OrderBy({"sortIndex" = "ASC"})
	 * @Assert\Count(min=1, max=5)
	 */
	protected $pictures;
	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Core\Tag", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_wonder_workshop_tag")
	 * @Assert\Count(min=2)
	 */
	protected $tags;
	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Referer\Referral", cascade={"persist", "remove"})
	 * @ORM\JoinTable(name="tbl_wonder_workshop_referral", inverseJoinColumns={@ORM\JoinColumn(name="referral_id", referencedColumnName="id", unique=true)})
	 */
	protected $referrals;
	/**
	 * @ORM\Column(type="string", length=100, nullable=true)
	 */
	private $location;
	/**
	 * @ORM\Column(type="float", nullable=true)
	 */
	private $latitude;
	/**
	 * @ORM\Column(type="float", nullable=true)
	 */
	private $longitude;
	/**
	 * @ORM\Column(type="smallint", nullable=true)
	 */
	private $area;
	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Block\AbstractBlock", cascade={"persist", "remove"})
	 * @ORM\JoinTable(name="tbl_wonder_workshop_body_block", inverseJoinColumns={@ORM\JoinColumn(name="block_id", referencedColumnName="id", unique=true)})
	 * @ORM\OrderBy({"sortIndex" = "ASC"})
	 * @Assert\Count(min=1)
	 */
	private $bodyBlocks;
	/**
	 * @ORM\Column(type="integer", name="body_block_picture_count")
	 */
	private $bodyBlockPictureCount = 0;
	/**
	 * @ORM\Column(type="integer", name="body_block_video_count")
	 */
	private $bodyBlockVideoCount = 0;
	/**
	 * @ORM\Column(type="integer", name="plan_count")
	 */
	private $planCount = 0;
	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Wonder\Plan", inversedBy="workshops", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_wonder_workshop_plan")
	 * @Assert\Count(min=0, max=4)
	 */
	private $plans;
	/**
	 * @ORM\Column(type="integer", name="howto_count")
	 */
	private $howtoCount = 0;
	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Howto\Howto", inversedBy="workshops", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_wonder_workshop_howto")
	 * @Assert\Count(min=0, max=4)
	 */
	private $howtos;

	/////

	public function __construct() {
		parent::__construct();
		$this->bodyBlocks = new \Doctrine\Common\Collections\ArrayCollection();
		$this->plans = new \Doctrine\Common\Collections\ArrayCollection();
		$this->howtos = new \Doctrine\Common\Collections\ArrayCollection();
	}

	// Type /////

	public function getType() {
		return Workshop::TYPE;
	}

	// Area /////

	public function getArea() {
		return $this->area;
	}

	public function setArea($area) {
		$this->area = $area;
		return $this;
	}

	// PlanCount /////

	public function getPlanCount() {
		return $this->planCount;
	}

	// Plans /////

	public function addPlan(\Ladb\CoreBundle\Entity\Wonder\Plan $plan) {
		if (!$this->plans->contains($plan)) {
			$this->plans[] = $plan;
			$this->planCount = count($this->plans);
			if (!$this->getIsDraft()) {
				$plan->incrementWorkshopCount();
			}
		}
		return $this;
	}

	public function removePlan(\Ladb\CoreBundle\Entity\Wonder\Plan $plan) {
		if ($this->plans->removeElement($plan)) {
			$this->planCount = count($this->plans);
			if (!$this->getIsDraft()) {
				$plan->incrementWorkshopCount(-1);
			}
		}
	}

	public function getPlans() {
		return $this->plans;
	}

	// HowtoCount /////

	public function getHowtoCount() {
		return $this->howtoCount;
	}

	// Howtos /////

	public function addHowto(\Ladb\CoreBundle\Entity\Howto\Howto $howto) {
		if (!$this->howtos->contains($howto)) {
			$this->howtos[] = $howto;
			$this->howtoCount = count($this->howtos);
			if (!$this->getIsDraft()) {
				$howto->incrementWorkshopCount();
			}
		}
		return $this;
	}

	public function removeHowto(\Ladb\CoreBundle\Entity\Howto\Howto $howto) {
		if ($this->howtos->removeElement($howto)) {
			$this->howtoCount = count($this->howtos);
			if (!$this->getIsDraft()) {
				$howto->incrementWorkshopCount(-1);
			}
		}
	}

	public function getHowtos() {
		return $this->howtos;
	}

}