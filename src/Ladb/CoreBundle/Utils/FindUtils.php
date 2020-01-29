<?php

namespace Ladb\CoreBundle\Utils;

use Doctrine\Common\Persistence\ObjectManager;
use Ladb\CoreBundle\Entity\Find\Find;
use Ladb\CoreBundle\Entity\Core\Picture;
use Ladb\CoreBundle\Manager\Core\PictureManager;

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

				$website->setHost(parse_url($website->getUrl(), PHP_URL_HOST));

				if (is_null($website->getThumbnail())) {

					// Try to fetch OpenGraph image, title and description
					$openGraphUtils = $this->get(OpenGraphUtils::NAME);
					$ogMetas = $openGraphUtils->fetchMetas($website->getUrl());
					if ($ogMetas) {

						if (isset($ogMetas['og:image'])) {
							$pictureManager = $this->get(PictureManager::NAME);
							$mainPicture = $pictureManager->createFromUrl($ogMetas['og:image'], false);
						}

						if (isset($ogMetas['og:title'])) {
							$ogTitle = substr($ogMetas['og:title'], 0, 255);
							$website->setTitle($ogTitle);
						}

						if (isset($ogMetas['og:description'])) {
							$ogDescription = substr($ogMetas['og:description'], 0, 255);
							$website->setDescription($ogDescription);
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
						$pictureManager = $this->get(PictureManager::NAME);
						$mainPicture = $pictureManager->createFromUrl($thumbnailUrl, false);
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