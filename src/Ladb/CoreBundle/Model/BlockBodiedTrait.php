<?php

namespace Ladb\CoreBundle\Model;

trait BlockBodiedTrait {

	use BodiedTrait;

	// BodyExtract /////

	public function setBodyExtract($bodyExtract) {
		$this->bodyExtract = $bodyExtract;
		return $this;
	}

	public function getBodyExtract() {
		return $this->bodyExtract;
	}

	// BodyBlocks /////

	public function addBodyBlock(\Ladb\CoreBundle\Entity\Core\Block\AbstractBlock $bodyBlock) {
		if (!$this->bodyBlocks->contains($bodyBlock)) {
			$this->bodyBlocks[] = $bodyBlock;
		}
		return $this;
	}

	public function removeBodyBlock(\Ladb\CoreBundle\Entity\Core\Block\AbstractBlock $bodyBlock) {
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