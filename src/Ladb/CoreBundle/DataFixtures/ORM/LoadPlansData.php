<?php

namespace Ladb\CoreBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Ladb\CoreBundle\Entity\Wonder\Plan;
use Ladb\CoreBundle\Entity\License;

class LoadPlansData extends AbstractFixture implements OrderedFixtureInterface {

	public function load(ObjectManager $manager) {

		$planDefs = array(
			array(
				'title' => 'Une luge',
				'body' => 'En avant sur les pistes !',
				'tagDefs' => array(
					'jeux',
					'neige',
				),
				'licenseDef' => array(
					'allowDerivs' => true,
					'shareAlike' => false,
					'allowCommercial' => true,
				),
			),
			array(
				'title' => 'Domino',
				'body' => 'Un bon moyen de jouer en famille.',
				'tagDefs' => array(
					'jeux',
				),
				'licenseDef' => array(
					'allowDerivs' => true,
					'shareAlike' => false,
					'allowCommercial' => true,
				),
			),
		);

		for ($i = 0; $i < 10; ++$i) {
			$index = 0;
			foreach ($planDefs as $planDef) {

				$pictureSerie = ($index++ % count($planDefs)) + 1;

				$user = $manager->merge($this->getReference('user-'.rand(1, 50)));
				$user->incrementPublishedPlanCount();

				$plan = new Plan();
				$plan->setTitle($planDef['title']);
				$plan->setBody($planDef['body']);
				$plan->setMainPicture($manager->merge($this->getReference('picture-plan-'.$pictureSerie.'-1')));
				$plan->addPicture($manager->merge($this->getReference('picture-plan-'.$pictureSerie.'-1')));
				$plan->addPicture($manager->merge($this->getReference('picture-plan-'.$pictureSerie.'-2')));
				$plan->addPicture($manager->merge($this->getReference('picture-plan-'.$pictureSerie.'-3')));
				$plan->setUser($user);
				$plan->setViewCount(rand(0, 10000));
				$plan->setIsDraft(false);

				// Tags
				foreach ($planDef['tagDefs'] as $tagRef) {
					$plan->addTag($manager->merge($this->getReference('tag-'.$tagRef)));
				}

				// License
				$licenseDef = $planDef['licenseDef'];
				$license = new License();
				$license->setAllowDerivs($licenseDef['allowDerivs']);
				$license->setShareAlike($licenseDef['shareAlike']);
				$license->setAllowCommercial($licenseDef['allowCommercial']);
				$plan->setLicense($license);

				$manager->persist($plan);
			}
		}

		$manager->flush();
	}

	public function getOrder() {
		return 3;
	}

}
