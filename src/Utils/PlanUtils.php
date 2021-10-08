<?php

namespace App\Utils;

use App\Entity\Core\Resource;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Entity\Wonder\Plan;

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

	public function processSketchup3DWarehouseUrl(Plan $plan) {
		$embedIdentifier = null;
		if (!is_null($plan->getSketchup3DWarehouseUrl())) {

			if (preg_match('~(?:http|https|)(?::\/\/|)(3dwarehouse\.sketchup\.com/(?:model\.html\?id=|model/)([\w\-]{36,}))[a-z0-9;:@#?&%=+\/\$_.-]*~i', $plan->getSketchup3DWarehouseUrl(), $match)) {
				$embedIdentifier = $match[2];
			}

		}
		$plan->setSketchup3DWarehouseEmbedIdentifier($embedIdentifier);
	}

	public function processA360Url(Plan $plan) {
		$embedIdentifier = null;
		if (!is_null($plan->getA360Url())) {

			if (preg_match('~(?:http|https|)(?::\/\/|)(a360\.co/)([a-z0-9]*)~i', $plan->getA360Url(), $match)) {
				$embedIdentifier = $match[2];
			}

		}
		$plan->setA360EmbedIdentifier($embedIdentifier);
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
			$zip->addFromString('LisezMoi.txt', $this->templating->render('Wonder/Plan/readme.txt.twig', array( 'plan' => $plan )));
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