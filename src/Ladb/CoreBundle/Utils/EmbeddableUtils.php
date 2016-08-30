<?php

namespace Ladb\CoreBundle\Utils;

use Symfony\Component\HttpFoundation\Request;
use Imagine\Filter\Advanced\RelativeResize;
use Imagine\Gd\Font;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\Point;
use Imagine\Image\Palette\RGB;
use Ladb\CoreBundle\Model\ChildInterface;
use Ladb\CoreBundle\Model\LicensedInterface;
use Ladb\CoreBundle\Model\BasicEmbeddableInterface;
use Ladb\CoreBundle\Model\EmbeddableInterface;
use Ladb\CoreBundle\Entity\Block\Gallery;
use Ladb\CoreBundle\Entity\Picture;
use Ladb\CoreBundle\Entity\Referer\Referer;
use Ladb\CoreBundle\Entity\Referer\Referral;
use Ladb\CoreBundle\Model\BlockBodiedInterface;
use Ladb\CoreBundle\Model\MultiPicturedInterface;
use Ladb\CoreBundle\Model\PicturedInterface;

class EmbeddableUtils extends AbstractContainerAwareUtils {

	const NAME = 'ladb_core.embeddable_utils';

	/////

	private function _retrievePageTitle($url, $pattern = null, $replacement = null) {
		try {
			$str = file_get_contents($url);
			if (strlen($str) > 0) {
				$matches = null;
				if (preg_match("/\<title\>(.*)\<\/title\>/", $str, $matches)) {
					$title = $matches[1];
					if (strlen($title) > 0) {
						if (!is_null($pattern) && !is_null($replacement)) {
							$returnValue = preg_replace($pattern, $replacement, $title);
							if (!is_null($returnValue)) {
								return $returnValue;
							}
						}
						return $title;
					}
				}
			}
		} catch (\Exception $e) {}
		return $url;
	}

	/////

	private function _generateEmbeddableTitle(BasicEmbeddableInterface $embeddable) {
		$title = $embeddable->getTitle();
		if (!($embeddable instanceof ChildInterface)) {
			$translator = $this->get('translator');
			$typableUtils = $this->get(TypableUtils::NAME);
			$title = '['.$translator->trans('share.sticker.title_suffix.'.$typableUtils->getStrippedName($embeddable)).'] '.$title;
		}
		return $title;
	}

	public function generateSticker(BasicEmbeddableInterface $embeddable) {

		$pictureCount = 0;
		$pictures = array();
		if ($embeddable instanceof MultiPicturedInterface) {
			$pictures = array_merge($pictures, $embeddable->getPictures()->toArray());
			$pictureCount = count($pictures);
		} else if ($embeddable instanceof PicturedInterface) {
			$pictures[] = $embeddable->getMainPicture();
			$pictureCount = 1;
		}
		if ($embeddable instanceof BlockBodiedInterface) {
			foreach ($embeddable->getBodyBlocks() as $block) {
				if ($block instanceof Gallery) {
					if ($pictureCount < 5) {
						$pictures = array_merge($pictures, array_slice($block->getPictures()->toArray(), 0, 5 - $pictureCount));
					}
					$pictureCount += count($block->getPictures());
				}
			}
		}

		if ($pictureCount == 0) {
			return null;
		}

		// Instantiate Imagine
		$imagine = new Imagine();

		// Create sticker picture
		$sticker = new Picture();
		$sticker->setMasterPath(sha1(uniqid(mt_rand(), true)).'.png');

		$hasSubtitle = $embeddable instanceof ChildInterface && $embeddable->getParent() instanceof EmbeddableInterface;

		$gutter = 10;
		$padding = 4;
		$textGutter = 8;
		$textPadding = 15;
		$titleFontSize = 11;
		$subtitleFontSize = 10;
		$authorFontSize = 10;
		$moreFontSize = 25;
		$width = 598;
		$overlayHeight = $textPadding + $titleFontSize + $textGutter + $authorFontSize + $textPadding + ($hasSubtitle ? $textGutter + $subtitleFontSize : 0);
		$avatarSize = 60;

		$largeWidth = $width - 2 * $padding;
		$smallWidth = ($width - 8 * $padding - 3 * $gutter) / 4;
		$footerHeight = 15;

		$palette = new RGB();

		// Convert pictures to images
		$images = array();
		$index = 0;
		foreach ($pictures as $picture) {
			$image = $imagine->open($picture->getAbsolutePath());
			if ($index == 0) {
				$relativeResize = new RelativeResize('widen', $largeWidth);
				$relativeResize->apply($image);
			} else {
				$image = $image->thumbnail(new Box($smallWidth, $smallWidth), ImageInterface::THUMBNAIL_OUTBOUND);
			}
			$images[] = $image;
			$index++;
		}

		// Output
		$stickerSize = new Box(
			$width,
			$padding + $images[0]->getSize()->getHeight() + $overlayHeight + $padding + $gutter + ((count($images) > 1) ? $padding + ($images[1]->getSize()->getHeight() + $padding + $gutter) : 0) + $footerHeight
		);
		$stickerImage = $imagine->create($stickerSize, $palette->color('000', 0));
		$x = $y = $padding;
		$imageCount = count($images);
		$iMax = $imageCount > 1 ? 5 : 1;
		for ($i = 0; $i < $iMax; $i++) {
			if ($i == 0) {
				$imageWidth = $largeWidth;
				$imageHeight = $images[$i]->getSize()->getHeight();
			} else {
				$imageWidth = $imageHeight = $smallWidth;
			}
			$stickerImage->draw()->polygon(
				array(
					new Point($x - $padding, $y - $padding),
					new Point($x + $imageWidth + $padding - 1, $y - $padding),
					new Point($x + $imageWidth + $padding - 1, $y + $imageHeight + ($i == 0 ? $overlayHeight : 0) + $padding - 1),
					new Point($x - $padding, $y + $imageHeight + ($i == 0 ? $overlayHeight : 0) + $padding - 1)
				),
				$palette->color('ddd', $i >= $imageCount ? 20 : 100),
				true,
				0
			);
			$stickerImage->draw()->polygon(
				array(
					new Point($x - $padding + 1, $y - $padding + 1),
					new Point($x + $imageWidth + $padding - 2, $y - $padding + 1),
					new Point($x + $imageWidth + $padding - 2, $y + $imageHeight + ($i == 0 ? $overlayHeight : 0) + $padding - 2),
					new Point($x - $padding + 1, $y + $imageHeight + ($i == 0 ? $overlayHeight : 0) + $padding - 2)
				),
				$palette->color('fff', $i >= $imageCount ? 10 : 100),
				true,
				0
			);
			if ($i < $imageCount) {
				$stickerImage->paste($images[$i], new Point($x, $y + ($i == 0 ? $overlayHeight : 0)));
			}
			if ($i == 4 && $pictureCount > 5) {
				$stickerImage->draw()->polygon(
					array(
						new Point($x, $y),
						new Point($x + $imageWidth - 1, $y),
						new Point($x + $imageWidth - 1, $y + $imageHeight + ($i == 0 ? $overlayHeight : 0) - 1),
						new Point($x, $y + $imageHeight + ($i == 0 ? $overlayHeight : 0) - 1)
					),
					$palette->color('000', 50),
					true,
					0
				);

				$moreFont = new Font(__DIR__.'/../Resources/private/fonts/OpenSans-Bold.ttf', $moreFontSize, $palette->color('fff'));
				$moreText = '+'.($pictureCount - $imageCount);
				$moreBox = $moreFont->box($moreText);
				$stickerImage->draw()->text($moreText, $moreFont, new Point($x + ($imageHeight - $moreBox->getWidth()) / 2, $y + ($imageHeight - $moreBox->getHeight()) / 2));

			}
			if ($i == 0) {

				$user = $embeddable->getUser();
				$titleFont = new Font(__DIR__.'/../Resources/private/fonts/OpenSans-Bold.ttf', $titleFontSize, $palette->color('fff'));
				$subtitleFont = new Font(__DIR__.'/../Resources/private/fonts/OpenSans-Regular.ttf', $subtitleFontSize, $palette->color('fff'));
				$authorFont = new Font(__DIR__.'/../Resources/private/fonts/OpenSans-Regular.ttf', $authorFontSize, $palette->color('fff', 70));
				$overlayY = $y;

				$avatar = $user->getAvatar();
				if (!is_null($avatar)) {
					$avatarImage = $imagine->open($avatar->getAbsolutePath());
					$avatarImage = $avatarImage->thumbnail(new Box($avatarSize, $avatarSize), ImageInterface::THUMBNAIL_OUTBOUND);
				} else {
					$avatarImage = null;
				}

				$stickerImage->draw()->polygon(
					array(
						new Point($x, $overlayY),
						new Point($x + $imageWidth - 1, $overlayY),
						new Point($x + $imageWidth - 1, $overlayY + $overlayHeight - 1),
						new Point($x, $overlayY + $overlayHeight - 1)
					),
					$palette->color('000'),
					true,
					0
				);
				$x += $textGutter;
				$yy = $overlayY + ($overlayHeight - $titleFontSize - $authorFontSize - $textGutter - ($hasSubtitle ? $subtitleFontSize + $textGutter : 0)) / 2;
				if (!is_null($avatarImage)) {
					$stickerImage->paste($avatarImage, new Point($x, $overlayY + $textGutter));
					$x += $avatarSize + $gutter;
				} else {
					$x += $gutter;
				}
				$stickerImage->draw()->text($this->_generateEmbeddableTitle($embeddable), $titleFont, new Point($x, $yy));
				$yy += $titleFontSize + $textGutter;
				if ($hasSubtitle) {
					$stickerImage->draw()->text($this->_generateEmbeddableTitle($embeddable->getParent()), $subtitleFont, new Point($x, $yy));
					$yy += $subtitleFontSize + $textGutter;
				}
				$stickerImage->draw()->text('par '.$user->getDisplayname(), $authorFont, new Point($x, $yy));

				$x = $padding;
				$y += $padding + $imageHeight + $overlayHeight + $padding + $gutter;
			} else {
				$x += $padding + $imageWidth + $padding + $gutter;
			}
		}

		$footerFont = new Font(__DIR__.'/../Resources/private/fonts/OpenSans-Regular.ttf', 10, $palette->color('000', 20));
		$stickerImage->draw()->text('www.lairdubois.fr', $footerFont, new Point(5, $stickerSize->getHeight() - $footerHeight + ($footerHeight - $footerFont->box('www.lairdubois.fr')->getHeight()) / 2));

		$licence = $embeddable instanceof LicensedInterface ? $embeddable->getLicense() : ($embeddable instanceof ChildInterface && $embeddable->getParent() instanceof LicensedInterface ? $embeddable->getParent()->getLicense() : null);
		if (!is_null($licence)) {
			$licenceBadge = $imagine->open(__DIR__.'/../Resources/public/ladb/images/cc/80x15/'.$licence->getStrippedName().'.png');
			$stickerImage->paste($licenceBadge, new Point($width - $licenceBadge->getSize()->getWidth(), $stickerSize->getHeight() - $footerHeight + ($footerHeight - $licenceBadge->getSize()->getHeight()) / 2));
		}

		// Save sticker image
		$stickerImage->save($sticker->getAbsoluteMasterPath(), array( 'format' => 'png', 'png_compression_level' => 9 ));

		// Add sticker to embeddable
		$embeddable->setSticker($sticker);

		return $sticker;
	}

	public function resetSticker(BasicEmbeddableInterface $embeddable) {
		$embeddable->setSticker(null);
	}

	/////

	public function processReferer(EmbeddableInterface $embeddable, Request $request) {
		if ($request->get('referer', '0') == '1') {

			// Ignore bots
			$userAgent = $request->headers->get('User-Agent');
			if (empty($userAgent) || preg_match('/bot|spider|crawler|curl|^$/i', $userAgent)) {
				return null;
			}

			$url = $request->headers->get('referer');
			if (!is_null($url) && strlen($url) > 0) {

				// Process URL
				$urlComponents = parse_url($url);
				if (isset($urlComponents['scheme'])) {
					$scheme = $urlComponents['scheme'];
				} else {
					return null;
				}
				if (isset($urlComponents['host'])) {
					$host = $urlComponents['host'];
				} else {
					return null;
				}
				if (isset($urlComponents['port'])) {
					$host .= ':'.$urlComponents['port'];
				}
				if (isset($urlComponents['path'])) {
					$path = $urlComponents['path'];
				} else {
					$path = '';
				}
				if (isset($urlComponents['query'])) {
					$query = $urlComponents['query'];
				} else {
					$query = '';
				}
				$baseUrl = $scheme.'://'.$host;
				$route = $path.$query;

				$om = $this->getDoctrine()->getManager();

				// Check referer
				$refererRepository = $om->getRepository(Referer::CLASS_NAME);
				$referer = $refererRepository->findOneByBaseUrl($baseUrl);
				if (is_null($referer)) {

					// Create a new referer
					$referer = new Referer();
					$referer->setTitle($this->_retrievePageTitle($baseUrl));
					$referer->setBaseUrl($baseUrl);

					$om->persist($referer);

				}

				// Process referral
				$referralRepository = $om->getRepository(Referral::CLASS_NAME);
				$referral = $referralRepository->findOneByEntityTypeAndEntityIdAndUrl($embeddable->getType(), $embeddable->getId(), $url);
				if (is_null($referral)) {

					// Create a new referral
					$referral = new Referral();
					$referral->setTitle($this->_retrievePageTitle($url, $referer->getRouteTitlePattern(), $referer->getRouteTitleReplacement()));
					$referral->setUrl($url);
					$referral->setEntityType($embeddable->getType());
					$referral->setEntityId($embeddable->getId());
					$referral->setReferer($referer);

					if (strlen($referer->getRoutePattern()) > 0 && preg_match($referer->getRoutePattern(), $route)) {
						$referral->setEnabled(true);
						$embeddable->addReferral($referral);
					}

				}
				$referral->incrementAccessCount();
				$referral->setDisplayRedirectionWarning($request->get('displayRedirectionWarning', '1') == '1');	// By default redirection warning is displayed

				$om->persist($referral);
				$om->flush();

				return $referral;
			}
		}

		return null;
	}

}