<?php

namespace Ladb\CoreBundle\Entity\Wonder;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ladb\CoreBundle\Model\BlockBodiedTrait;
use Symfony\Component\Validator\Constraints as Assert;
use Ladb\CoreBundle\Validator\Constraints as LadbAssert;
use Ladb\CoreBundle\Model\BlockBodiedInterface;

/**
 * @ORM\Table("tbl_wonder_creation")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Wonder\CreationRepository")
 * @LadbAssert\BodyBlocks()
 */
class Creation extends AbstractWonder implements BlockBodiedInterface {

	use BlockBodiedTrait;

	const CLASS_NAME = 'LadbCoreBundle:Wonder\Creation';
	const TYPE = 100;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Picture", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_wonder_creation_picture")
	 * @ORM\OrderBy({"sortIndex" = "ASC"})
	 * @Assert\Count(min=1, max=5)
	 */
	protected $pictures;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Tag", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_wonder_creation_tag")
	 * @Assert\Count(min=2)
	 */
	protected $tags;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Referer\Referral", cascade={"persist", "remove"})
	 * @ORM\JoinTable(name="tbl_wonder_creation_referral", inverseJoinColumns={@ORM\JoinColumn(name="referral_id", referencedColumnName="id", unique=true)})
	 */
	protected $referrals;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Block\AbstractBlock", cascade={"persist", "remove"})
	 * @ORM\JoinTable(name="tbl_wonder_creation_body_block", inverseJoinColumns={@ORM\JoinColumn(name="block_id", referencedColumnName="id", unique=true)})
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
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Input\Wood", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_wonder_creation_wood")
	 */
	private $woods;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Input\Tool", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_wonder_creation_tool")
	 */
	private $tools;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Input\Finish", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_wonder_creation_finish")
	 */
	private $finishes;

	/**
	 * @ORM\Column(type="integer", name="plan_count")
	 */
	private $planCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Wonder\Plan", inversedBy="creations", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_wonder_creation_plan")
	 * @Assert\Count(min=0, max=4)
	 */
	private $plans;

	/**
	 * @ORM\Column(type="integer", name="howto_count")
	 */
	private $howtoCount = 0;

    /**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Howto\Howto", inversedBy="creations", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_wonder_creation_howto")
	 * @Assert\Count(min=0, max=4)
	 */
	private $howtos;

	/**
	 * @ORM\Column(type="integer", name="provider_count")
	 */
	private $providerCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Provider", inversedBy="creations", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_wonder_creation_provider")
	 * @Assert\Count(min=0, max=10)
	 */
	private $providers;

	/**
     * @ORM\Column(type="integer", name="rebound_count")
     */
    private $reboundCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Wonder\Creation", mappedBy="inspirations")
	 */
	private $rebounds;

	/**
	 * @ORM\Column(type="integer", name="inspiration_count")
	 */
	private $inspirationCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Wonder\Creation", inversedBy="rebounds", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_wonder_creation_inspiration",
	 *      	joinColumns={ @ORM\JoinColumn(name="creation_id", referencedColumnName="id") },
	 *      	inverseJoinColumns={ @ORM\JoinColumn(name="rebound_creation_id", referencedColumnName="id") }
	 *      )
	 * @Assert\Count(min=0, max=4)
	 */
	private $inspirations;

	/**
	 * @ORM\OneToOne(targetEntity="Ladb\CoreBundle\Entity\Spotlight", cascade={"remove"})
	 * @ORM\JoinColumn(name="spotlight_id", referencedColumnName="id")
	 */
	private $spotlight = null;

	/////

	public function __construct() {
		parent::__construct();
		$this->bodyBlocks = new \Doctrine\Common\Collections\ArrayCollection();
		$this->woods = new \Doctrine\Common\Collections\ArrayCollection();
		$this->finishes = new \Doctrine\Common\Collections\ArrayCollection();
		$this->tools = new \Doctrine\Common\Collections\ArrayCollection();
		$this->plans = new \Doctrine\Common\Collections\ArrayCollection();
		$this->howtos = new \Doctrine\Common\Collections\ArrayCollection();
		$this->providers = new \Doctrine\Common\Collections\ArrayCollection();
		$this->inspirations = new \Doctrine\Common\Collections\ArrayCollection();
	}

	// Type /////

	public function getType() {
		return Creation::TYPE;
	}

	// Woods /////

	public function addWood(\Ladb\CoreBundle\Entity\Input\Wood $wood) {
        if (!$this->woods->contains($wood)) {
		    $this->woods[] = $wood;
        }
		return $this;
	}

	public function removeWood(\Ladb\CoreBundle\Entity\Input\Wood $wood) {
		$this->woods->removeElement($wood);
	}

	public function getWoods() {
		return $this->woods;
	}

	// Tools /////

	public function addTool(\Ladb\CoreBundle\Entity\Input\Tool $tool) {
		if (!$this->tools->contains($tool)) {
			$this->tools[] = $tool;
		}
		return $this;
	}

	public function removeTool(\Ladb\CoreBundle\Entity\Input\Tool $tool) {
		$this->tools->removeElement($tool);
	}

	public function getTools() {
		return $this->tools;
	}

	// Finishes /////

	public function addFinish(\Ladb\CoreBundle\Entity\Input\Finish $finish) {
		if (!$this->finishes->contains($finish)) {
			$this->finishes[] = $finish;
		}
		return $this;
	}

	public function removeFinish(\Ladb\CoreBundle\Entity\Input\Finish $finish) {
		$this->finishes->removeElement($finish);
	}

	public function getFinishes() {
		return $this->finishes;
	}

	// PlanCount /////

	public function incrementPlanCount($by = 1) {
		return $this->planCount += intval($by);
	}

	public function getPlanCount() {
		return $this->planCount;
	}

	// Plans /////

	public function addPlan(\Ladb\CoreBundle\Entity\Wonder\Plan $plan) {
		if (!$this->plans->contains($plan)) {
			$this->plans[] = $plan;
			$this->planCount = count($this->plans);
			if (!$this->getIsDraft()) {
				$plan->incrementCreationCount();
			}
		}
		return $this;
	}

	public function removePlan(\Ladb\CoreBundle\Entity\Wonder\Plan $plan) {
		if ($this->plans->removeElement($plan)) {
			$this->planCount = count($this->plans);
			if (!$this->getIsDraft()) {
				$plan->incrementCreationCount(-1);
			}
		}
	}

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

	// Howtos /////

	public function addHowto(\Ladb\CoreBundle\Entity\Howto\Howto $howto) {
		if (!$this->howtos->contains($howto)) {
			$this->howtos[] = $howto;
			$this->howtoCount = count($this->howtos);
			if (!$this->getIsDraft()) {
				$howto->incrementCreationCount();
			}
		}
		return $this;
	}

	public function removeHowto(\Ladb\CoreBundle\Entity\Howto\Howto $howto) {
		if ($this->howtos->removeElement($howto)) {
			$this->howtoCount = count($this->howtos);
			if (!$this->getIsDraft()) {
				$howto->incrementCreationCount(-1);
			}
		}
	}

	public function getHowtos() {
		return $this->howtos;
	}

	// ProviderCount /////

	public function incrementProviderCount($by = 1) {
		return $this->providerCount += intval($by);
	}

	public function getProviderCount() {
		return $this->providerCount;
	}

	// Providers /////

	public function addProvider(\Ladb\CoreBundle\Entity\Knowledge\Provider $provider) {
		if (!$this->providers->contains($provider)) {
			$this->providers[] = $provider;
			$this->providerCount = count($this->providers);
			if (!$this->getIsDraft()) {
				$provider->incrementCreationCount();
			}
		}
		return $this;
	}

	public function removeProvider(\Ladb\CoreBundle\Entity\Knowledge\Provider $provider) {
		if ($this->providers->removeElement($provider)) {
			$this->providerCount = count($this->providers);
			if (!$this->getIsDraft()) {
				$provider->incrementCreationCount(-1);
			}
		}
	}

	public function getProviders() {
		return $this->providers;
	}

	// ReboundCount /////

	public function getReboundCount() {
		return $this->reboundCount;
	}

	public function getRebounds() {
		return $this->rebounds;
	}

	// Rebounds /////

	public function getInspirationCount() {
		return $this->inspirationCount;
	}

	// InspirationCount /////

	public function addInspiration(\Ladb\CoreBundle\Entity\Wonder\Creation $inspiration) {
		if (!$this->inspirations->contains($inspiration)) {
			$this->inspirations[] = $inspiration;
			$this->inspirationCount = count($this->inspirations);
			if (!$this->getIsDraft()) {
				$inspiration->incrementReboundCount();
			}
		}
		return $this;
	}

	// Inspirations /////

	public function incrementReboundCount($by = 1) {
		return $this->reboundCount += intval($by);
	}

	public function removeInspiration(\Ladb\CoreBundle\Entity\Wonder\Creation $inspiration) {
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

	// Spotlight /////

	public function getSpotlight() {
		return $this->spotlight;
	}

	public function setSpotlight(\Ladb\CoreBundle\Entity\Spotlight $spotlight = null) {
		$this->spotlight = $spotlight;
		return $this;
	}

}