<?php

namespace Ladb\CoreBundle\Entity\Core;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ladb\CoreBundle\Entity\AbstractPublication;
use Symfony\Component\Validator\Constraints as Assert;
use Ladb\CoreBundle\Validator\Constraints as LadbAssert;
use Ladb\CoreBundle\Entity\AbstractDraftableAuthoredPublication;
use Ladb\CoreBundle\Model\CollectionnableInterface;
use Ladb\CoreBundle\Model\CollectionnableTrait;
use Ladb\CoreBundle\Model\SluggedInterface;
use Ladb\CoreBundle\Model\SluggedTrait;
use Ladb\CoreBundle\Model\LicensedTrait;
use Ladb\CoreBundle\Model\BodiedInterface;
use Ladb\CoreBundle\Model\BodiedTrait;
use Ladb\CoreBundle\Model\PicturedInterface;
use Ladb\CoreBundle\Model\PicturedTrait;
use Ladb\CoreBundle\Model\CommentableTrait;
use Ladb\CoreBundle\Model\IndexableTrait;
use Ladb\CoreBundle\Model\LikableTrait;
use Ladb\CoreBundle\Model\ScrapableTrait;
use Ladb\CoreBundle\Model\SitemapableInterface;
use Ladb\CoreBundle\Model\SitemapableTrait;
use Ladb\CoreBundle\Model\TaggableTrait;
use Ladb\CoreBundle\Model\TitledTrait;
use Ladb\CoreBundle\Model\ViewableTrait;
use Ladb\CoreBundle\Model\WatchableTrait;
use Ladb\CoreBundle\Model\IndexableInterface;
use Ladb\CoreBundle\Model\TitledInterface;
use Ladb\CoreBundle\Model\ViewableInterface;
use Ladb\CoreBundle\Model\LikableInterface;
use Ladb\CoreBundle\Model\WatchableInterface;
use Ladb\CoreBundle\Model\CommentableInterface;
use Ladb\CoreBundle\Model\ReportableInterface;
use Ladb\CoreBundle\Model\TaggableInterface;
use Ladb\CoreBundle\Model\ExplorableInterface;
use Ladb\CoreBundle\Model\ScrapableInterface;

/**
 * @ORM\Table("tbl_core_tip")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Core\TipRepository")
 * @LadbAssert\BodyBlocks()
 */
class Tip extends AbstractPublication implements TitledInterface, BodiedInterface {

	use BodiedTrait;

	const CLASS_NAME = 'LadbCoreBundle:Promotion\Tip';
	const TYPE = 5;

	/**
	 * @ORM\Column(type="text", nullable=false)
	 */
	private $body;

	/**
	 * @ORM\Column(type="text", nullable=false)
	 */
	private $htmlBody;

	/**
	 * @ORM\Column(type="string", length=255, nullable=false)
	 * @Assert\NotBlank()
	 * @Assert\Url()
	 */
	private $url;

	/////

	// Type /////

	public function getType() {
		return Tip::TYPE;
	}

	// Title /////

	public function setTitle($title) {
	}

	public function getTitle() {
		return 'Le saviez-vous n°'.$this->getId();
	}

	// Url /////

	public function getUrl() {
		return $this->url;
	}

	public function setUrl($url) {
		$this->url = $url;
		return $this;
	}

}