<?php

namespace Ladb\CoreBundle\Entity\Opencutlist;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table("tbl_opencutlist_download")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Opencutlist\DownloadRepository")
 */
class Download {

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
	 * @ORM\Column(type="string", nullable=true)
	 */
	private $env;

	/**
	 * @ORM\Column(name="client_ip4", type="string")
	 */
	private $clientIp4;

	/**
	 * @ORM\Column(name="client_user_agent", type="string", nullable=true)
	 */
	private $clientUserAgent;

	/////

	// CreatedAt /////

	public function setCreatedAt($createdAt) {
		$this->createdAt = $createdAt;
		return $this;
	}

	public function getCreatedAt() {
		return $this->createdAt;
	}

	// Age /////

	public function getAge() {
		return $this->getCreatedAt()->diff(new \DateTime());
	}

	// Env /////

	public function setEnv($env) {
		$this->env = $env;
		return $this;
	}

	public function getEnv() {
		return $this->env;
	}

	// ClientIp4 /////

	public function setClientIp4($clientIp4) {
		$this->clientIp4 = $clientIp4;
		return $this;
	}

	public function getClientIp4() {
		return $this->clientIp4;
	}

	// ClientUserAgent /////

	public function setClientUserAgent($clientUserAgent) {
		$this->clientUserAgent = $clientUserAgent;
		return $this;
	}

	public function getClientUserAgent() {
		return $this->clientUserAgent;
	}

}
