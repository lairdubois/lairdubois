<?php

namespace Ladb\CoreBundle\Command;

use DirkGroenen\Pinterest\Pinterest;
use Ladb\CoreBundle\Entity\Howto\Howto;
use Ladb\CoreBundle\Entity\Wonder\Creation;
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
		$webScreenshotUtils = $this->getContainer()->get(WebScreenshotUtils::NAME);
		$router = $this->getContainer()->get('router');

		// Retrieve workflows

		$output->write('<info>Retriving workflows...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'w', 'mp' ))
			->from('LadbCoreBundle:Workflow\Workflow', 'w')
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

			$url = $router->generate('core_workflow_diagram', array( 'id' => $workflow->getId() ), UrlGeneratorInterface::ABSOLUTE_URL);

			if ($verbose) {
				$output->write('<info> Capturing ['.$url.'] ...</info>');
			}

			$mainPicture = $webScreenshotUtils->captureToPicture($url, 600, 600, 600, 600);
			$workflow->setMainPicture($mainPicture);

			if ($verbose) {
				$output->writeln('<fg=cyan> [OK]</fg=cyan>');
			}

		}

		if ($forced) {
			$om->flush();
		}

	}
}