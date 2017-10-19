<?php

namespace Ladb\CoreBundle\Utils;

use Ladb\CoreBundle\Entity\Core\Resource;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Ladb\CoreBundle\Entity\Wonder\Plan;

class PlanUtils {

	const NAME = 'ladb_core.plan_utils';

	protected $templating;
	protected $router;

	public function __construct($templating, Router $router) {
		$this->templating = $templating;
		$this->router = $router;
	}

	public function generateKinds(Plan $plan) {
		$kinds = array();
		foreach ($plan->getResources() as $resource) {
			$kind = $resource->getKind();
			if ($kind != Resource::KIND_UNKNOW && !in_array($kind, $kinds)) {
				$kinds[] = $kind;
			}
		}
		$plan->setKinds($kinds);
	}

	public function createZipArchive(Plan $plan) {
		$zipAbsolutePath = $this->getZipAbsolutePath($plan);

		// Remove archive if it exists
		if (is_file($zipAbsolutePath)) {
			unlink($zipAbsolutePath);
		}

		// Create a new archive
		$zip = new \ZipArchive();
		if ($zip->open($zipAbsolutePath, \ZipArchive::CREATE)) {

			foreach ($plan->getResources() as $resource) {
				$zip->addFile($resource->getAbsolutePath(), $resource->getFilename());
			}
			$zip->addFromString('LisezMoi.txt', $this->templating->render('LadbCoreBundle:Wonder/Plan:readme.txt.twig', array( 'plan' => $plan )));
			$zip->close();
			$plan->setZipArchiveSize(filesize($zipAbsolutePath));

			return true;
		} else {

			$plan->setZipArchiveSize(0);

			return false;
		}
	}

	public function getZipAbsolutePath(Plan $plan) {
		$downloadAbsolutePath = __DIR__ . '/../../../../downloads/';
		return $downloadAbsolutePath.'plan_'.$plan->getId().'.zip';
	}

	public function deleteZipArchive(Plan $plan) {
		$zipAbsolutePath = $this->getZipAbsolutePath($plan);
		try {
			unlink($zipAbsolutePath);
		} catch (\Exception $e) {
		}
	}

}