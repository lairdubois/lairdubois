<?php

namespace Ladb\CoreBundle\Entity\Core\Referer;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table("tbl_core_referer")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Core\Referer\RefererRepository")
 */
class Referer {

	const CLASS_NAME = 'LadbCoreBundle:Core\Referer\Referer';

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
	private $createdAt;

	/**
	 * @ORM\Column(name="updated_at", type="datetime", nullable=true)
	 * @Gedmo\Timestampable(on="update")
	 */
	private $updatedAt;

	/**
	 * @ORM\Column(type="string", length=255, nullable=false)
	 */
	private $title;

	/**
	 * @ORM\Column(name="base_url", type="string", length=255, nullable=false)
	 */
	private $baseUrl;

	/**
	 * @ORM\Column(name="favicon_url", type="string", length=255, nullable=true)
	 */
	private $faviconUrl = null;

	/**
	 * @ORM\Column(name="route_pattern", type="string", length=100, nullable=true)
	 */
	private $routePattern = null;

	/**
	 * @ORM\Column(name="route_title_pattern", type="string", length=100, nullable=true)
	 */
	private $routeTitlePattern = null;

	/**
	 * @ORM\Column(name="route_title_replacement", type="string", length=100, nullable=true)
	 */
	private $routeTitleReplacement = null;

	/////

	// Id /////

	public function getId() {
		return $this->id;
	}

	// CreatedAt /////

	public function getCreatedAt() {
		return $this->createdAt;
	}

	public function setCreatedAt($createdAt) {
		$this->createdAt = $createdAt;
		return $this;
	}

	// UpdatedAt /////

	public function getUpdatedAt() {
		return $this->updatedAt;
	}

	public function setUpdatedAt($updatedAt) {
		$this->updatedAt = $updatedAt;
		return $this;
	}

	// Label /////

	public function getTitle() {
		return $this->title;
	}

	public function setTitle($label) {
		$this->title = $label;
		return $this;
	}

	// BaseUrl /////

	public function getBaseUrl() {
		return $this->baseUrl;
	}

	public function setBaseUrl($baseUrl) {
		$this->baseUrl = $baseUrl;
		return $this;
	}

	// FaviconUrl /////

	public function getFaviconUrl() {
		return $this->faviconUrl;
	}

	public function setFaviconUrl($faviconUrl) {
		$this->faviconUrl = $faviconUrl;
		return $this;
	}

	// RoutePattern /////

	public function getRoutePattern() {
		return $this->routePattern;
	}

	public function setRoutePattern($routePattern) {
		$this->routePattern = $routePattern;
		return $this;
	}

	// RouteTitlePattern /////

	public function getRouteTitlePattern() {
		return $this->routeTitlePattern;
	}

	public function setRouteTitlePattern($routeTitlePattern) {
		$this->routeTitlePattern = $routeTitlePattern;
	}

	// RouteTitlePatternCaptureIndex /////

	public function getRouteTitleReplacement() {
		return $this->routeTitleReplacement;
	}

	public function setRouteTitleReplacement($routeTitlePatternCaptureIndex) {
		$this->routeTitleReplacement = $routeTitlePatternCaptureIndex;
	}

}