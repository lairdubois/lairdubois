<?php

namespace App\Entity\Core;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints as LadbAssert;
use App\Entity\AbstractPublication;
use App\Model\HtmlBodiedInterface;
use App\Model\HtmlBodiedTrait;
use App\Model\IndexableTrait;
use App\Model\ViewableTrait;
use App\Model\IndexableInterface;
use App\Model\TitledInterface;
use App\Model\ViewableInterface;

/**
 * @ORM\Table("tbl_core_tip")
 * @ORM\Entity(repositoryClass="App\Repository\Core\TipRepository")
 * @LadbAssert\BodyBlocks()
 */
class Tip extends AbstractPublication implements TitledInterface, HtmlBodiedInterface, IndexableInterface, ViewableInterface {

	use HtmlBodiedTrait;
	use IndexableTrait, ViewableTrait;

	const CLASS_NAME = 'App\Entity\Core\Tip';
	const TYPE = 5;

	/**
	 * @ORM\Column(type="text", nullable=false)
	 */
	private $body;

	/**
	 * @ORM\Column(type="text", nullable=false, name="htmlBody")
	 */
	private $htmlBody;

	/**
	 * @ORM\Column(type="string", length=255, nullable=false)
	 * @Assert\NotBlank()
	 * @Assert\Url()
	 */
	private $url;

	/**
	 * @ORM\Column(type="integer", name="view_count")
	 */
	private $viewCount = 0;

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