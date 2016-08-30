<?php

namespace Ladb\CoreBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Ladb\CoreBundle\Entity\Tag;

class LoadTagsData extends AbstractFixture implements OrderedFixtureInterface {

	public function load(ObjectManager $manager) {
		$tagNames = array(
			/* Creations */		'Meuble', 'Ecole', 'Objet', 'Véhicule', 'Chauffage', 'Chaise', 'Horloge', 'Déco',
			/* Workshops */		'Sous-sol', 'Garage', 'Plein pied',
			/* Plans */			'Jeux', 'Neige',
			/* Finds */			'Blog', 'Vidéo', 'Pas à Pas', 'Formation',
		);

		foreach ($tagNames as $tagName) {
			$tag = new Tag();
			$tag->setName(\Gedmo\Sluggable\Util\Urlizer::urlize($tagName));
			$manager->persist($tag);

			$this->addReference('tag-'.(\Gedmo\Sluggable\Util\Urlizer::urlize($tag->getName())), $tag);
		}

		$manager->flush();
	}

	public function getOrder() {
		return 1;
	}

}
