<?php

namespace Ladb\CoreBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Ladb\CoreBundle\Entity\Knowledge\Finish;

class LoadFinishesData extends AbstractFixture implements OrderedFixtureInterface {

	public function load(ObjectManager $manager) {
		$finishNames = array(
			'Vernis',
			'Peinture',
			'Cire',
		);

		foreach ($finishNames as $finishName) {
			$finish = new Finish();
			$finish->setName($finishName);
			$manager->persist($finish);

			$this->addReference('finish-'.(\Gedmo\Sluggable\Util\Urlizer::urlize($finish->getName())), $finish);
		}

		$manager->flush();
	}

	public function getOrder() {
		return 1;
	}

}
