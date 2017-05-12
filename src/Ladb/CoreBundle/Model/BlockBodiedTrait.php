<?php

namespace Ladb\CoreBundle\Model;

trait BlockBodiedTrait {

	// Body /////

	public function setBody($body) {
		$this->body = $body;
		return $this;
	}

	public function getBody() {
		return $this->body;
	}

	// BodyExtract /////

	public function getBodyExtract() {
		$firstBlock = $this->bodyBlocks->first();
		if ($firstBlock instanceof \Ladb\CoreBundle\Entity\Block\Text) {
			return $firstBlock->getHtmlBody();
		}
		return '';
	}

	// BodyBlocks /////

	public function addBodyBlock(\Ladb\CoreBundle\Entity\Block\AbstractBlock $bodyBlock) {
		if (!$this->bodyBlocks->contains($bodyBlock)) {
			$this->bodyBlocks[] = $bodyBlock;
		}
		return $this;
	}

	public function removeBodyBlock(\Ladb\CoreBundle\Entity\Block\AbstractBlock $bodyBlock) {
		$this->bodyBlocks->removeElement($bodyBlock);
	}

	public function getBodyBlocks() {
		return $this->bodyBlocks;
	}

	public function resetBodyBlocks() {
		$this->bodyBlocks = new \Doctrine\Common\Collections\ArrayCollection();
	}

	// BodyBlockPictureCount /////

	public function setBodyBlockPictureCount($bodyBlockPictureCount) {
		$this->bodyBlockPictureCount = $bodyBlockPictureCount;
		return $this;
	}

	public function getBodyBlockPictureCount() {
		return $this->bodyBlockPictureCount;
	}

	// BodyBlockVideoCount /////

	public function setBodyBlockVideoCount($bodyBlockVideoCount) {
		$this->bodyBlockVideoCount = $bodyBlockVideoCount;
		return $this;
	}

	public function getBodyBlockVideoCount() {
		return $this->bodyBlockVideoCount;
	}

}