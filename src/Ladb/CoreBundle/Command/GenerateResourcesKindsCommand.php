<?php

namespace Ladb\CoreBundle\Command;

use Ladb\CoreBundle\Entity\Core\Resource;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateResourcesKindsCommand extends ContainerAwareCommand {

	protected function configure() {
		$this
			->setName('ladb:generate:resourceskinds')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force updating')
			->setDescription('Generate resources kinds')
			->setHelp(<<<EOT
The <info>ladb:generate:resourceskinds</info> command generate resources kinds
EOT
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$forced = $input->getOption('force');
		$verbose = $input->getOption('verbose');

		$om = $this->getContainer()->get('doctrine')->getManager();

		// Retrieve resources

		$output->write('<info>Resetting resources...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'r' ))
			->from('LadbCoreBundle:Core\Resource', 'r')
		;

		try {
			$resources = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$resources = array();
		}

		if ($verbose) {
			$output->writeln('<comment> ['.count($resources).' resources]</comment>');
		}

		foreach ($resources as $resource) {

			$fileExtension = $resource->getFileExtension();

			// Extract kind
			$kind = Resource::KIND_UNKNOW;
			if (!is_null($fileExtension)) {

				// AutoCAD
				if ($fileExtension == 'dwf' || $fileExtension == 'dwg') {
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

				// fusion360
				if ($fileExtension == 'f3d') {
					$kind = Resource::KIND_FUSION360;
				}

			}
			$resource->setKind($kind);

			if ($verbose) {
				$output->writeln('<comment> ['.$resource->getPath().' kind='.$kind.']</comment>');
			}

		}

		if ($forced) {
			$om->flush();
		}

	}

}