<?php

namespace Ladb\CoreBundle\Utils;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\Palette\RGB;
use Imagine\Image\Point;
use Ladb\CoreBundle\Entity\Knowledge\Wood;
use Ladb\CoreBundle\Entity\Knowledge\Wood\Texture;
use Ladb\CoreBundle\Entity\Knowledge\Value\Picture;

class TextureUtils extends AbstractContainerAwareUtils {

	const NAME = 'ladb_core.texture_utils';

	/////

	public function createTexture(Wood $wood, Picture $value, $flush = true) {
		$om = $this->getDoctrine()->getManager();

		if ($value->getId() > 0) {		// 0 = new value = no test

			// Check vote score
			if ($value->getVoteScore() < 0) {
				return;
			}

			// Check if texture already exists
			$textureRepository = $om->getRepository(Texture::CLASS_NAME);
			if ($textureRepository->existsByWoodAndValue($wood, $value)) {
				return;
			}

		}

		// Create a new texture
		$texture = new Texture();
		$texture->setWood($wood);
		$texture->setValue($value);

		// Generate pictures
		$this->generatePictures($texture);

		$wood->addTexture($texture);
		$wood->incrementTextureCount(1);

		if ($flush) {
			$om->flush();
		}
	}

	public function updateTexture(Wood $wood, Picture $value, $flush = true) {
		$om = $this->getDoctrine()->getManager();

		$textureRepository = $om->getRepository(Texture::CLASS_NAME);
		$texture = $textureRepository->findOneByWoodAndValue($wood, $value);
		if (!is_null($texture)) {

			// Generate pictures
			$this->generatePictures($texture);

			// Delete the Zip archive
			$this->deleteZipArchive($texture);

			if ($flush) {
				$om->flush();
			}

		}
	}

	public function deleteTexture(Wood $wood, Picture $value, $flush = true) {
		$om = $this->getDoctrine()->getManager();

		$textureRepository = $om->getRepository(Texture::CLASS_NAME);
		$texture = $textureRepository->findOneByWoodAndValue($wood, $value);
		if (!is_null($texture)) {

			$this->deleteZipArchive($texture);

			$om->remove($texture); // Workaround to foreign key integrity

			$wood->removeTexture($texture);
			$wood->incrementTextureCount(-1);

			if ($flush) {
				$om->flush();
			}

		}
	}

	/////

	public function generatePictures(Texture $texture) {
		$valuePicture = $texture->getValue()->getData();
		$width = $valuePicture->getWidth();
		$height = $valuePicture->getHeight();

		// Instantiate Imagine
		$imagine = new Imagine();
		$palette = new RGB();

		$valueImage = $imagine->open($valuePicture->getAbsolutePath());


		// Single picture /////

		// Create single picture
		$singlePicture = new \Ladb\CoreBundle\Entity\Picture();
		$singlePicture->setMasterPath(sha1(uniqid(mt_rand(), true)).'.jpg');

		$cropSize = 10;
		$singleWidth = $width - $cropSize * 2;
		$singleHeight = $height - $cropSize * 2;

		// Create single image
		$singleImage = $valueImage->crop(new Point($cropSize, $cropSize), new Box($singleWidth, $singleHeight));

		// Save single image
		$singleImage->save($singlePicture->getAbsoluteMasterPath(), array( 'format' => 'jpg' ));

		$singlePicture->setWidth($singleWidth);
		$singlePicture->setHeight($singleHeight);
		$singlePicture->setHeightRatio100($singleWidth > 0 ? $singleHeight / $singleWidth * 100 : 100);

		// Save single picture into texture
		$texture->setSinglePicture($singlePicture);


		// Mosaic picture /////

		$mosaicWidth = $singleWidth * 2;
		$mosaicHeight = $singleHeight * 2;

		// Create mosaic picture
		$mosaicPicture = new \Ladb\CoreBundle\Entity\Picture();
		$mosaicPicture->setMasterPath(sha1(uniqid(mt_rand(), true)).'.jpg');

		// Create mosaic image
		$mosaicImage = $imagine->create(new Box($mosaicWidth, $mosaicHeight), $palette->color('000', 0));

		// Paste the original image
		$mosaicImage->paste($singleImage, new Point(0, 0));
		$mosaicImage->paste($singleImage->copy()->flipHorizontally(), new Point($singleWidth, 0));
		$mosaicImage->paste($singleImage->copy()->flipVertically(), new Point(0, $singleHeight));
		$mosaicImage->paste($singleImage->copy()->flipVertically()->flipHorizontally(), new Point($singleWidth, $singleHeight));

		// Save mosaic image
		$mosaicImage->save($mosaicPicture->getAbsoluteMasterPath(), array( 'format' => 'jpg' ));

		$mosaicPicture->setWidth($mosaicWidth);
		$mosaicPicture->setHeight($mosaicHeight);
		$mosaicPicture->setHeightRatio100($mosaicWidth > 0 ? $mosaicHeight / $mosaicWidth * 100 : 100);

		// Save mosaic picture into texture
		$texture->setMosaicPicture($mosaicPicture);
	}
	
	/////

	public function getBaseFilename($texture) {
		$translator = $this->get('translator');
		return $texture->getWood()->getSlug().'_'.\Gedmo\Sluggable\Util\Urlizer::urlize($translator->trans('knowledge.wood.field.'.$texture->getValue()->getParentEntityField()), '_').'_'.$texture->getValue()->getId();
	}

	public function getZipAbsolutePath(Texture $texture) {
		$downloadAbsolutePath = __DIR__ . '/../../../../downloads/';
		return $downloadAbsolutePath.'texture_'.$texture->getId().'.zip';
	}

	public function createZipArchive(Texture $texture) {
		$zipAbsolutePath = $this->getZipAbsolutePath($texture);

		// Remove archive if it exists
		if (is_file($zipAbsolutePath)) {
			unlink($zipAbsolutePath);
		}

		// Create a new archive
		$zip = new \ZipArchive();
		if ($zip->open($zipAbsolutePath, \ZipArchive::CREATE)) {

			$baseFilename = $this->getBaseFilename($texture);
			$singleFilename = $baseFilename.'.jpg';
			$mosaicFilename = $baseFilename.'_mosaique.jpg';

			$zip->addFile($texture->getSinglePicture()->getAbsolutePath(), $singleFilename);
			$zip->addFile($texture->getMosaicPicture()->getAbsolutePath(), $mosaicFilename);
			$zip->addFromString('LisezMoi.txt', $this->get('templating')->render('LadbCoreBundle:Wood:texture-readme.txt.twig', array( 'texture' => $texture, 'files' => array( $singleFilename, $mosaicFilename ) )));
			$zip->close();

			return true;
		}

		return false;
	}

	public function deleteZipArchive(Texture $texture) {
		$zipAbsolutePath = $this->getZipAbsolutePath($texture);
		try {
			unlink($zipAbsolutePath);
		} catch (\Exception $e) {
		}
	}
	

}