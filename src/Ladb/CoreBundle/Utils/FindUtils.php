<?php

namespace Ladb\CoreBundle\Utils;

use Doctrine\Common\Persistence\ObjectManager;
use Ladb\CoreBundle\Entity\Find\Find;
use Ladb\CoreBundle\Entity\Picture;

class FindUtils extends AbstractContainerAwareUtils {

	const NAME = 'ladb_core.find_utils';

	public function generateMainPicture(Find $find) {
		$om = $this->getDoctrine()->getManager();
		$webScreenshotUtils = $this->get(WebScreenshotUtils::NAME);
		$videoHostingUtils = $this->get(VideoHostingUtils::NAME);

		switch ($find->getKind()) {

			case Find::KIND_WEBSITE:
				$website = $find->getContent();

				if (is_null($website->getThumbnail())) {
					$mainPicture = $webScreenshotUtils->captureToPicture($website->getUrl(), 1280, 1024, 1280, 1024);
					$website->setThumbnail($mainPicture);
				} else {
					$mainPicture = $website->getThumbnail();
				}
				$find->setMainPicture($mainPicture);

				break;

			case Find::KIND_VIDEO:
				$video = $find->getContent();

				if (is_null($video->getThumbnail())) {
					$thumbnailUrl = $videoHostingUtils->getThumbnailUrl($video->getKind(), $video->getEmbedIdentifier());
					if (!is_null($thumbnailUrl)) {

						$mainPicture = new Picture();
						$mainPicture->setMasterPath(sha1(uniqid(mt_rand(), true)).'.jpg');

						if (copy($thumbnailUrl, $mainPicture->getAbsolutePath())) {

							list($width, $height) = getimagesize($mainPicture->getAbsolutePath());
							$mainPicture->setWidth($width);
							$mainPicture->setHeight($height);
							$mainPicture->setHeightRatio100($width > 0 ? $height / $width * 100 : 100);

							$om->persist($mainPicture);
						}

					} else {
						$mainPicture = $webScreenshotUtils->captureToPicture($video->getUrl(), 1280, 1024, 1280, 1024);
					}
					$video->setThumbnail($mainPicture);
				} else {
					$mainPicture = $video->getThumbnail();
				}
				$find->setMainPicture($mainPicture);
				break;

			case Find::KIND_GALLERY:
				$gallery = $find->getContent();
				if (count($gallery->getPictures()) > 0) {
					$find->setMainPicture($gallery->getPictures()[0]);
				}
				break;

			case Find::KIND_EVENT:
				$event = $find->getContent();
				if (count($event->getPictures()) > 0) {
					$find->setMainPicture($event->getPictures()[0]);
				}
				break;

		}

	}

}