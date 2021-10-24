<?php

namespace App\Command;

use App\Entity\Opencutlist\Access;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CronOpencutlistAccessAnalyzeCommand extends AbstractContainerAwareCommand {

	protected function configure() {
		$this
			->setName('ladb:cron:opencutlist:access:analyze')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force updating')
			->setDescription('Analyze OpenCutList access')
			->setHelp(<<<EOT
The <info>ladb:cron:opencutlist:access:analyze</info> Analyze OpenCutList access
EOT
			);
	}

	/////

	protected function execute(InputInterface $input, OutputInterface $output) {

		$forced = $input->getOption('force');
		$verbose = $input->getOption('verbose');

		$om = $this->getDoctrine()->getManager();

		if ($verbose) {
			$output->write('<info>Retriving fresh access...</info>');
		}

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'a' ))
			->from(Access::CLASS_NAME, 'a')
			->where('a.analyzed = false')
			->setMaxResults(40)		// ip-api.com endpoint is limited to 45 requests per minute from an IP address.
		;

		try {
			$accesses = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$accesses = array();
		}

		if ($verbose) {
			$output->writeln('<comment> ['.count($accesses).' accesses to analyze]</comment>');
		}

		foreach ($accesses as $access) {

			// Extract OS

			$re = '/^Mozilla\/5.0 \((Windows|Macintosh)/';
			preg_match_all($re, $access->getClientUserAgent(), $matches, PREG_SET_ORDER, 0);

			if (!empty($matches)) {
				$access->setClientOS($matches[0][1] == 'Windows' ? Access::OS_WIN : ($matches[0][1] == 'Macintosh' ? Access::OS_MAC : Access::OS_UNKNOW));
			}

			// Sketchp Family and Sketchup Version

			$re = '/\sSketchUp\s*(Pro|Make|)\/(\d*.\d)\s/';
			preg_match_all($re, $access->getClientUserAgent(), $matches, PREG_SET_ORDER, 0);

			if (!empty($matches)) {
				$access->setClientSketchupFamily($matches[0][1] == 'Pro' ? Access::SKETCHUP_FAMILY_PRO : ($matches[0][1] == 'Make' ? Access::SKETCHUP_FAMILY_MAKE : Access::SKETCHUP_FAMILY_UNKNOW));
				$access->setClientSketchupVersion($matches[0][2]);
			}

			// Extract Location, Latitude and Longitude with ip-api.com web service

			$hash = json_decode(file_get_contents('http://ip-api.com/json/'.$access->getClientIp4().'?lang=fr&fields=status,message,continentCode,country,countryCode,city,lat,lon'), true);
			if ($hash && isset($hash['status']) && $hash['status'] == 'success') {

				$continentCode = $hash['continentCode'];
				$countryCode = $hash['countryCode'];
				$country = $hash['country'];
				$city = $hash['city'];
				$latitude = $hash['lat'];
				$longitude = $hash['lon'];

				$access->setContinentCode($continentCode);
				$access->setCountryCode($countryCode);
				$access->setLocation($city.', '.$country);
				$access->setLatitude($latitude);
				$access->setLongitude($longitude);

			}

			// Flag download as analyzed
			$access->setAnalyzed(true);

		}

		if ($forced) {
			$om->flush();
		}

        return Command::SUCCESS;
	}
}