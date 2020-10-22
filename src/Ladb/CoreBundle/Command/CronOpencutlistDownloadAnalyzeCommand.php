<?php

namespace Ladb\CoreBundle\Command;

use DirkGroenen\Pinterest\Pinterest;
use Ladb\CoreBundle\Entity\Howto\Howto;
use Ladb\CoreBundle\Entity\Offer\Offer;
use Ladb\CoreBundle\Entity\Opencutlist\Download;
use Ladb\CoreBundle\Entity\Wonder\Creation;
use Ladb\CoreBundle\Manager\Offer\OfferManager;
use Ladb\CoreBundle\Model\StripableInterface;
use Ladb\CoreBundle\Utils\WebScreenshotUtils;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Facebook\FacebookSession;
use Ladb\CoreBundle\Entity\Core\Spotlight;
use Ladb\CoreBundle\Utils\MailerUtils;
use Ladb\CoreBundle\Utils\TypableUtils;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;

class CronOpencutlistDownloadAnalyzeCommand extends ContainerAwareCommand {

	protected function configure() {
		$this
			->setName('ladb:cron:opencutlist:download:analyze')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force updating')
			->setDescription('Analyze OpenCutList downloads')
			->setHelp(<<<EOT
The <info>ladb:cron:opencutlist:download:analyze</info> Analyze OpenCutList downloads
EOT
			);
	}

	/////

	protected function execute(InputInterface $input, OutputInterface $output) {

		$forced = $input->getOption('force');
		$verbose = $input->getOption('verbose');

		$om = $this->getContainer()->get('doctrine')->getManager();

		if ($verbose) {
			$output->write('<info>Retriving fresh downloads...</info>');
		}

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'd' ))
			->from('LadbCoreBundle:Opencutlist\Download', 'd')
			->where('d.analyzed = false')
		;

		try {
			$downloads = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$downloads = array();
		}

		if ($verbose) {
			$output->writeln('<comment> ['.count($downloads).' downloads to analyze]</comment>');
		}

		foreach ($downloads as $download) {

			// Extract OS, Sketchp Family and Sketchup Version

			$re = '/\sSketchUp\s*(Pro|Make|)\/(\d*.\d) \((PC|Mac)\)\s/';
			preg_match_all($re, $download->getClientUserAgent(), $matches, PREG_SET_ORDER, 0);

			if (!empty($matches)) {
				$download->setClientSketchupFamily($matches[0][1] == 'Pro' ? Download::SKETCHUP_FAMILY_PRO : $matches[0][1] == 'Make' ? Download::SKETCHUP_FAMILY_MAKE : Download::SKETCHUP_FAMILY_UNKNOW);
				$download->setClientSketchupVersion($matches[0][2]);
				$download->setClientOS($matches[0][3] == 'PC' ? Download::OS_WIN : $matches[0][3] == 'Mac' ? Download::OS_MAC : Download::OS_UNKNOW);
			}

			// Extract Location, Latitude and Longitude with ip-api.com web service

			$hash = json_decode(file_get_contents('http://ip-api.com/json/'.$download->getClientIp4().'?lang=fr'), true);
			if ($hash && isset($hash['status']) && $hash['status'] == 'success') {

				$country = $hash['country'];
				$city = $hash['city'];
				$latitude = $hash['lat'];
				$longitude = $hash['lon'];

				$download->setLocation($city.', '.$country);
				$download->setLatitude($latitude);
				$download->setLongitude($longitude);

			}

			// Flag download as analyzed
			$download->setAnalyzed(true);

		}

		if ($forced) {
			$om->flush();
		}

	}
}