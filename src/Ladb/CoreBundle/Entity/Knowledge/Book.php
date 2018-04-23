<?php

namespace Ladb\CoreBundle\Entity\Knowledge;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ladb\CoreBundle\Entity\Knowledge\Value\Price;
use Ladb\CoreBundle\Entity\Knowledge\Value\Url;
use Symfony\Component\Validator\Constraints as Assert;
use Ladb\CoreBundle\Entity\Knowledge\Value\Text;
use Ladb\CoreBundle\Entity\Knowledge\Value\Longtext;
use Ladb\CoreBundle\Entity\Knowledge\Value\Integer;
use Ladb\CoreBundle\Entity\Knowledge\Value\Picture;
use Ladb\CoreBundle\Entity\Knowledge\Value\Language;
use Ladb\CoreBundle\Entity\Knowledge\Value\Isbn;

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
	const FIELD_BACK_COVER = 'back_cover';
	const FIELD_AUTHOR = 'author';
	const FIELD_EDITOR = 'editor';
	const FIELD_COLLECTION = 'collection';
	const FIELD_CATALOG_LINK = 'catalog_link';
	const FIELD_SUMMARY = 'summary';
	const FIELD_SUBJECTS = 'subjects';
	const FIELD_LANGUAGE = 'language';
	const FIELD_TRANSLATED = 'translated';
	const FIELD_PAGE_COUNT = 'page_count';
	const FIELD_ISBN = 'isbn';
	const FIELD_PUBLISH_YEAR = 'publish_year';
	const FIELD_PRICE = 'price';

	public static $FIELD_DEFS = array(
		Book::FIELD_TITLE        => array(Book::ATTRIB_TYPE => Text::TYPE_STRIPPED_NAME, Book::ATTRIB_MULTIPLE => true, Book::ATTRIB_MANDATORY => true, Book::ATTRIB_CONSTRAINTS => array(array('\\Ladb\\CoreBundle\\Validator\\Constraints\\UniqueBook', array('excludedId' => '@getId'))), Book::ATTRIB_DATA_CONSTRAINTS => array(array('\\Ladb\\CoreBundle\\Validator\\Constraints\\OneThing', array('message' => 'N\'indiquez qu\'un seul Nom français par proposition.')))),
		Book::FIELD_COVER        => array(Book::ATTRIB_TYPE => Picture::TYPE_STRIPPED_NAME, Book::ATTRIB_MULTIPLE => false, Book::ATTRIB_MANDATORY => true, Book::ATTRIB_POST_PROCESSOR => \Ladb\CoreBundle\Entity\Core\Picture::POST_PROCESSOR_SQUARE),
		Book::FIELD_BACK_COVER   => array(Book::ATTRIB_TYPE => Picture::TYPE_STRIPPED_NAME, Book::ATTRIB_MULTIPLE => false, Book::ATTRIB_POST_PROCESSOR => \Ladb\CoreBundle\Entity\Core\Picture::POST_PROCESSOR_SQUARE),
		Book::FIELD_AUTHOR       => array(Book::ATTRIB_TYPE => Text::TYPE_STRIPPED_NAME, Book::ATTRIB_MULTIPLE => true, Book::ATTRIB_FILTER_QUERY => '@author:"%q%"'),
		Book::FIELD_EDITOR       => array(Book::ATTRIB_TYPE => Text::TYPE_STRIPPED_NAME, Book::ATTRIB_MULTIPLE => false, Book::ATTRIB_FILTER_QUERY => '@editor:"%q%"'),
		Book::FIELD_COLLECTION   => array(Book::ATTRIB_TYPE => Text::TYPE_STRIPPED_NAME, Book::ATTRIB_MULTIPLE => false, Book::ATTRIB_FILTER_QUERY => '@collection:"%q%"'),
		Book::FIELD_CATALOG_LINK => array(Book::ATTRIB_TYPE => Url::TYPE_STRIPPED_NAME, Book::ATTRIB_MULTIPLE => false),
		Book::FIELD_SUMMARY      => array(Book::ATTRIB_TYPE => Longtext::TYPE_STRIPPED_NAME, Book::ATTRIB_MULTIPLE => false),
		Book::FIELD_SUBJECTS     => array(Book::ATTRIB_TYPE => Text::TYPE_STRIPPED_NAME, Book::ATTRIB_MULTIPLE => true, Book::ATTRIB_FILTER_QUERY => '@subjects:"%q%"', Book::ATTRIB_DATA_CONSTRAINTS => array(array('\\Ladb\\CoreBundle\\Validator\\Constraints\\OneThing', array('message' => 'N\'indiquez qu\'un seul sujet par proposition.')))),
		Book::FIELD_LANGUAGE     => array(Book::ATTRIB_TYPE => Language::TYPE_STRIPPED_NAME, Book::ATTRIB_MULTIPLE => false, Book::ATTRIB_FILTER_QUERY => '@language:"%q%"'),
		Book::FIELD_TRANSLATED   => array(Book::ATTRIB_TYPE => Integer::TYPE_STRIPPED_NAME, Book::ATTRIB_MULTIPLE => false, Book::ATTRIB_CHOICES => array(1 => 'Oui', 0 => 'Non')),
		Book::FIELD_PAGE_COUNT   => array(Book::ATTRIB_TYPE => Integer::TYPE_STRIPPED_NAME, Book::ATTRIB_MULTIPLE => false),
		Book::FIELD_ISBN         => array(Book::ATTRIB_TYPE => Isbn::TYPE_STRIPPED_NAME, Book::ATTRIB_MULTIPLE => false),
		Book::FIELD_PUBLISH_YEAR => array(Book::ATTRIB_TYPE => Integer::TYPE_STRIPPED_NAME, Book::ATTRIB_MULTIPLE => false),
		Book::FIELD_PRICE        => array(Book::ATTRIB_TYPE => Price::TYPE_STRIPPED_NAME, Book::ATTRIB_MULTIPLE => false),
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


	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\Picture", cascade={"persist"})
	 * @ORM\JoinColumn(name="back_cover_id", nullable=true)
	 * @Assert\Type(type="Ladb\CoreBundle\Entity\Core\Picture")
	 */
	private $backCover;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Picture", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_book_value_back_cover")
	 * @ORM\OrderBy({"voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $backCoverValues;


	/**
	 * @ORM\Column(type="string", nullable=true)
	 */
	private $author;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Text", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_book_value_author")
	 * @ORM\OrderBy({"voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $authorValues;


	/**
	 * @ORM\Column(type="string", nullable=true)
	 */
	private $editor;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Text", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_book_value_editor")
	 * @ORM\OrderBy({"voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $editorValues;


	/**
	 * @ORM\Column(type="string", nullable=true)
	 */
	private $collection;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Text", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_book_value_collection")
	 * @ORM\OrderBy({"voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $collectionValues;


	/**
	 * @ORM\Column(type="string", nullable=true, length=255)
	 */
	private $catalogLink;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Url", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_book_value_catalog_link")
	 * @ORM\OrderBy({"voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $catalogLinkValues;


	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	private $summary;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Longtext", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_book_value_summary")
	 * @ORM\OrderBy({"voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $summaryValues;


	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	private $subjects;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Text", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_book_value_subjects")
	 * @ORM\OrderBy({"voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $subjectsValues;


	/**
	 * @ORM\Column(type="string", nullable=true)
	 */
	private $language;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Language", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_book_value_language")
	 * @ORM\OrderBy({"voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $languageValues;


	/**
	 * @ORM\Column(type="boolean", nullable=true)
	 */
	private $translated;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Integer", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_book_value_translated")
	 * @ORM\OrderBy({"voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $translatedValues;


	/**
	 * @ORM\Column(type="integer", nullable=true)
	 */
	private $pageCount;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Integer", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_book_value_page_count")
	 * @ORM\OrderBy({"voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $pageCountValues;


	/**
	 * @ORM\Column(type="string", nullable=true, length=20)
	 */
	private $isbn;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Isbn", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_book_value_isbn")
	 * @ORM\OrderBy({"voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $isbnValues;


	/**
	 * @ORM\Column(type="integer", nullable=true)
	 */
	private $publishYear;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Integer", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_book_value_publish_year")
	 * @ORM\OrderBy({"voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $publishYearValues;


	/**
	 * @ORM\Column(type="string", nullable=true)
	 */
	private $price;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Price", cascade={"all"})
	 * @ORM\JoinTable(name="tbl_knowledge2_book_value_price")
	 * @ORM\OrderBy({"voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $priceValues;


	/////

	public function __construct() {
		$this->titleValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->coverValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->backCoverValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->authorValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->editorValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->collectionValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->catalogLinkValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->summaryValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->subjectsValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->languageValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->translatedValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->pageCountValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->isbnValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->priceValues = new \Doctrine\Common\Collections\ArrayCollection();
		$this->publishYearValues = new \Doctrine\Common\Collections\ArrayCollection();
	}

	/////

	// IsRejected /////

	public function getIsRejected() {
		return $this->getTitleRejected() || $this->getCoverRejected();
	}

	// Type /////

	public function getType() {
		return Book::TYPE;
	}

	// Title /////

	public function getTitleWorkaround() {
		return $this->getTitle();
	}

	// Body /////

	public function getBody() {
		$terms = array($this->getTitle());
		return implode($terms, ',');
	}

	// StrippedName /////

	public function getStrippedName() {
		return Book::STRIPPED_NAME;
	}

	// FieldDefs /////

	public function getFieldDefs() {
		return Book::$FIELD_DEFS;
	}

	// TitleValues /////

	public function addTitleValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Text $titleValue) {
		if (!$this->titleValues->contains($titleValue)) {
			$this->titleValues[] = $titleValue;
		}
		return $this;
	}

	public function removeTitleValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Text $titleValue) {
		$this->titleValues->removeElement($titleValue);
	}

	public function setTitleValues($titleValues) {
		$this->titleValues = $titleValues;
	}

	public function getTitleValues() {
		return $this->titleValues;
	}

	// TitleRejected /////

	public function setTitleRejected($titleRejected) {
		$this->titleRejected = $titleRejected;
		return $this;
	}

	public function getTitleRejected() {
		return $this->titleRejected;
	}

	// Cover /////

	public function setCover($cover) {
		return $this->setMainPicture($cover);
	}

	public function getCover() {
		return $this->getMainPicture();
	}

	// CoverValues /////

	public function addCoverValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Picture $coverValue) {
		if (!$this->coverValues->contains($coverValue)) {
			$this->coverValues[] = $coverValue;
		}
		return $this;
	}

	public function removeCoverValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Picture $coverValue) {
		$this->coverValues->removeElement($coverValue);
	}

	public function setCoverValues($coverValues) {
		$this->coverValues = $coverValues;
	}

	public function getCoverValues() {
		return $this->coverValues;
	}

	// CoverRejected /////

	public function setCoverRejected($coverRejected) {
		$this->coverRejected = $coverRejected;
		return $this;
	}

	public function getCoverRejected() {
		return $this->coverRejected;
	}

	// BackCover /////

	public function setBackCover($backCover) {
		$this->backCover = $backCover;
		return $this;
	}

	public function getBackCover() {
		return $this->backCover;
	}

	// BackCoverValues /////

	public function addBackCoverValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Picture $backCoverValue) {
		if (!$this->backCoverValues->contains($backCoverValue)) {
			$this->backCoverValues[] = $backCoverValue;
		}
		return $this;
	}

	public function removeBackCoverValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Picture $backCoverValue) {
		$this->backCoverValues->removeElement($backCoverValue);
	}

	public function setBackCoverValues($backCoverValues) {
		$this->backCoverValues = $backCoverValues;
	}

	public function getBackCoverValues() {
		return $this->backCoverValues;
	}

	// Author /////

	public function setAuthor($author) {
		$this->author = $author;
		return $this;
	}

	public function getAuthor() {
		return $this->author;
	}

	// AuthorValues /////

	public function addAuthorValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Text $authorValue) {
		if (!$this->authorValues->contains($authorValue)) {
			$this->authorValues[] = $authorValue;
		}
		return $this;
	}

	public function removeAuthorValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Text $authorValue) {
		$this->authorValues->removeElement($authorValue);
	}

	public function setAuthorValues($authorValues) {
		$this->authorValues = $authorValues;
	}

	public function getAuthorValues() {
		return $this->authorValues;
	}

	// Editor /////

	public function setEditor($editor) {
		$this->editor = $editor;
		return $this;
	}

	public function getEditor() {
		return $this->editor;
	}

	// EditorValues /////

	public function addEditorValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Text $editorValue) {
		if (!$this->editorValues->contains($editorValue)) {
			$this->editorValues[] = $editorValue;
		}
		return $this;
	}

	public function removeEditorValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Text $editorValue) {
		$this->editorValues->removeElement($editorValue);
	}

	public function setEditorValues($editorValues) {
		$this->editorValues = $editorValues;
	}

	public function getEditorValues() {
		return $this->editorValues;
	}

	// Collection /////

	public function setCollection($collection) {
		$this->collection = $collection;
		return $this;
	}

	public function getCollection() {
		return $this->collection;
	}

	// CollectionValues /////

	public function addCollectionValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Text $collectionValue) {
		if (!$this->collectionValues->contains($collectionValue)) {
			$this->collectionValues[] = $collectionValue;
		}
		return $this;
	}

	public function removeCollectionValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Text $collectionValue) {
		$this->collectionValues->removeElement($collectionValue);
	}

	public function setCollectionValues($collectionValues) {
		$this->collectionValues = $collectionValues;
	}

	public function getCollectionValues() {
		return $this->collectionValues;
	}

	// CatalogLink /////

	public function setCatalogLink($catalogLink) {
		$this->catalogLink = $catalogLink;
		return $this;
	}

	public function getCatalogLink() {
		return $this->catalogLink;
	}

	// CatalogLinkValues /////

	public function addCatalogLinkValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Url $catalogLinkValue) {
		if (!$this->catalogLinkValues->contains($catalogLinkValue)) {
			$this->catalogLinkValues[] = $catalogLinkValue;
		}
		return $this;
	}

	public function removeCatalogLinkValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Url $catalogLinkValue) {
		$this->catalogLinkValues->removeElement($catalogLinkValue);
	}

	public function setCatalogLinkValues($catalogLinkValues) {
		$this->catalogLinkValues = $catalogLinkValues;
	}

	public function getCatalogLinkValues() {
		return $this->catalogLinkValues;
	}

	// Summary /////

	public function setSummary($summary) {
		$this->summary = $summary;
		return $this;
	}

	public function getSummary() {
		return $this->summary;
	}

	// SummaryValues /////

	public function addSummaryValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Longtext $summaryValue) {
		if (!$this->summaryValues->contains($summaryValue)) {
			$this->summaryValues[] = $summaryValue;
		}
		return $this;
	}

	public function removeSummaryValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Longtext $summaryValue) {
		$this->summaryValues->removeElement($summaryValue);
	}

	public function setSummaryValues($summaryValues) {
		$this->summaryValues = $summaryValues;
	}

	public function getSummaryValues() {
		return $this->summaryValues;
	}

	// Subjects /////

	public function setSubjects($subjects) {
		$this->subjects = $subjects;
		return $this;
	}

	public function getSubjects() {
		return $this->subjects;
	}

	// SubjectsValues /////

	public function addSubjectsValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Text $subjectsValue) {
		if (!$this->subjectsValues->contains($subjectsValue)) {
			$this->subjectsValues[] = $subjectsValue;
		}
		return $this;
	}

	public function removeSubjectsValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Text $subjectsValue) {
		$this->subjectsValues->removeElement($subjectsValue);
	}

	public function setSubjectsValues($subjectsValues) {
		$this->subjectsValues = $subjectsValues;
	}

	public function getSubjectsValues() {
		return $this->subjectsValues;
	}

	// Language /////

	public function setLanguage($language) {
		$this->language = $language;
		return $this;
	}

	public function getLanguage() {
		return $this->language;
	}

	// LanguageValues /////

	public function addLanguageValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Language $languageValue) {
		if (!$this->languageValues->contains($languageValue)) {
			$this->languageValues[] = $languageValue;
		}
		return $this;
	}

	public function removeLanguageValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Language $languageValue) {
		$this->languageValues->removeElement($languageValue);
	}

	public function setLanguageValues($languageValues) {
		$this->languageValues = $languageValues;
	}

	public function getLanguageValues() {
		return $this->languageValues;
	}

	// Translated /////

	public function setTranslated($translated) {
		$this->translated = $translated;
		return $this;
	}

	public function getTranslated() {
		return $this->translated;
	}

	// TranslatedValues /////

	public function addTranslatedValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Integer $translatedValue) {
		if (!$this->translatedValues->contains($translatedValue)) {
			$this->translatedValues[] = $translatedValue;
		}
		return $this;
	}

	public function removeTranslatedValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Integer $translatedValue) {
		$this->translatedValues->removeElement($translatedValue);
	}

	public function setTranslatedValues($translatedValues) {
		$this->translatedValues = $translatedValues;
	}

	public function getTranslatedValues() {
		return $this->translatedValues;
	}

	// PageCount /////

	public function setPageCount($pageCount) {
		$this->pageCount = $pageCount;
		return $this;
	}

	public function getPageCount() {
		return $this->pageCount;
	}

	// PageCountValues /////

	public function addPageCountValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Integer $pageCountValue) {
		if (!$this->pageCountValues->contains($pageCountValue)) {
			$this->pageCountValues[] = $pageCountValue;
		}
		return $this;
	}

	public function removePageCountValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Integer $pageCountValue) {
		$this->pageCountValues->removeElement($pageCountValue);
	}

	public function setPageCountValues($pageCountValues) {
		$this->pageCountValues = $pageCountValues;
	}

	public function getPageCountValues() {
		return $this->pageCountValues;
	}

	// Isbn /////

	public function setIsbn($isbn) {
		$this->isbn = $isbn;
		return $this;
	}

	public function getIsbn() {
		return $this->isbn;
	}

	// IsbnValues /////

	public function addIsbnValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Isbn $isbnValue) {
		if (!$this->isbnValues->contains($isbnValue)) {
			$this->isbnValues[] = $isbnValue;
		}
		return $this;
	}

	public function removeIsbnValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Isbn $isbnValue) {
		$this->isbnValues->removeElement($isbnValue);
	}

	public function setIsbnValues($isbnValues) {
		$this->isbnValues = $isbnValues;
	}

	public function getIsbnValues() {
		return $this->isbnValues;
	}

	// PublishYear /////

	public function setPublishYear($publishYear) {
		$this->publishYear = $publishYear;
		return $this;
	}

	public function getPublishYear() {
		return $this->publishYear;
	}

	// PublishYearValues /////

	public function addPublishYearValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Integer $publishYearValue) {
		if (!$this->publishYearValues->contains($publishYearValue)) {
			$this->publishYearValues[] = $publishYearValue;
		}
		return $this;
	}

	public function removePublishYearValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Integer $publishYearValue) {
		$this->publishYearValues->removeElement($publishYearValue);
	}

	public function setPublishYearValues($publishYearValues) {
		$this->publishYearValues = $publishYearValues;
	}

	public function getPublishYearValues() {
		return $this->publishYearValues;
	}

	// Price /////

	public function setPrice($price) {
		$this->price = $price;
		return $this;
	}

	public function getPrice() {
		return $this->price;
	}

	// PriceValues /////

	public function addPriceValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Price $priceValue) {
		if (!$this->priceValues->contains($priceValue)) {
			$this->priceValues[] = $priceValue;
		}
		return $this;
	}

	public function removePriceValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Price $priceValue) {
		$this->priceValues->removeElement($priceValue);
	}

	public function setPriceValues($priceValues) {
		$this->priceValues = $priceValues;
	}

	public function getPriceValues() {
		return $this->priceValues;
	}

}