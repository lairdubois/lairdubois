<?php

namespace App\Entity\Core;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table("tbl_core_tag")
 * @ORM\Entity(repositoryClass="App\Repository\Core\TagRepository")
 */
class Tag {

	const CLASS_NAME = 'App\Entity\Core\Tag';

	/**
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @ORM\Column(type="string", length=40)
	 */
	private $label;

	/**
	 * @Gedmo\Slug(fields={"label"}, updatable=false)
	 * @ORM\Column(type="string", length=40, unique=true)
	 */
	private $slug;

	/**
	 * @ORM\Id
	 * @ORM\OneToMany(targetEntity="App\Entity\Core\TagUsage", mappedBy="tag", cascade={"remove"})
	 */
	private $usages;

	/////

	// Id /////

	public function getId() {
		return $this->id;
	}

	// Label /////

	public function getLabel() {
		return $this->label;
	}

	public function setLabel($label) {
		$this->label = $label;
		return $this;
	}

	// Slug /////

	public function getSlug() {
		return $this->slug;
	}

	public function setSlug($slug) {
		$this->slug = $slug;
		return $this;
	}

}