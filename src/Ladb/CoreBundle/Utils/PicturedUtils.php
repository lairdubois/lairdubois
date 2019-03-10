<?php

namespace Ladb\CoreBundle\Utils;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Ladb\CoreBundle\Entity\Core\Block\Gallery;
use Ladb\CoreBundle\Entity\Core\Picture;
use Ladb\CoreBundle\Model\BlockBodiedInterface;
use Ladb\CoreBundle\Model\MultiPicturedInterface;

class PicturedUtils extends AbstractContainerAwareUtils {

	const NAME = 'ladb_core.pictured_utils';

	/////

	public function duplicatePicture(Picture $picture) {
		$om = $this->getDoctrine()->getManager();

		$fileExtension = strtolower(pathinfo($picture->getPath(), PATHINFO_EXTENSION));
		$newResourcePath = sha1(uniqid(mt_rand(), true)).'.'.$fileExtension;

		// Create the new picture
		$newPicture = new Picture();
		$newPicture->setUser($picture->getUser());
		$newPicture->setLegend($picture->getLegend());
		$newPicture->setSourceUrl($picture->getSourceUrl());
		$newPicture->setMasterPath($newResourcePath);
		$newPicture->setRotation($picture->getRotation());
		$newPicture->setSortIndex($picture->getSortIndex());
		$newPicture->setWidth($picture->getWidth());
		$newPicture->setHeight($picture->getHeight());
		$newPicture->setHeightRatio100($picture->getHeightRatio100());
		$newPicture->setCenterX100($picture->getCenterX100());
		$newPicture->setCenterY100($picture->getCenterY100());

		// Copy master picture file
		if (copy($picture->getAbsolutePath(), $newPicture->getAbsoluteMasterPath())) {

			// Save new picture into DB
			$om->persist($newPicture);
			$om->flush();

			return $newPicture;

		}
		return $picture;
	}

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