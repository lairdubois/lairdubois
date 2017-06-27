<?php

namespace Ladb\CoreBundle\Handler;

use Doctrine\Common\Persistence\ObjectManager;
use Ladb\CoreBundle\Entity\Core\Resource;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

require_once('../vendor/blueimp/jquery-file-upload/server/php/UploadHandler.php');

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
			'upload_dir'                   => __DIR__ . '/../../../../uploads/',
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
			$fileAbsolutePath = $this->options['upload_dir'].$name;
			$fileExtension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
			$resourcePath = sha1(uniqid(mt_rand(), true)).'.'.$fileExtension;
			$resourceAbsolutePath = $this->options['upload_dir'].$resourcePath;

			// Rename uploaded file to generated uniqid
			rename($fileAbsolutePath, $resourceAbsolutePath);

			// Create the new resource
			$resource = new Resource();
			$resource->setUser($user);
			$resource->setPath($resourcePath);
			$resource->setFileName($name);
			$resource->setFileExtension($fileExtension);
			$resource->setFileSize(filesize($resourceAbsolutePath));

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