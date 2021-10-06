<?php

namespace App\Command;

use DirkGroenen\Pinterest\Pinterest;
use App\Entity\Howto\Howto;
use App\Entity\Wonder\Creation;
use App\Model\StripableInterface;
use App\Utils\WebScreenshotUtils;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Facebook\FacebookSession;
use App\Entity\Core\Spotlight;
use App\Utils\MailerUtils;
use App\Utils\TypableUtils;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;

class CronWorkflowThumbnailCommand extends ContainerAwareCommand {

	protected function configure() {
		$this
			->setName('ladb:cron:workflow:thumbnails')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force updating')
			->setDescription('Update workflow thumbnails')
			->setHelp(<<<EOT
The <info>ladb:cron:workflow:thumbnails</info> update workflow thumbnails
EOT
			);
	}

	/////

	protected function execute(InputInterface $input, OutputInterface $output) {

		$forced = $input->getOption('force');
		$verbose = $input->getOption('verbose');

		$om = $this->getContainer()->get('doctrine')->getManager();
		$webScreenshotUtils = $this->getContainer()->get(WebScreenshotUtils::class);
		$router = $this->getContainer()->get('router');

		// Retrieve workflows

		if ($verbose) {
			$output->write('<info>Retriving workflows...</info>');
		}

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'w', 'mp' ))
			->from('App\Entity\Workflow\Workflow', 'w')
			->leftJoin('w.mainPicture', 'mp')
			->where('w.mainPicture IS NULL')
			->orWhere('w.updatedAt > mp.createdAt')
		;

		try {
			$workflows = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$workflows = array();
		}

		if ($verbose) {
			$output->writeln('<comment> ['.count($workflows).' workflows]</comment>');
		}

		foreach ($workflows as $workflow) {

			$url = $router->generate('core_workflow_internal_diagram', array( 'id' => $workflow->getId() ), UrlGeneratorInterface::ABSOLUTE_URL);

			if ($verbose) {
				$output->write('<info> Capturing ['.$url.'] ...</info>');
			}

			$mainPicture = $webScreenshotUtils->captureToPicture($url, 600, 600, 600, 600, 3);
			$workflow->setMainPicture($mainPicture);

			if ($verbose) {
				if ($forced) {
					$output->writeln('<fg=cyan> [OK]</fg=cyan>');
				} else {
					$output->writeln('<fg=cyan> [FAKE]</fg=cyan>');
				}
			}

		}

		if ($forced) {
			$om->flush();
		}

	}
}