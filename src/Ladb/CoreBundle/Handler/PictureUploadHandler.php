<?php

namespace Ladb\CoreBundle\Handler;

use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Ladb\CoreBundle\Entity\Core\Picture;

require_once('../vendor/blueimp/jquery-file-upload/server/php/UploadHandler.php');

class PictureUploadHandler extends \UploadHandler {

	const NAME = 'ladb_core.picture_upload_handler';

	private $om;
	private $tokenStorage;

	function __construct(ObjectManager $om, TokenStorage $tokenStorage) {
		$this->om = $om;
		$this->tokenStorage = $tokenStorage;
	}

	public function handle($acceptFileTypes = Picture::DEFAULT_ACCEPTED_FILE_TYPE,
						   $maxFileSize = Picture::DEFAULT_MAX_FILE_SIZE,
						   $imageMaxWidth = Picture::DEFAULT_IMAGE_MAX_WIDTH,
						   $imageMaxHeight = Picture::DEFAULT_IMAGE_MAX_HEIGHT) {
		parent::__construct(array(
			'script_url'                   => '',
			'upload_dir'                   => __DIR__.'/../../../../uploads/',
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
		));
	}

	protected function handle_file_upload($uploaded_file, $name, $size, $type, $error, $index = null, $content_range = null) {
		$file = parent::handle_file_upload(
			$uploaded_file, $name, $size, $type, $error, $index, $content_range
		);
		if (empty($file->error)) {

			$user = $this->tokenStorage->getToken()->getUser();
			$fileAbsolutePath = $this->options['upload_dir'].$name;
			$fileExtension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
			$resourcePath = sha1(uniqid(mt_rand(), true)).'.'.$fileExtension;
			$resourceAbsolutePath = $this->options['upload_dir'].$resourcePath;
			list($width, $height) = $this->get_image_size($fileAbsolutePath);

			// Rename uploaded file to generated uniqid
			rename($fileAbsolutePath, $resourceAbsolutePath);

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

}