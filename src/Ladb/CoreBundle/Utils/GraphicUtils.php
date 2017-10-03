<?php

namespace Ladb\CoreBundle\Utils;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Ladb\CoreBundle\Entity\Promotion\Graphic;

class GraphicUtils extends AbstractContainerAwareUtils {

	const NAME = 'ladb_core.graphic_utils';

	public function createZipArchive(Graphic $graphic) {
		$zipAbsolutePath = $this->getZipAbsolutePath($graphic);

		// Remove archive if it exists
		if (is_file($zipAbsolutePath)) {
			unlink($zipAbsolutePath);
		}

		// Create a new archive
		$zip = new \ZipArchive();
		if ($zip->open($zipAbsolutePath, \ZipArchive::CREATE)) {

			$zip->addFile($graphic->getResource()->getAbsolutePath(), $graphic->getResource()->getFilename());
			$zip->addFromString('LisezMoi.txt', $this->get('templating')->render('LadbCoreBundle:Promotion/Graphic:readme.txt.twig', array( 'graphic' => $graphic )));
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