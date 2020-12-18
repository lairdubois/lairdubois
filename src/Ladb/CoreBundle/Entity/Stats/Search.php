<?php

namespace Ladb\CoreBundle\Entity\Stats;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table("tbl_stats_search")
 * @ORM\Entity()
 */
class Search {

	const CLASS_NAME = 'LadbCoreBundle:Stats\Search';

	/**
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @ORM\Column(name="created_at", type="datetime")
	 * @Gedmo\Timestampable(on="create")
	 */
	protected $createdAt;

	/**
	 * @ORM\Column(type="string", length=14, name="session_identifier")
	 */
	private $sessionIdentifier;

	/**
	 * @ORM\Column(type="string", length=50)
	 */
	private $context;

	/**
	 * @ORM\Column(type="text")
	 */
	private $query;

	/**
	 * @ORM\Column(type="integer", name="total_hits")
	 */
	private $totalHits;

	/////

	// Id /////

	public function getId() {
		return $this->id;
	}

	// CreatedAt /////

	public function setCreatedAt($createdAt) {
		$this->createdAt = $createdAt;
		return $this;
	}

	public function getCreatedAt() {
		return $this->createdAt;
	}

	// SessionIdentifier /////

	public function getSessionIdentifier() {
		return $this->sessionIdentifier;
	}

	public function setSessionIdentifier($sessionIdentifier) {
		$this->sessionIdentifier = $sessionIdentifier;
	}

	// Context /////

	public function getContext() {
		return $this->context;
	}

	public function setContext($context) {
		$this->context = $context;
	}

	// Query /////

	public function getQuery() {
		return $this->query;
	}

	public function setQuery($query) {
		$this->query = $query;
	}

	// TotalHits /////

	public function getTotalHits() {
		return $this->totalHits;
	}

	public function setTotalHits($totalHits) {
		$this->totalHits = $totalHits;
	}

}