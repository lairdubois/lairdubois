<?php

namespace Ladb\CoreBundle\Command;

use Ladb\CoreBundle\Entity\Core\Block\Gallery;
use Ladb\CoreBundle\Entity\Core\User;
use Ladb\CoreBundle\Entity\Wonder\Creation;
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
			->addOption('force', null, InputOption::VALUE_NONE, 'Force removing')
			->setDescription('Change owner')
			->setHelp(<<<EOT
The <info>ladb:cleanup:blocks</info> command change owner
EOT
		);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$forced = $input->getOption('force');

		$om = $this->getContainer()->get('doctrine')->getManager();

		// Retrieve target user ////

		$targetUserId = 1; //17684;

		$userRepository = $om->getRepository(User::class);
		$targetUser = $userRepository->findOneById($targetUserId);

		$output->writeln('<info>$targetUser='.$targetUser->getDisplayName().'</info>');

		// Retrieve creation ////

		$creationId = 1; //12040;

		$creationRepository = $om->getRepository(Creation::class);
		$creation = $creationRepository->findOneById($creationId);

		$output->writeln('<info>$creation='.$creation->getTitle().'</info>');

		foreach ($creation->getPictures() as $picture) {
			$picture->setUser($targetUser);
		}
		foreach ($creation->getBodyBlocks() as $bodyBlock) {
			if ($bodyBlock instanceof Gallery) {
				foreach ($bodyBlock->getPictures() as $picture) {
					$picture->setUser($targetUser);
				}
			}
		}

		if ($forced) {
			$om->flush();
		}
	}

}