<?php

namespace Ladb\CoreBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Ladb\CoreBundle\Entity\User;

class LoadUsersData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface {

	private $container;

	public function setContainer(ContainerInterface $container = null) {
		$this->container = $container;
	}

	public function load(ObjectManager $manager) {
		$userManager = $this->container->get('fos_user.user_manager');

		$user = $userManager->createUser();
		$user->setUsername('Zeloko');
		$user->setEmail('bo@borisbeaulant.com');
		$user->setPlainPassword('pass');
		$user->setEnabled(true);
		$user->setRoles(array('ROLE_ADMIN'));
		$userManager->updateUser($user, false);

		$userNames = array('Pierre', 'Paul', 'Jacques', 'Charles', 'Jean');
		$userLocations = array('Paris', 'Lyon', 'Marseille', 'Trifouilly les Oies', 'Saint-Germain-La-Tuile');
		$userWebsite = array('http://www.bricoleur.fr', 'http://www.mon-blog.fr', 'http://www.menuisier.com', 'http://www.ebeniste.fr', 'http://www.jaimelebois.fr');
		$index = 1;
		for ($i = 0; $i < 10; ++$i) {
			foreach ($userNames as $userName) {
				if ($i > 0) {
					$userName .= $i;
				}
				$user = $userManager->createUser();
				$user->setUsername($userName);
				$user->setEmail($userName . '@lairdubois.fr');
				$user->setPlainPassword('pass');
				$user->setEnabled(true);
				$user->setRoles(array('ROLE_USER'));
				$user->setLocation($userLocations[rand(1, count($userLocations) - 1)]);
				$user->setWebsite($userWebsite[rand(1, count($userWebsite) - 1)]);
				$userManager->updateUser($user, false);

				$this->addReference('user-' . $index++, $user);
			}
		}

		$manager->flush();
	}

	public function getOrder() {
		return 0;
	}

}
