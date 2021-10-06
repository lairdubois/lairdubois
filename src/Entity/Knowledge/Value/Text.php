<?php

namespace App\Entity\Knowledge\Value;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_knowledge2_value_text")
 * @ORM\Entity(repositoryClass="App\Repository\Knowledge\Value\TextRepository")
 */
class Text extends BaseValue {

	const CLASS_NAME = 'App\Entity\Knowledge\Value\Text';
	const TYPE = 10;

	const TYPE_STRIPPED_NAME = 'text';

	/**
	 * @ORM\Column(type="string", length=100)
	 * @Assert\NotBlank(groups={"mandatory"})
	 * @Assert\Length(max=100)
	 * @Assert\Regex("/^[ a-zA-Z0-9ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ'’’#,.:;«»&+\/-]+$/")
	 */
	protected $data;

	/////

	// Type /////

	public function getType() {
		return self::TYPE;
	}

	// Title /////

	public function getTitle() {
		return $this->getData();
	}

}