<?php

namespace App\Command;

use DirkGroenen\Pinterest\Pinterest;
use App\Entity\Howto\Howto;
use App\Entity\Offer\Offer;
use App\Entity\Wonder\Creation;
use App\Manager\Offer\OfferManager;
use App\Model\StripableInterface;
use App\Utils\WebScreenshotUtils;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Facebook\FacebookSession;
use App\Entity\Core\Spotlight;
use App\Utils\MailerUtils;
use App\Utils\TypableUtils;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;

class CronOffersCommand extends AbstractContainerAwareCommand {

    public static function getSubscribedServices() {
        return array_merge(parent::getSubscribedServices(), array(
            '?'.OfferManager::class,
            '?'.MailerUtils::class,
        ));
    }

    /////

    protected function configure() {
		$this
			->setName('ladb:cron:offers')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force updating')
			->setDescription('Check offers lifetime')
			->setHelp(<<<EOT
The <info>ladb:cron:offers</info> check offers lifetime
EOT
			);
	}

	/////

	protected function execute(InputInterface $input, OutputInterface $output) {

		$forced = $input->getOption('force');
		$verbose = $input->getOption('verbose');

		$om = $this->getDoctrine()->getManager();
		$offerManager = $this->get(OfferManager::class);
		$mailerUtils = $this->get(MailerUtils::class);

		// Retrieve OUTDATED offers

		if ($verbose) {
			$output->write('<info>Retriving outdated offers...</info>');
		}

		$retrieveDate = (new \DateTime())->modify('-'.Offer::FULL_LIFETIME);

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'o' ))
			->from('App\Entity\Offer\Offer', 'o')
			->where('o.publishCount > 0')
			->andWhere('o.changedAt < :retrieveDate')
			->setParameter('retrieveDate', $retrieveDate)
		;

		try {
			$oudatedOffers = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$oudatedOffers = array();
		}

		if ($verbose) {
			$output->writeln('<comment> ['.count($oudatedOffers).' outdated offers]</comment>');
		}

		foreach ($oudatedOffers as $offer) {

			if ($verbose) {
				$output->write('Deleting Offer id='.$offer->getId());
			}

			// Delete Offer
			$offerManager->delete($offer, true, false);

			if ($verbose) {
				if ($forced) {
					$output->writeln('<fg=cyan> [OK]</fg=cyan>');
				} else {
					$output->writeln('<fg=cyan> [FAKE]</fg=cyan>');
				}
			}

		}

		// Retrieve EXPIRED offers

		if ($verbose) {
			$output->write('<info>Retriving expired offers...</info>');
		}

		$retrieveDate = (new \DateTime())->modify('-'.Offer::ACTIVE_LIFETIME);

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'o' ))
			->from('App\Entity\Offer\Offer', 'o')
			->where('o.isDraft = false')
			->andWhere('o.changedAt < :retrieveDate')
			->setParameter('retrieveDate', $retrieveDate)
		;

		try {
			$expiredOffers = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$expiredOffers = array();
		}

		if ($verbose) {
			$output->writeln('<comment> ['.count($expiredOffers).' expired offers]</comment>');
		}

		foreach ($expiredOffers as $offer) {

			if ($verbose) {
				$output->write('Unpublishing Offer id='.$offer->getId());
			}

			// Unpublish offer
			$offerManager->unpublish($offer, false);

			// Email notification
			$mailerUtils->sendOfferExpiredEmailMessage($offer->getUser(), $offer);

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

        return Command::SUCCESS;
	}
}