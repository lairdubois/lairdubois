<?php

namespace Ladb\CoreBundle\Entity\Knowledge\Value;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_knowledge2_value_language")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Knowledge\Value\LanguageRepository")
 */
class Language extends BaseValue {

	const CLASS_NAME = 'LadbCoreBundle:Knowledge\Value\Language';
	const TYPE = 18;

	const TYPE_STRIPPED_NAME = 'language';

	/**
	 * @ORM\Column(type="string")
	 */
	protected $data;

	/**
	 * @ORM\Column(type="string")
	 * @Assert\NotBlank
	 * @Assert\Language
	 */
	protected $rawLanguage = 'fr';

	/////

	// Type /////

	public function getType() {
		return self::TYPE;
	}

	// RawLanguage /////

	public function getRawLanguage() {
		return $this->rawLanguage;
	}

	public function setRawLanguage($rawLanguage) {
		$this->rawLanguage = $rawLanguage;
		return $this;
	}

}