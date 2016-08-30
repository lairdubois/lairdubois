<?php

namespace Ladb\CoreBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Ladb\CoreBundle\Entity\Knowledge\Tool;

class LoadToolsData extends AbstractFixture implements OrderedFixtureInterface {

	public function load(ObjectManager $manager) {
		$toolNames = array(
			'Scie circulaire',
			'Raboteuse',
			'Dégauchisseuse',
			'Toupie',
			'Mortaiseuse',
			'Défonceuse',
		);

		foreach ($toolNames as $toolName) {
			$tool = new Tool();
			$tool->setName($toolName);
			$manager->persist($tool);

			$this->addReference('tool-'.(\Gedmo\Sluggable\Util\Urlizer::urlize($tool->getName())), $tool);
		}

		$manager->flush();
	}

	public function getOrder() {
		return 1;
	}

}
