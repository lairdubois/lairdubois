<?php

namespace App\Utils;

use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Find\Find;
use App\Entity\Core\Picture;
use App\Manager\Core\PictureManager;

class FindUtils extends AbstractContainerAwareUtils {

	public function generateMainPicture(Find $find) {
		$webScreenshotUtils = $this->get(WebScreenshotUtils::class);
		$videoHostingUtils = $this->get(VideoHostingUtils::class);

		switch ($find->getKind()) {

			case Find::KIND_WEBSITE:
				$website = $find->getContent();
				$mainPicture = null;

				$website->setHost(parse_url($website->getUrl(), PHP_URL_HOST));

				if (is_null($website->getThumbnail())) {

					// Try to fetch OpenGraph image, title and description
					$openGraphUtils = $this->get(OpenGraphUtils::class);
					$ogMetas = $openGraphUtils->fetchMetas($website->getUrl());
					if ($ogMetas) {

						if (isset($ogMetas['og:image'])) {
							$pictureManager = $this->get(PictureManager::class);
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
						$pictureManager = $this->get(PictureManager::class);
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

		}

	}

}