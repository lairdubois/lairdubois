<?php

namespace App\Utils;

use App\Manager\Core\PictureManager;
use App\Model\AuthoredInterface;
use App\Model\StripableInterface;
use Imagine\Filter\Advanced\RelativeResize;
use Imagine\Gd\Font;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\Point;
use Imagine\Image\Palette\RGB;
use App\Model\ChildInterface;
use App\Model\LicensedInterface;
use App\Entity\Core\Picture;
use App\Model\MultiPicturedInterface;
use App\Model\PicturedInterface;

class StripableUtils extends AbstractContainerAwareUtils {

	const NAME = 'ladb_core.stripable_utils';

	/////

	public function generateStrip(StripableInterface $stripable) {

		$pictureCount = 0;
		$pictures = array();
		if ($stripable instanceof MultiPicturedInterface) {
			$pictures = array_merge($pictures, $stripable->getPictures()->toArray());
			$pictureCount = count($pictures);
		} else if ($stripable instanceof PicturedInterface) {
			$pictures[] = $stripable->getMainPicture();
			$pictureCount = 1;
		}

		if ($pictureCount == 0) {
			return null;
		}

		// Instantiate Imagine
		$imagine = new Imagine();

		// Create strip picture
		$pictureManager = $this->get(PictureManager::class);
		$strip = $pictureManager->createEmpty();

		$gutter = 5;
		$width = 564;
		$footerHeight = 15 + $gutter * 2;
		$footerPadding = 10;

		$imagesHeight = 0;

		$palette = new RGB();

		// Convert pictures to images
		$images = array();
		foreach ($pictures as $picture) {

			$image = $imagine->open($picture->getAbsolutePath());

			// Resize image to width
			$relativeResize = new RelativeResize('widen', $width);
			$relativeResize->apply($image);

			// Sum height
			$imagesHeight += $image->getSize()->getHeight();

			$images[] = $image;

		}

		// Output
		$imageCount = count($images);
		$stripSize = new Box(
			$width,
			$imagesHeight + ($imageCount - 1) * $gutter + $footerHeight
		);
		$stripImage = $imagine->create($stripSize, $palette->color('fff', 100));
		$x = $y = 0;
		for ($i = 0; $i < $imageCount; $i++) {
			$stripImage->paste($images[$i], new Point($x, $y));
			$y += $images[$i]->getSize()->getHeight() + $gutter;
		}

		$x = $footerPadding;
		if ($stripable instanceof AuthoredInterface) {
			$footerUserFont = new Font(__DIR__.'/../Resources/private/fonts/OpenSans-Bold.ttf', 10, $palette->color('333', 100));
			$footerUserText = '@'.$stripable->getUser()->getDisplayName();
			$stripImage->draw()->text($footerUserText, $footerUserFont, new Point($x, $stripSize->getHeight() - $footerHeight + ($footerHeight - $footerUserFont->box($footerUserText)->getHeight()) / 2));
			$x += $footerUserFont->box($footerUserText)->getWidth();
		}
		$footerUrlFont = new Font(__DIR__.'/../Resources/private/fonts/OpenSans-Regular.ttf', 10, $palette->color('555', 100));
		$footerUrlText = ($stripable instanceof AuthoredInterface ? ' | ' : '').'www.lairdubois.fr';
		$stripImage->draw()->text($footerUrlText, $footerUrlFont, new Point($x, $stripSize->getHeight() - $footerHeight + ($footerHeight - $footerUrlFont->box($footerUrlText)->getHeight()) / 2));

		$licence = $stripable instanceof LicensedInterface ? $stripable->getLicense() : ($stripable instanceof ChildInterface && $stripable->getParentEntity() instanceof LicensedInterface ? $stripable->getParentEntity()->getLicense() : null);
		if (!is_null($licence)) {
			$licenceBadge = $imagine->open(__DIR__.'/../Resources/public/ladb/images/cc/80x15/'.$licence->getStrippedName().'.png');
			$stripImage->paste($licenceBadge, new Point($width - $licenceBadge->getSize()->getWidth() - $footerPadding, $stripSize->getHeight() - $footerHeight + ($footerHeight - $licenceBadge->getSize()->getHeight()) / 2));
		}

		// Save strip image
		$stripImage->save($strip->getAbsoluteMasterPath(), array( 'format' => 'jpg' ));

		// Add strip to embeddable
		$stripable->setStrip($strip);

		return $strip;
	}

	public function resetStrip(StripableInterface $stripable) {
		$stripable->setStrip(null);
	}

}