<?php

namespace App\Utils;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use App\Entity\Core\Block\Gallery;
use App\Entity\Core\Picture;
use App\Model\BlockBodiedInterface;
use App\Model\MultiPicturedInterface;

class PicturedUtils extends AbstractContainerAwareUtils {

	const NAME = 'ladb_core.pictured_utils';

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

	public function getPictureSitemapData(Picture $picture = null) {
		if (is_null($picture)) {
			return null;
		}
		return array(
			'loc'     => $this->get('liip_imagine.cache.manager')->getBrowserPath($picture->getWebPath(), '1024x1024i'),
			'caption' => $picture->getLegend(),
		);
	}

}