<?php

namespace Ladb\CoreBundle\Utils;

use Doctrine\Common\Persistence\ObjectManager;
use Ladb\CoreBundle\Entity\Find\Find;
use Ladb\CoreBundle\Entity\Core\Picture;

class FindUtils extends AbstractContainerAwareUtils {

	const NAME = 'ladb_core.find_utils';

	public function generateMainPicture(Find $find) {
		$om = $this->getDoctrine()->getManager();
		$webScreenshotUtils = $this->get(WebScreenshotUtils::NAME);
		$videoHostingUtils = $this->get(VideoHostingUtils::NAME);

		switch ($find->getKind()) {

			case Find::KIND_WEBSITE:
				$website = $find->getContent();
				$mainPicture = null;

				if (is_null($website->getThumbnail())) {

					// Try to fetch OpenGraph image
					$openGraphUtils = $this->get(OpenGraphUtils::NAME);
					$ogMetas = $openGraphUtils->fetchMetas($website->getUrl());
					if ($ogMetas && isset($ogMetas['og:image'])) {

						$mainPicture = new Picture();
						$mainPicture->setMasterPath(sha1(uniqid(mt_rand(), true)).'.jpg');

						if (isset($ogMetas['og:description'])) {
							$mainPicture->setLegend($ogMetas['og:description']);
						}

						if (copy($ogMetas['og:image'], $mainPicture->getAbsolutePath())) {

							$finfo = finfo_open(FILEINFO_MIME_TYPE);
							if ($finfo == IMAGETYPE_JPEG || $finfo == IMAGETYPE_PNG) {

								list($width, $height) = getimagesize($mainPicture->getAbsolutePath());
								$mainPicture->setWidth($width);
								$mainPicture->setHeight($height);
								$mainPicture->setHeightRatio100($width > 0 ? $height / $width * 100 : 100);

								$om->persist($mainPicture);

							}

						}

					}

					// No picture detected capture a screenshot
					if (is_null($mainPicture)) {
						$mainPicture = $webScreenshotUtils->captureToPicture($website->getUrl(), 1280, 1024, 1280, 1024);
					}

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

							$finfo = finfo_open(FILEINFO_MIME_TYPE);
							if ($finfo == IMAGETYPE_JPEG || $finfo == IMAGETYPE_PNG) {

								list($width, $height) = getimagesize($mainPicture->getAbsolutePath());
								$mainPicture->setWidth($width);
								$mainPicture->setHeight($height);
								$mainPicture->setHeightRatio100($width > 0 ? $height / $width * 100 : 100);

								$om->persist($mainPicture);

							}

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
				$find->setMainPicture($gallery->getPictures()->first());
				break;

			case Find::KIND_EVENT:
				$event = $find->getContent();
				$find->setMainPicture($event->getPictures()->first());
				break;

		}

	}

}