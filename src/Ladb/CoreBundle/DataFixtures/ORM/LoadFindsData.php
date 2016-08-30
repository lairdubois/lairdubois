<?php

namespace Ladb\CoreBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Ladb\CoreBundle\Entity\Tag;
use Ladb\CoreBundle\Entity\Find\Find;

class LoadFindsData extends AbstractFixture implements OrderedFixtureInterface {

	public function load(ObjectManager $manager) {

		$findDefs = array(
			array(
				'title' => 'Un site sympa pour se former en vidéo',
				'url' => 'http://www.copaindescopeaux.fr',
				'body' => 'Il y a plein de choses : des vidéos, des plans, des conseils et des astuces. Bref faut absolument y faire un tour.',
				'tagDefs' => array(
					'video',
					'formation',
				),
			),
			array(
				'title' => 'Un blog sympa et plein d\'infos utiles',
				'url' => 'http://www.avbois.fr',
				'body' => 'Il y a plein de choses : des conseils et des astuces. Bref faut absolument y faire un tour.',
				'tagDefs' => array(
					'blog',
					'pas-a-pas',
				),
			),
		);

		foreach ($findDefs as $findDef) {

			$user = $manager->merge($this->getReference('user-' . rand(1, 50)));
			$user->incrementPublishedFindCount();

			$find = new Find();
			$find->setUser($user);
			$find->setTitle($findDef['title']);
			$find->setUrl($findDef['url']);
			$find->setBody($findDef['body']);
			$find->setViewCount(rand(0, 10000));

			// Tags
			foreach ($findDef['tagDefs'] as $tagRef) {
				$find->addTag($manager->merge($this->getReference('tag-'.$tagRef)));
			}

			$manager->persist($find);
		}

		$manager->flush();
	}

	public function getOrder() {
		return 4;
	}

}
