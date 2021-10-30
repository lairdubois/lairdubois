<?php

namespace App\Entity\Core;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use App\Model\BodiedInterface;
use App\Model\IdentifiableInterface;
use Symfony\Component\Validator\Constraints as Assert;
use App\Model\HtmlBodiedTrait;
use App\Model\HtmlBodiedInterface;

/**
 * @ORM\Table("tbl_core_biography")
 * @ORM\Entity
 */
class Biography implements IdentifiableInterface, HtmlBodiedInterface {

	use HtmlBodiedTrait;

	/**
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @ORM\Column(type="text", nullable=true)
	 * @Assert\Length(min=2, max=10000)
	 */
	private $body;

	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	private $htmlBody;

	/////

	// Id /////

	public function getId() {
		return $this->id;
	}

}