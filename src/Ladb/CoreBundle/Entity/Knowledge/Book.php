<?php

namespace Ladb\CoreBundle\Entity\Knowledge;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Ladb\CoreBundle\Entity\Knowledge\Value\Text;
use Ladb\CoreBundle\Entity\Knowledge\Value\Integer;
use Ladb\CoreBundle\Entity\Knowledge\Value\Picture;

/**
 * Ladb\CoreBundle\Entity\Knowledge\Book
 *
 * @ORM\Table("tbl_knowledge2_book")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Knowledge\BookRepository")
 */
class Book extends AbstractKnowledge {

	const CLASS_NAME = 'LadbCoreBundle:Knowledge\Book';
	const TYPE = 119;

	const STRIPPED_NAME = 'book';

	const FIELD_TITLE = 'title';
	const FIELD_COVER = 'cover';

	public static $FIELD_DEFS = array(
		Book::FIELD_TITLE => array(Book::ATTRIB_TYPE => Text::TYPE_STRIPPED_NAME, Book::ATTRIB_MULTIPLE => true, Book::ATTRIB_MANDATORY => true, Book::ATTRIB_CONSTRAINTS => array(array('\\Ladb\\CoreBundle\\Validator\\Constraints\\UniqueBook', array('excludedId' => '@getId'))), Book::ATTRIB_DATA_CONSTRAINTS => array( array('\\Ladb\\CoreBundle\\Validator\\Constraints\\OneThing', array('message' => 'N\'indiquez qu\'un seul Nom français par proposition.')))),
		Book::FIELD_COVER => array(Book::ATTRIB_TYPE => Picture::TYPE_STRIPPED_NAME, Book::ATTRIB_MULTIPLE => false, Book::ATTRIB_MANDATORY => true, Book::ATTRIB_POST_PROCESSOR => \Ladb\CoreBundle\Entity\Core\Picture::POST_PROCESSOR_SQUARE),
	);

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Text", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_book_value_title")
	 * @ORM\OrderBy({"voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $titleValues;

	/**
	 * @ORM\Column(type="boolean", nullable=false, name="title_rejected")
	 */
	private $titleRejected = false;


	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Picture", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_book_value_cover")
	 * @ORM\OrderBy({"voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $coverValues;

	/**
	 * @ORM\Column(type="boolean", nullable=false, name="cover_rejected")
	 */
	private $coverRejected = false;


	/////

	public function __construct() {
		$this->titleValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->coverValues = new \Doctrine\Common\Collections\ArrayCollection();
	}

	/////

	// IsRejected /////

	public function getIsRejected() {
		return $this->getTitleRejected() || $this->getCoverRejected();
	}

	// Type /////

	public function getTitleRejected() {
		return $this->titleRejected;
	}

	// Title /////

	public function setTitleRejected($titleRejected) {
		$this->titleRejected = $titleRejected;
		return $this;
	}

	// Body /////

	public function getCoverRejected() {
		return $this->coverRejected;
	}

	// StrippedName /////

	public function setCoverRejected($coverRejected) {
		$this->coverRejected = $coverRejected;
		return $this;
	}

	// FieldDefs /////

	public function getType() {
		return Book::TYPE;
	}

	// TitleValues /////

	public function getTitleWorkaround() {
		return $this->getTitle();
	}

	public function getBody() {
		$terms = array($this->getTitle());
		return implode($terms, ',');
	}

	public function getStrippedName() {
		return Book::STRIPPED_NAME;
	}

	public function getFieldDefs() {
		return Book::$FIELD_DEFS;
	}

	// TitleRejected /////

	public function addTitleValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Text $titleValue) {
		if (!$this->titleValues->contains($titleValue)) {
			$this->titleValues[] = $titleValue;
		}
		return $this;
	}

	public function removeTitleValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Text $titleValue) {
		$this->titleValues->removeElement($titleValue);
	}

	// Cover /////

	public function getTitleValues() {
		return $this->titleValues;
	}

	public function setTitleValues($titleValues) {
		$this->titleValues = $titleValues;
	}

	// CoverValues /////

	public function setCover($cover) {
		return $this->setMainPicture($cover);
	}

	public function getCover() {
		return $this->getMainPicture();
	}

	public function addCoverValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Picture $coverValue) {
		if (!$this->coverValues->contains($coverValue)) {
			$this->coverValues[] = $coverValue;
		}
		return $this;
	}

	public function removeCoverValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Picture $coverValue) {
		$this->coverValues->removeElement($coverValue);
	}

	// CoverRejected /////

	public function getCoverValues() {
		return $this->coverValues;
	}

	public function setCoverValues($coverValues) {
		$this->coverValues = $coverValues;
	}

}