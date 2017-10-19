<?php

namespace Ladb\CoreBundle\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

class ReplyMessage {

	/**
	 * @Assert\Count(min=0, max=4)
	 */
	protected $pictures;
	/**
	 * @Assert\NotBlank()
	 * @Assert\Length(min=2, max=10000)
	 */
	private $body;

	/////

	public function __construct() {
		$this->pictures = new \Doctrine\Common\Collections\ArrayCollection();
	}

	// Body /////

	public function getBody() {
		return $this->body;
	}

	public function setBody($body) {
		$this->body = $body;
		return $this;
	}

	// Pictures /////

	public function addPicture(\Ladb\CoreBundle\Entity\Core\Picture $picture) {
		if (!$this->pictures->contains($picture)) {
			$this->pictures[] = $picture;
		}
		return $this;
	}

	public function removePicture(\Ladb\CoreBundle\Entity\Core\Picture $picture) {
		$this->pictures->removeElement($picture);
	}

	public function getPictures() {
		return $this->pictures;
	}

}