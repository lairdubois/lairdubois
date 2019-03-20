<?php

namespace Ladb\CoreBundle\Handler;

use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Ladb\CoreBundle\Entity\Core\Picture;
use Ladb\CoreBundle\Entity\Core\Resource;

require_once(__DIR__.'/../../../../vendor/blueimp/jquery-file-upload/server/php/UploadHandler.php');

class ResourceUploadHandler extends \UploadHandler {

	const NAME = 'ladb_core.resource_upload_handler';

	private $om;
	private $tokenStorage;

	function __construct(ObjectManager $om, TokenStorage $tokenStorage) {
		$this->om = $om;
		$this->tokenStorage = $tokenStorage;
	}

	public function handle($acceptedFileTypes = Resource::DEFAULT_ACCEPTED_FILE_TYPE, $maxFileSize = Resource::DEFAULT_MAX_FILE_SIZE) {
		parent::__construct(array(
			'script_url'                   => '',
			'upload_dir'                   => sys_get_temp_dir(),
			'upload_url'                   => '',
			'access_control_allow_methods' => array(
				'POST',
			),
			'accept_file_types'            => $acceptedFileTypes,
			'max_file_size'                => $maxFileSize,
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

			// Create the new resource
			$resource = new Resource();
			$resource->setUser($user);
			$resource->setPath($resourcePath);
			$resource->setFileName($name);
			$resource->setFileExtension($fileExtension);
			$resource->setFileSize(filesize($resourceAbsolutePath));

			// Extract kind
			$kind = Resource::KIND_UNKNOW;
			if (!is_null($fileExtension)) {

				// AutoCAD
				if ($fileExtension == 'dwf' || $fileExtension == 'dwg' || $fileExtension == 'dxf') {
					$kind = Resource::KIND_AUTOCAD;
				}

				// Sketchup
				if ($fileExtension == 'skp') {
					$kind = Resource::KIND_SKETCHUP;
				}

				// PDF
				if ($fileExtension == 'pdf') {
					$kind = Resource::KIND_PDF;
				}

				// GeoGebra
				if ($fileExtension == 'ggb') {
					$kind = Resource::KIND_GEOGEBRA;
				}

				// SVG
				if ($fileExtension == 'svg') {
					$kind = Resource::KIND_SVG;
				}

				// FreeCAD
				if ($fileExtension == 'fcstd') {
					$kind = Resource::KIND_FREECAD;
				}

				// STL
				if ($fileExtension == 'stl') {
					$kind = Resource::KIND_STL;
				}

				// 123 Design
				if ($fileExtension == '123dx') {
					$kind = Resource::KIND_123DESIGN;
				}

				// libreOffice
				if ($fileExtension == 'xlsx' || $fileExtension == 'xlsm' || $fileExtension == 'ods') {
					$kind = Resource::KIND_LIBREOFFICE;
				}

				// Fusion360
				if ($fileExtension == 'f3d') {
					$kind = Resource::KIND_FUSION360;
				}

				// Collada
				if ($fileExtension == 'dae') {
					$kind = Resource::KIND_COLLADA;
				}

			}
			$resource->setKind($kind);

			if ($kind == Resource::KIND_PDF || $kind == Resource::KIND_SVG) {

				// ImageMagick Workaround : https://stackoverflow.com/questions/10455985/issue-with-imagick-and-also-with-phmagick-postscript-delegate-failed-no-such
				putenv( 'PATH=' . getenv('PATH') . ':/usr/local/bin' );

				// Create thumbnail
				$thumbnail = new Picture();
				$thumbnail->setMasterPath(sha1(uniqid(mt_rand(), true)).'.jpg');

				$imagick = new \Imagick($resource->getAbsolutePath().'[0]');
				$imagick->setCompression(\Imagick::COMPRESSION_JPEG);
				$imagick->setCompressionQuality(100);
				$imagick->setBackgroundColor('#ffffff');
				$imagick->setImageAlphaChannel(11 /*/ \Imagick::ALPHACHANNEL_REMOVE */);
				$imagick->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
				$imagick->thumbnailImage(1024, 1024, true, false);
				$imagick->writeImage($thumbnail->getAbsoluteMasterPath());

				list($width, $height) = $this->get_image_size($thumbnail->getAbsoluteMasterPath());
				$thumbnail->setWidth($width);
				$thumbnail->setHeight($height);
				$thumbnail->setHeightRatio100($width > 0 ? $height / $width * 100 : 100);

				$resource->setThumbnail($thumbnail);

			}

			$this->om->persist($resource);
			$this->om->flush();

			$file->id = $resource->getId();

			$size = array( 'o', 'Ko', 'Mo' );
			$factor = floor((strlen($resource->getFileSize()) - 1) / 3);
			$file->size = sprintf("%.0f", $resource->getFileSize() / pow(1024, $factor)).' '.@$size[$factor];

		}
		return $file;
	}

}