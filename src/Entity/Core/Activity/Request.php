<?php

namespace App\Entity\Core\Activity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_core_activity_request")
 * @ORM\Entity(repositoryClass="App\Repository\Core\Activity\RequestRepository")
 */
class Request extends AbstractActivity {

	const CLASS_NAME = 'App\Entity\Core\Activity\Request';
	const STRIPPED_NAME = 'request';

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Core\MemberRequest")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $request;

	/////

	// StrippedName /////

	public function getStrippedName() {
		return self::STRIPPED_NAME;
	}

	// Request /////

	public function setRequest(\App\Entity\Core\MemberRequest $request) {
		$this->request = $request;
		return $this;
	}

	public function getRequest() {
		return $this->request;
	}

}