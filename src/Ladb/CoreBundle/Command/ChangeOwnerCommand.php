<?php

namespace Ladb\CoreBundle\Command;

use Ladb\CoreBundle\Entity\Core\Block\Gallery;
use Ladb\CoreBundle\Entity\Core\User;
use Ladb\CoreBundle\Entity\Wonder\Creation;
use Ladb\CoreBundle\Fos\UserManager;
use Ladb\CoreBundle\Manager\Wonder\CreationManager;
use Ladb\CoreBundle\Model\AuthoredInterface;
use Ladb\CoreBundle\Model\BlockBodiedInterface;
use Ladb\CoreBundle\Model\HiddableInterface;
use Ladb\CoreBundle\Model\MultiPicturedInterface;
use Ladb\CoreBundle\Model\PublicationInterface;
use Ladb\CoreBundle\Utils\TypableUtils;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

class ChangeOwnerCommand extends ContainerAwareCommand {

	protected function configure() {
		$this
			->setName('ladb:change:owner')
			->addOption('target-username', 't', InputOption::VALUE_REQUIRED, 'Target username')
			->addOption('creation-id', 'cid', InputOption::VALUE_REQUIRED, 'Creation ID')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force removing')
			->setDescription('Change owner')
			->setHelp(<<<EOT
The <info>ladb:change:owner</info> command change owner
EOT
		);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$forced = $input->getOption('force');
		$targetUsername = $input->getOption('target-username');
		$creationId = $input->getOption('creation-id');

		$om = $this->getContainer()->get('doctrine')->getManager();

		// Retrieve target user ////

		$userManager = $this->getContainer()->get(UserManager::NAME);
		$targetUser = $userManager->findUserByUsername($targetUsername);

		if (is_null($targetUser)) {
			$output->writeln('<error>Unknow unsername='.$targetUsername.'</error>', 0);
			return;
		}

		$output->writeln('<info>targetUser='.$targetUser->getDisplayName().'</info>');

		// Retrieve creation ////

		$typableUtils = $this->getContainer()->get(TypableUtils::NAME);
		$typable = $typableUtils->findTypable(Creation::TYPE, $creationId);

		if (is_null($typable)) {
			$output->writeln('<error>Unknow Creation creationId='.$creationId.'</error>', 0);
			return;
		}

		$output->writeln('<info>typable='.$typable->getTitle().'</info>');

		$creationManager = $this->getContainer()->get(CreationManager::NAME);
		$creationManager->changeOwner($typable, $targetUser, false);

		if ($forced) {
			$om->flush();
		}

	}

}