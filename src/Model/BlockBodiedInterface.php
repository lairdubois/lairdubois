<?php

namespace App\Model;

interface BlockBodiedInterface extends BodiedInterface {

	// BodyExtract /////

	public function setBodyExtract($bodyExtract);

	public function getBodyExtract();

	// BodyBlocks /////

	public function addBodyBlock(\App\Entity\Core\Block\AbstractBlock $bodyBlock);

	public function removeBodyBlock(\App\Entity\Core\Block\AbstractBlock $bodyBlock);

	public function getBodyBlocks();

	public function resetBodyBlocks();

	// BodyBlockPictureCount /////

	public function setBodyBlockPictureCount($bodyBlockPictureCount);

	public function getBodyBlockPictureCount();

	// BodyBlockVideoCount /////

	public function setBodyBlockVideoCount($bodyBlockVideoCount);

	public function getBodyBlockVideoCount();

}
