<?php

namespace App\Handler;

use App\Entity\Core\User;
use App\Manager\Core\PictureManager;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use App\Entity\Core\Picture;
use Symfony\Component\Security\Core\Security;

class PictureUploadHandler extends BaseUploadHandler {

	const NAME = 'ladb_core.picture_upload_handler';

	private $om;
	private $pictureManager;

	function __construct(ManagerRegistry $om, PictureManager $pictureManager) {
		$this->om = $om->getManager();
		$this->pictureManager = $pictureManager;
	}

	public function handle($quality = Picture::QUALITY_SD, $postProcessor = Picture::POST_PROCESSOR_NONE, User $owner = null) {
		parent::__construct(array(
			'script_url'                   => '',
			'upload_dir'                   => sys_get_temp_dir().DIRECTORY_SEPARATOR,
			'upload_url'                   => '',
			'access_control_allow_methods' => array(
				'POST',
			),
			'accept_file_types'            => Picture::ACCEPTED_FILE_TYPE,
			'max_file_size'                => Picture::MAX_FILE_SIZE,
			'image_library'                => 1,    // imagick
			'image_file_types'             => Picture::ACCEPTED_FILE_TYPE,
			'image_versions' => array(
				'' => array(
					'auto_orient'  => true,
					'max_width'    => Picture::VERSION_IMAGE_SIZE,
					'max_height'   => Picture::VERSION_IMAGE_SIZE,
					'jpeg_quality' => 100,
				),
			),
			'quality' => $quality,
			'post_processor' => $postProcessor,
			'owner' => $owner,
		));
	}

	protected function handle_file_upload($uploaded_file, $name, $size, $type, $error, $index = null, $content_range = null) {
		$file = parent::handle_file_upload(
			$uploaded_file, $name, $size, $type, $error, $index, $content_range
		);
		if (empty($file->error)) {

			$fileAbsolutePath = $this->options['upload_dir'].$file->name;
			$fileExtension = strtolower(pathinfo($file->name, PATHINFO_EXTENSION));

			// Create en empty picture
			$picture = $this->pictureManager->createEmpty($fileExtension);

			// Rename uploaded file to generated uniqid and move it from tmp to uploads folder
			rename($fileAbsolutePath, $picture->getAbsolutePath());

			// Post-processors
			switch ($this->options['post_processor']) {

				case Picture::POST_PROCESSOR_SQUARE:

					$imagick = new \Imagick($picture->getAbsolutePath().'[0]');
					$imagick->setCompression(\Imagick::COMPRESSION_JPEG);					// Convert to JPG
					$imagick->setCompressionQuality(100);										// Set max quality
					$imagick->setBackgroundColor('#ffffff');									// Set background color to white
					$imagick->setImageAlphaChannel(11 /*/ \Imagick::ALPHACHANNEL_REMOVE */);
					$imagick->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);				// Merge layers
					$imagick->thumbnailImage(1024, 1024, true, true);			// Rescale to 1024x1024 fill
					$imagick->writeImage($picture->getAbsolutePath());

					break;

			}

			// Compute image size
			$this->pictureManager->computeSizes($picture);

			// Set "owner" as picture's user
			$picture->setUser($this->options['owner']);

			$this->om->flush();

			$file->id = $picture->getId();
			$file->name = $picture->getMasterPath();

		}
		return $file;
	}

	protected function validate($uploaded_file, $file, $error, $index, $content_range) {
		if (parent::validate($uploaded_file, $file, $error, $index, $content_range)) {

			list($img_width, $img_height) = $this->get_image_size($uploaded_file);
			$minSize = $this->options['quality'] == Picture::QUALITY_HD ? Picture::QUALITY_HD_MIN_SIZE : ($this->options['quality'] == Picture::QUALITY_SD ? Picture::QUALITY_SD_MIN_SIZE : Picture::QUALITY_LD_MIN_SIZE);

			// Check image size
			if ($img_width < $minSize) {
				$file->error = "L'image est trop petite. La largeur de l'image doit être suppérieure à ".$minSize." pixels.";
				return false;
			}
			if ($img_height < $minSize) {
				$file->error = "L'image est trop petite. La hauteur de l'image doit être suppérieure à ".$minSize." pixels.";
				return false;
			}

			if ($this->options['post_processor'] != Picture::POST_PROCESSOR_SQUARE) {	// Do not block 1/4 aspect ratio if post processor is SQUARE

				// Check image ratio
				$ratio = $img_width / $img_height;
				if ($ratio > 4 || $ratio < 0.25) {
					$file->error = "Les proportions de l'image sont incorrectes.<br>La plus petite dimension de l'image ne doit pas être inférieure au <strong>1/4</strong> de la plus grande.";
					return false;
				}

			}

			return true;
		}
		return false;
	}

}