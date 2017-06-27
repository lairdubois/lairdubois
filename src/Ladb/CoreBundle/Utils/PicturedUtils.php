<?php

namespace Ladb\CoreBundle\Utils;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Ladb\CoreBundle\Entity\Block\Gallery;
use Ladb\CoreBundle\Entity\Core\Picture;
use Ladb\CoreBundle\Model\BlockBodiedInterface;
use Ladb\CoreBundle\Model\MultiPicturedInterface;

class PicturedUtils {

	const NAME = 'ladb_core.pictured_utils';

	private $imagineCacheManager ;

	public function __construct(CacheManager $imagineCacheManager) {
		$this->imagineCacheManager = $imagineCacheManager;
	}

	/////

	public function resetPictures($entity) {
		if ($entity instanceof MultiPicturedInterface) {
			$entity->resetPictures();
		}
		if ($entity instanceof BlockBodiedInterface) {
			foreach ($entity->getBodyBlocks() as $block) {
				if ($block instanceof Gallery) {
					$block->resetPictures();
				}
			}
		}
	}

	public function getPictureSitemapData(Picture $picture) {
		return array(
			'loc'     => $this->imagineCacheManager->getBrowserPath($picture->getWebPath(), '1024x1024i'),
			'caption' => $picture->getLegend(),
		);
	}

}