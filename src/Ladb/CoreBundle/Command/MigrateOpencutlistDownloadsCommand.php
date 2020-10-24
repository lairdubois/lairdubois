<?php

namespace Ladb\CoreBundle\Command;

use Ladb\CoreBundle\Entity\Knowledge\Book;
use Ladb\CoreBundle\Entity\Knowledge\Value\BookIdentity;
use Ladb\CoreBundle\Entity\Knowledge\Value\Integer;
use Ladb\CoreBundle\Entity\Opencutlist\Access;
use Ladb\CoreBundle\Entity\Opencutlist\Download;
use Ladb\CoreBundle\Utils\CommentableUtils;
use Ladb\CoreBundle\Utils\KnowledgeUtils;
use Ladb\CoreBundle\Utils\PropertyUtils;
use Ladb\CoreBundle\Utils\VotableUtils;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateOpencutlistDownloadsCommand extends ContainerAwareCommand {

	private $toTransferCommentables = array();
	private $toTransferVotables = array();

	protected function configure() {
		$this
			->setName('ladb:migrate:opencutlist:downloads')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force updating')
			->setDescription('Migrate opencutlist downloads')
			->setHelp(<<<EOT
The <info>ladb:migrate:books</info> command migrate opencutlist downloads
EOT
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$forced = $input->getOption('force');
		$verbose = $input->getOption('verbose');

		$om = $this->getContainer()->get('doctrine')->getManager();

		if ($verbose) {
			$output->write('<info>Retriving downloads...</info>');
		}

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array('d'))
			->from(Download::CLASS_NAME, 'd');

		try {
			$downloads = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$downloads = array();
		}

		if ($verbose) {
			$output->writeln('<comment> ['.count($downloads).' accesses to migrate]</comment>');
		}

		foreach ($downloads as $download) {

			$access = new Access();
			$access->setKind(Access::KIND_DOWNLOAD);
			$access->setEnv($download->getEnv());
			$access->setClientIp4($download->getClientIp4());
			$access->setClientUserAgent($download->getClientUserAgent());

			$om->persist($access);

		}

		if ($forced) {
			$om->flush();
		}

	}

}