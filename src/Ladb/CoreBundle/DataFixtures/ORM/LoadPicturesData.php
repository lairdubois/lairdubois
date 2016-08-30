<?php

namespace Ladb\CoreBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Ladb\CoreBundle\Entity\Picture;

class LoadPicturesData extends AbstractFixture implements OrderedFixtureInterface {

	public function load(ObjectManager $manager) {

		// Creations
		for ($i = 1; $i <= 7; $i++) {
			for ($j = 1; $j <= 4; $j++) {
				$picture = new Picture();
				$picture->setLegend('Image '.$i.'-'.$j);
				$picture->setMasterPath('creation'.$i.'-'.$j.'.jpeg');
				$manager->persist($picture);

				$this->addReference('picture-creation-'.$i.'-'.$j, $picture);
			}
		}

		// Workshops
		for ($i = 1; $i <= 3; $i++) {
			for ($j = 1; $j <= 2; $j++) {
				$picture = new Picture();
				$picture->setLegend('Image '.$i.'-'.$j);
				$picture->setMasterPath('workshop'.$i.'-'.$j.'.jpeg');
				$manager->persist($picture);

				$this->addReference('picture-workshop-'.$i.'-'.$j, $picture);
			}
		}

		// Plans
		for ($i = 1; $i <= 2; $i++) {
			for ($j = 1; $j <= 3; $j++) {
				$picture = new Picture();
				$picture->setLegend('Image '.$i.'-'.$j);
				$picture->setMasterPath('plan'.$i.'-'.$j.'.jpeg');
				$manager->persist($picture);

				$this->addReference('picture-plan-'.$i.'-'.$j, $picture);
			}
		}

		$manager->flush();
	}

	public function getOrder() {
		return 1;
	}

}
