<?php

namespace Ladb\CoreBundle\Entity\Input;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\MappedSuperclass
 */
abstract class AbstractInput {

	/**
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @ORM\Column(type="string", length=50)
	 */
	private $label;

	/**
	 * @Gedmo\Slug(fields={"label"}, updatable=false, separator="_")
	 * @ORM\Column(type="string", length=50, unique=true)
	 */
	private $slug;

	/////

	// Id /////

	public function getId() {
		return $this->id;
	}

	// Name /////

	public function setLabel($name) {
		$this->label = ucfirst($name);
		return $this;
	}

	public function getLabel() {
		return $this->label;
	}

	// Slug /////

	public function setSlug($slug) {
		$this->slug = $slug;
		return $this;
	}

	public function getSlug() {
		return $this->slug;
	}

}