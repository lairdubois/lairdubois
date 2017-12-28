<?php

namespace Ladb\CoreBundle\Model;

interface BlockBodiedInterface {

	// Body /////

	public function setBody($body);

	public function getBody();

	// BodyExtract /////

	public function setBodyExtract($bodyExtract);

	public function getBodyExtract();

	// BodyBlocks /////

	public function addBodyBlock(\Ladb\CoreBundle\Entity\Core\Block\AbstractBlock $bodyBlock);

	public function removeBodyBlock(\Ladb\CoreBundle\Entity\Core\Block\AbstractBlock $bodyBlock);

	public function getBodyBlocks();

	public function resetBodyBlocks();

	// BodyBlockPictureCount /////

	public function setBodyBlockPictureCount($bodyBlockPictureCount);

	public function getBodyBlockPictureCount();

	// BodyBlockVideoCount /////

	public function setBodyBlockVideoCount($bodyBlockVideoCount);

	public function getBodyBlockVideoCount();

}
