<?php

namespace Ladb\CoreBundle\Utils;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Ladb\CoreBundle\Entity\Promotion\Graphic;

class GraphicUtils {

	const NAME = 'ladb_core.graphic_utils';

	protected $templating;
	protected $router;

	public function __construct($templating, Router $router) {
		$this->templating = $templating;
		$this->router = $router;
	}

	public function generateKinds(Graphic $graphic) {
		$kinds = array();
		foreach ($graphic->getResources() as $resource) {
			$kind = Graphic::KIND_UNKNOW;
			$fileExtension = $resource->getFileExtension();
			if (!is_null($fileExtension)) {
				$fileExtension = strtolower($fileExtension);

				// PDF
				if ($fileExtension == 'pdf') {
					$kind = Graphic::KIND_PDF;
				}

				// SVG
				if ($fileExtension == 'svg') {
					$kind = Graphic::KIND_SVG;
				}

			}
			if ($kind != Graphic::KIND_UNKNOW && !in_array($kind, $kinds)) {
				$kinds[] = $kind;
			}
		}
		$graphic->setKinds($kinds);
	}

	public function createZipArchive(Graphic $graphic) {
		$zipAbsolutePath = $this->getZipAbsolutePath($graphic);

		// Remove archive if it exists
		if (is_file($zipAbsolutePath)) {
			unlink($zipAbsolutePath);
		}

		// Create a new archive
		$zip = new \ZipArchive();
		if ($zip->open($zipAbsolutePath, \ZipArchive::CREATE)) {

			foreach ($graphic->getResources() as $resource) {
				$zip->addFile($resource->getAbsolutePath(), $resource->getFilename());
			}
			$zip->addFromString('LisezMoi.txt', $this->templating->render('LadbCoreBundle:Promotion/Graphic:readme.txt.twig', array( 'graphic' => $graphic )));
			$zip->close();
			$graphic->setZipArchiveSize(filesize($zipAbsolutePath));

			return true;
		} else {

			$graphic->setZipArchiveSize(0);

			return false;
		}
	}

	public function getZipAbsolutePath(Graphic $graphic) {
		$downloadAbsolutePath = __DIR__ . '/../../../../downloads/';
		return $downloadAbsolutePath.'graphic_'.$graphic->getId().'.zip';
	}

	public function deleteZipArchive(Graphic $graphic) {
		$zipAbsolutePath = $this->getZipAbsolutePath($graphic);
		try {
			unlink($zipAbsolutePath);
		} catch (\Exception $e) {
		}
	}

}