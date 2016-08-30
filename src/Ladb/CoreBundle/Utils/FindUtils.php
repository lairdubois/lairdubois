<?php

namespace Ladb\CoreBundle\Utils;

use Doctrine\Common\Persistence\ObjectManager;
use Ladb\CoreBundle\Entity\Find\Find;
use Ladb\CoreBundle\Entity\Picture;

class FindUtils {

	const NAME = 'ladb_core.find_utils';

	protected $om;
	protected $webScreenshotUtils;

	public function __construct(ObjectManager $om, WebScreenshotUtils $webScreenshotUtils) {
		$this->om = $om;
		$this->webScreenshotUtils = $webScreenshotUtils;
	}

	public function generateMainPicture(Find $find) {

		switch ($find->getKind()) {

			case Find::KIND_WEBSITE:
				$website = $find->getContent();

				if (is_null($website->getThumbnail())) {
					$mainPicture = $this->webScreenshotUtils->captureToPicture($website->getUrl(), 1280, 1024, 1280, 1024);
					$website->setThumbnail($mainPicture);
				} else {
					$mainPicture = $website->getThumbnail();
				}
				$find->setMainPicture($mainPicture);

				break;

			case Find::KIND_VIDEO:
				$video = $find->getContent();

				if (is_null($video->getThumbnail())) {
					switch ($video->getKind()) {

						case VideoHostingUtils::KIND_YOUTUBE:
							$thumbnailUrl = 'http://img.youtube.com/vi/'.$video->getEmbedIdentifier().'/hqdefault.jpg';
							break;

						case VideoHostingUtils::KIND_YOUTUBEPLAYLIST:
							$hash = json_decode(file_get_contents('http://gdata.youtube.com/feeds/api/playlists/'.$video->getEmbedIdentifier().'?alt=json'), true);
							if ($hash && isset($hash['feed']['media$group']['media$thumbnail'][1]['url'])) {
								$thumbnailUrl = $hash['feed']['media$group']['media$thumbnail'][1]['url'];
							}
							break;

						case VideoHostingUtils::KIND_VIMEO:
							$hash = unserialize(file_get_contents('http://vimeo.com/api/v2/video/'.$video->getEmbedIdentifier().'.php'));
							if ($hash && isset($hash[0]) && isset($hash[0]['thumbnail_large'])) {
								$thumbnailUrl = $hash[0]['thumbnail_large'];
							}
							break;

						case VideoHostingUtils::KIND_DAILYMOTION:
							$thumbnailUrl = 'http://www.dailymotion.com/thumbnail/video/'.$video->getEmbedIdentifier();
							break;

					}
					if (isset($thumbnailUrl)) {

						$mainPicture = new Picture();
						$mainPicture->setMasterPath(sha1(uniqid(mt_rand(), true)).'.jpg');

						if (copy($thumbnailUrl, $mainPicture->getAbsolutePath())) {

							list($width, $height) = getimagesize($mainPicture->getAbsolutePath());
							$mainPicture->setWidth($width);
							$mainPicture->setHeight($height);
							$mainPicture->setHeightRatio100($width > 0 ? $height / $width * 100 : 100);

							$this->om->persist($mainPicture);
						}

					} else {
						$mainPicture = $this->webScreenshotUtils->captureToPicture($video->getUrl(), 1280, 1024, 1280, 1024);
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