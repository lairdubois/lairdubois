<?php

namespace Ladb\CoreBundle\Model;

interface BlockBodiedInterface {

	// Body /////

	public function setBody($body);

	public function getBody();

	// BodyBlocks /////

	public function addBodyBlock(\Ladb\CoreBundle\Entity\Block\AbstractBlock $bodyBlock);

	public function removeBodyBlock(\Ladb\CoreBundle\Entity\Block\AbstractBlock $bodyBlock);

	public function getBodyBlocks();

	public function resetBodyBlocks();

	// BodyBlockPictureCount /////

	public function setBodyBlockPictureCount($bodyBlockPictureCount);

	public function getBodyBlockPictureCount();

	// BodyBlockVideoCount /////

	public function setBodyBlockVideoCount($bodyBlockVideoCount);

	public function getBodyBlockVideoCount();

}
