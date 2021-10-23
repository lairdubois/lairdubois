<?php

namespace App\Manager\Core;

use App\Manager\AbstractManager;
use App\Entity\Core\Picture;

class PictureManager extends AbstractManager {

	public function createEmpty($fileExtension = 'jpg', $persist = true) {

		$picture = new Picture();
		$this->_computeMasterPath($picture, $fileExtension);

		if ($persist) {
			$om = $this->getDoctrine()->getManager();
			$om->persist($picture);
		}

		return $picture;
	}

	public function createFromUrl($url, $persist = true) {

		// Extract file extension from URL
		$fileExtension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));

		// Create an empty picture
		$picture = $this->createEmpty($fileExtension, false);

		// Try to fix missing url protocol
		$url = preg_replace('~^//~i', '', $url);
		$url = parse_url($url, PHP_URL_SCHEME) === null ? 'http://'.$url : $url;

		// Copy file content
		try {
			if (copy($url, $picture->getAbsolutePath())) {
				if ($this->computeSizes($picture)) {
					if ($persist) {
						$om = $this->getDoctrine()->getManager();
						$om->persist($picture);
					}
					return $picture;
				} else {
					unlink($picture->getAbsolutePath());
				}
			}
		} catch (\Exception $e) {}

		return null;
	}

	public function duplicate(Picture $picture) {
		$om = $this->getDoctrine()->getManager();

		$fileExtension = strtolower(pathinfo($picture->getPath(), PATHINFO_EXTENSION));

		// Create the new picture
		$newPicture = $this->createEmpty($fileExtension, false);
		$newPicture->setUser($picture->getUser());
		$newPicture->setLegend($picture->getLegend());
		$newPicture->setSourceUrl($picture->getSourceUrl());
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

	////

	public function computeSizes(Picture $picture) {

		if ($this->_checkMimeType($picture->getAbsolutePath())) {

			list($width, $height) = getimagesize($picture->getAbsolutePath());
			$picture->setWidth($width);
			$picture->setHeight($height);
			$picture->setHeightRatio100($width > 0 ? $height / $width * 100 : 100);

			return true;
		}

		return false;
	}

	/////

	private function _computeMasterPath(Picture $picture, $fileExtension = 'jpg') {
		if ($fileExtension == 'jpeg') {
			$fileExtension = 'jpg';
		}
		$picture->setMasterPath(sha1(uniqid(mt_rand(), true)).'.'.$fileExtension);
	}

	private function _checkMimeType($path) {
		$mimeType = mime_content_type($path);
		if ($mimeType == 'image/jpeg' || $mimeType == 'image/png') {
			return $mimeType;
		}
		return false;
	}

}