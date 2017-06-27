<?php

namespace Ladb\CoreBundle\Entity\Core;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table("tbl_core_tag")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Core\TagRepository")
 */
class Tag {

	const CLASS_NAME = 'LadbCoreBundle:Core\Tag';

	/**
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @ORM\Column(type="string", length=40)
	 */
	private $name;

	/**
	 * @Gedmo\Slug(fields={"name"}, updatable=false)
	 * @ORM\Column(type="string", length=40, unique=true)
	 */
	private $slug;

	/**
	 * @ORM\Id
	 * @ORM\OneToMany(targetEntity="Ladb\CoreBundle\Entity\Core\TagUsage", mappedBy="tag", cascade={"remove"})
	 */
	private $usages;

	/////

	// Id /////

	public function getId() {
		return $this->id;
	}

	// Name /////

	public function getName() {
		return $this->name;
	}

	public function setName($name) {
		$this->name = $name;
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