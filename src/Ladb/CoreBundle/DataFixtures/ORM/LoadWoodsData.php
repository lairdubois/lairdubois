<?php

namespace Ladb\CoreBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Ladb\CoreBundle\Entity\Knowledge\Wood;

class LoadWoodsData extends AbstractFixture implements OrderedFixtureInterface {

	public function load(ObjectManager $manager) {
		$woodNames = array(
			'Pin',
			'Noyer',
			'Chêne',
			'Merisier',
			'Cèdre',
		);

		foreach ($woodNames as $woodName) {
			$wood = new Wood();
			$wood->setName($woodName);
			$manager->persist($wood);

			$this->addReference('wood-'.(\Gedmo\Sluggable\Util\Urlizer::urlize($wood->getName())), $wood);
		}

		$manager->flush();
	}

	public function getOrder() {
		return 1;
	}

}
