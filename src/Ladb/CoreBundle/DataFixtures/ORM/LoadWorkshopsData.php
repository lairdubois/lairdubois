<?php

namespace Ladb\CoreBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Ladb\CoreBundle\Entity\Wonder\Workshop;
use Ladb\CoreBundle\Entity\License;

class LoadWorkshopsData extends AbstractFixture implements OrderedFixtureInterface {

	public function load(ObjectManager $manager) {

		$workshopDefs = array(
			array(
				'title' => 'Du bois et de la terre',
				'body' => 'Un petit coin pour se resourser au contact du bois.',
				'tagDefs' => array(
					'plein-pied'
				),
				'licenseDef' => array(
					'allowDerivs' => true,
					'shareAlike' => false,
					'allowCommercial' => true,
				),
			),
			array(
				'title' => 'Un sous sol bien rempli',
				'body' => 'Un petit coin pour se resourser au contact du bois.',
				'tagDefs' => array(
					'sous-sol'
				),
				'licenseDef' => array(
					'allowDerivs' => true,
					'shareAlike' => false,
					'allowCommercial' => true,
				),
			),
			array(
				'title' => 'Le garage du boiseu',
				'body' => 'Un petit coin pour se resourser au contact du bois.',
				'tagDefs' => array(
					'garage'
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
			foreach ($workshopDefs as $workshopDef) {

				$pictureSerie = ($index++ % count($workshopDefs)) + 1;

				$user = $manager->merge($this->getReference('user-'.rand(1, 50)));
				$user->incrementPublishedWorkshopCount();

				$workshop = new Workshop();
				$workshop->setTitle($workshopDef['title']);
				$workshop->setBody($workshopDef['body']);
				$workshop->setMainPicture($manager->merge($this->getReference('picture-workshop-'.$pictureSerie.'-1')));
				$workshop->addPicture($manager->merge($this->getReference('picture-workshop-'.$pictureSerie.'-1')));
				$workshop->addPicture($manager->merge($this->getReference('picture-workshop-'.$pictureSerie.'-2')));
				$workshop->setUser($user);
				$workshop->setViewCount(rand(0, 2000));
				$workshop->setArea(rand(10, 20));
				$workshop->setIsDraft(false);

				// Tags
				foreach ($workshopDef['tagDefs'] as $tagRef) {
					$workshop->addTag($manager->merge($this->getReference('tag-'.$tagRef)));
				}

				// License
				$licenseDef = $workshopDef['licenseDef'];
				$license = new License();
				$license->setAllowDerivs($licenseDef['allowDerivs']);
				$license->setShareAlike($licenseDef['shareAlike']);
				$license->setAllowCommercial($licenseDef['allowCommercial']);
				$workshop->setLicense($license);

				$manager->persist($workshop);
			}
		}

		$manager->flush();
	}

	public function getOrder() {
		return 3;
	}
	
}
