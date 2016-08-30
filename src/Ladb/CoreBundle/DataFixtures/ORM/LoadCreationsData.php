<?php

namespace Ladb\CoreBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Ladb\CoreBundle\Entity\Wonder\Creation;
use Ladb\CoreBundle\Entity\License;

class LoadCreationsData extends AbstractFixture implements OrderedFixtureInterface {

	public function load(ObjectManager $manager) {

		$creationDefs = array(
			array(
				'title' => 'Un tableau noir',
				'body' => 'Attention est-ce que tout le monde est prêt pour la leçon ?',
				'woodDefs' => array(
					'pin',
				),
				'finishDefs' => array(
					'vernis',
					'peinture',
				),
				'toolDefs' => array(
					'raboteuse',
					'degauchisseuse',
					'defonceuse',
				),
				'tagDefs' => array(
					'meuble',
					'ecole',
				),
				'licenseDef' => array(
					'allowDerivs' => true,
					'shareAlike' => false,
					'allowCommercial' => true,
				),
			),
			array(
				'title' => 'Un cahier d\'école',
				'body' => 'Prenez vos cahier, interrogation écrite. Et on ne rigole pas avec ça.',
				'woodDefs' => array(
					'noyer',
					'chene'
				),
				'finishDefs' => array(
					'vernis',
				),
				'toolDefs' => array(
					'raboteuse',
					'degauchisseuse',
					'defonceuse',
				),
				'tagDefs' => array(
					'objet',
					'ecole',
				),
				'licenseDef' => array(
					'allowDerivs' => true,
					'shareAlike' => false,
					'allowCommercial' => true,
				),
			),
			array(
				'title' => 'Chariot à bûches',
				'body' => 'Il commence à faire plutôt froid dehors. Alors il est temps de rentrer un peu de bois près de la cheminée.',
				'woodDefs' => array(
					'pin',
				),
				'finishDefs' => array(
				),
				'toolDefs' => array(
					'mortaiseuse',
				),
				'tagDefs' => array(
					'vehicule',
					'chauffage',
				),
				'licenseDef' => array(
					'allowDerivs' => true,
					'shareAlike' => false,
					'allowCommercial' => true,
				),
			),
			array(
				'title' => 'Comtoise en merisier',
				'body' => 'Tic, tac ... c\'est un bruit qui n\'est pas si banal dans nos maisons modernes.',
				'woodDefs' => array(
					'merisier',
				),
				'finishDefs' => array(
					'cire',
				),
				'toolDefs' => array(
					'raboteuse',
					'degauchisseuse',
					'toupie',
					'mortaiseuse',
				),
				'tagDefs' => array(
					'meuble',
					'horloge',
				),
				'licenseDef' => array(
					'allowDerivs' => true,
					'shareAlike' => false,
					'allowCommercial' => true,
				),
			),
			array(
				'title' => 'Un pense bête',
				'body' => 'Rien oublier et penser à tout. C\'est bien la malheur de notre époque.',
				'woodDefs' => array(
					'cedre',
				),
				'finishDefs' => array(
					'cire',
				),
				'toolDefs' => array(
					'scie-circulaire',
					'raboteuse',
					'degauchisseuse',
					'mortaiseuse',
					'defonceuse',
				),
				'tagDefs' => array(
					'deco',
				),
				'licenseDef' => array(
					'allowDerivs' => true,
					'shareAlike' => false,
					'allowCommercial' => true,
				),
			),
			array(
				'title' => 'Rocking chair enfant',
				'body' => 'Qui a dit qu\'une chaise ça avait quatre pieds ?',
				'woodDefs' => array(
					'cedre',
				),
				'finishDefs' => array(
					'cire',
				),
				'toolDefs' => array(
					'scie-circulaire',
					'raboteuse',
					'degauchisseuse',
					'mortaiseuse',
				),
				'tagDefs' => array(
					'meuble',
					'chaise',
				),
				'licenseDef' => array(
					'allowDerivs' => true,
					'shareAlike' => false,
					'allowCommercial' => true,
				),
			),
			array(
				'title' => 'Brosse pour tableau',
				'body' => 'Je crois qu\'on peut dire que ça va avec le tableau.',
				'woodDefs' => array(
					'chene',
				),
				'finishDefs' => array(
				),
				'toolDefs' => array(
					'scie-circulaire',
					'raboteuse',
					'degauchisseuse',
					'defonceuse',
				),
				'tagDefs' => array(
					'objet',
					'ecole',
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
			foreach ($creationDefs as $creationDef) {

				$pictureSerie = ($index++ % count($creationDefs)) + 1;

				$user = $manager->merge($this->getReference('user-'.rand(1, 50)));
				$user->incrementPublishedCreationCount();

				$creation = new Creation();
				$creation->setTitle($creationDef['title']);
				$creation->setBody($creationDef['body']);
				$creation->setMainPicture($manager->merge($this->getReference('picture-creation-'.$pictureSerie.'-1')));
				$creation->addPicture($manager->merge($this->getReference('picture-creation-'.$pictureSerie.'-1')));
				$creation->addPicture($manager->merge($this->getReference('picture-creation-'.$pictureSerie.'-2')));
				$creation->addPicture($manager->merge($this->getReference('picture-creation-'.$pictureSerie.'-3')));
				$creation->addPicture($manager->merge($this->getReference('picture-creation-'.$pictureSerie.'-4')));
				$creation->setUser($user);
				$creation->setViewCount(rand(0, 10000));
				$creation->setIsDraft(false);

				// Woods
				foreach ($creationDef['woodDefs'] as $woodRef) {
					$creation->addWood($manager->merge($this->getReference('wood-'.$woodRef)));
				}

				// Finishes
				foreach ($creationDef['finishDefs'] as $finishRef) {
					$creation->addFinish($manager->merge($this->getReference('finish-'.$finishRef)));
				}

				// Tools
				foreach ($creationDef['toolDefs'] as $toolRef) {
					$creation->addTool($manager->merge($this->getReference('tool-'.$toolRef)));
				}

				// Tags
				foreach ($creationDef['tagDefs'] as $tagRef) {
					$creation->addTag($manager->merge($this->getReference('tag-'.$tagRef)));
				}

				// License
				$licenseDef = $creationDef['licenseDef'];
				$license = new License();
				$license->setAllowDerivs($licenseDef['allowDerivs']);
				$license->setShareAlike($licenseDef['shareAlike']);
				$license->setAllowCommercial($licenseDef['allowCommercial']);
				$creation->setLicense($license);

				$manager->persist($creation);
			}
		}

		$manager->flush();
	}

	public function getOrder() {
		return 3;
	}

}
