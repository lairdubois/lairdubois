<?php

namespace Ladb\CoreBundle\Handler;

use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Ladb\CoreBundle\Entity\Core\Picture;

require_once(__DIR__.'/../../../../vendor/blueimp/jquery-file-upload/server/php/UploadHandler.php');

class PictureUploadHandler extends \UploadHandler {

	const NAME = 'ladb_core.picture_upload_handler';

	private $om;
	private $tokenStorage;

	function __construct(ObjectManager $om, TokenStorage $tokenStorage) {
		$this->om = $om;
		$this->tokenStorage = $tokenStorage;
	}

	public function handle($postProcessor = null,
						   $acceptFileTypes = Picture::DEFAULT_ACCEPTED_FILE_TYPE,
						   $maxFileSize = Picture::DEFAULT_MAX_FILE_SIZE,
						   $imageMaxWidth = Picture::DEFAULT_IMAGE_MAX_WIDTH,
						   $imageMaxHeight = Picture::DEFAULT_IMAGE_MAX_HEIGHT) {
		parent::__construct(array(
			'script_url'                   => '',
			'upload_dir'                   => sys_get_temp_dir(),
			'upload_url'                   => '',
			'access_control_allow_methods' => array(
				'POST',
			),
			'accept_file_types'            => $acceptFileTypes,
			'max_file_size'                => $maxFileSize,
			'image_library'                => 1,    // imagick
			'image_file_types'             => '/\.(jpe?g|png)$/i',
			'image_versions' => array(
				'' => array(
					'auto_orient' => true,
					'max_width'   => $imageMaxWidth,
					'max_height'  => $imageMaxHeight,
				),
			),
			'post_processor' => $postProcessor,
		));
	}

	protected function handle_file_upload($uploaded_file, $name, $size, $type, $error, $index = null, $content_range = null) {
		$file = parent::handle_file_upload(
			$uploaded_file, $name, $size, $type, $error, $index, $content_range
		);
		if (empty($file->error)) {

			$user = $this->tokenStorage->getToken()->getUser();
			$fileAbsolutePath = $this->options['upload_dir'].$file->name;
			$fileExtension = strtolower(pathinfo($file->name, PATHINFO_EXTENSION));
			$resourcePath = sha1(uniqid(mt_rand(), true)).'.'.$fileExtension;
			$resourceAbsolutePath = __DIR__.'/../../../../uploads/'.$resourcePath;

			// Rename uploaded file to generated uniqid and move it from tmp to uploads folder
			rename($fileAbsolutePath, $resourceAbsolutePath);

			// Post-processors
			switch ($this->options['post_processor']) {

				case Picture::POST_PROCESSOR_SQUARE:

					$imagick = new \Imagick($resourceAbsolutePath.'[0]');
					$imagick->setCompression(\Imagick::COMPRESSION_JPEG);					// Convert to JPG
					$imagick->setCompressionQuality(100);										// Set max quality
					$imagick->setBackgroundColor('#ffffff');									// Set background color to white
					$imagick->setImageAlphaChannel(11 /*/ \Imagick::ALPHACHANNEL_REMOVE */);
					$imagick->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);				// Merge layers
					$imagick->thumbnailImage(1024, 1024, true, true);			// Rescale to 1024x1024 fill
					$imagick->writeImage($resourceAbsolutePath);

					break;

			}

			// Compute image size
			list($width, $height) = $this->get_image_size($resourceAbsolutePath);

			// Create the new picture
			$picture = new Picture();
			$picture->setUser($user);
			$picture->setMasterPath($resourcePath);
			$picture->setWidth($width);
			$picture->setHeight($height);
			$picture->setHeightRatio100($width > 0 ? $height / $width * 100 : 100);

			$this->om->persist($picture);
			$this->om->flush();

			$file->id = $picture->getId();
			$file->name = $picture->getMasterPath();

		}
		return $file;
	}

	protected function validate($uploaded_file, $file, $error, $index) {
		if (parent::validate($uploaded_file, $file, $error, $index)) {

			list($img_width, $img_height) = $this->get_image_size($uploaded_file);

			if ($this->options['post_processor'] != Picture::POST_PROCESSOR_SQUARE) {	// Do not block 1/3 aspect ratio if post processor is SQUARE

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