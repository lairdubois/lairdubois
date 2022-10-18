<?php

namespace Ladb\CoreBundle\Validator\Constraints;

use Ladb\CoreBundle\Entity\Core\UserWitness;
use Ladb\CoreBundle\Fos\UserManager;
use Ladb\CoreBundle\Utils\GlobalUtils;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Ladb\CoreBundle\Entity\Core\User;

class ValidUserUsernameValidator extends ConstraintValidator {

	const UNAUTHORIZED_USERNAMES = array(

		'announcement',
		'admin',
		'administrator',
		'administrateur',
		'administrateurs',
		'modo',
		'moderateur',
		'moderateurs',
		'lairdubois',

		'login',
		'smartlogin',
		'register',
		'rejoindre',
		'resetting',
		'email',
		'likes',
		'comments',
		'reports',
		'watches',
		'followers',
		'tags',
		'knowledge',
		'notifications',
		'referer',

		'new',
		'create',
		'publish',
		'unpublish',
		'update',
		'edit',
		'delete',
		'upload',
		'copy',

		'activate',
		'deactivate',
		'empty',

		'uploads',
		'media',
		'sitemap',

		'rechercher',
		'a-propos',
		'faq',
		'me',
		'parametres',
		'messages',
		'messagerie',
		'creations',
		'ateliers',
		'boiseux',
		'projets',
		'pas-a-pas',
		'plans',
		'questions',
		'processus',
		'trouvailles',
		'blog',
		'xylotheque',
		'fournisseurs',
		'ecoles',
		'livres',
		'financement',
		'outils',
		'api',
		'youtook',
		'questions',
		'promouvoir',
		'collections',
		'logiciels',
		'astuces',
		'annonces',
		'evenements',
		'opencutlist',
		'collectifs',

	);

	protected $container;

	public function __construct(Container $container) {
		$this->container = $container;
	}

	/**
	 * Checks if the passed value is valid.
	 *
	 * @param mixed $value      The value that should be validated
	 * @param Constraint $constraint The constraint for the validation
	 *
	 * @api
	 */
	public function validate($value, Constraint $constraint) {
		if ($value instanceof User) {


			if (in_array($value->getUsernameCanonical(), self::UNAUTHORIZED_USERNAMES)) {
				$this->context->buildViolation('Ce nom d\'utilisateur n\'est pas autorisé.')
					->atPath('username')
					->addViolation();
			}
			if (strlen($value->getUsername()) > 25) {
				$this->context->buildViolation('Le nom d\'utilisateur est trop long.')
					->atPath('username')
					->addViolation();
			}
			if (preg_match('/\s/', $value->getUsername())) {
				$this->context->buildViolation('Le nom d\'utilisateur ne doit pas contenir d\'espaces.')
					->atPath('username')
					->addViolation();
			}
			if (!preg_match('/^[a-zA-Z0-9]+$/', $value->getUsername())) {
				$this->context->buildViolation('Le nom d\'utilisateur ne doit pas contenir de caractères accentués ou de symboles.')
					->atPath('username')
					->addViolation();
			}

			$userRepository = $this->container->get('doctrine')->getRepository(User::class);
			$currentUser = $value->getId() ? $userRepository->findOneById($value->getId()) : null;

			$userManager = $this->container->get(UserManager::NAME);
			$user = $userManager->findUserByUsername($value->getUsername());
			if (is_null($user)) {
				$user = $userManager->findUserByDisplayname($value->getUsername());
			}

			$exists = !is_null($user) && is_null($currentUser) || !is_null($user) && !is_null($currentUser) && $user !== $currentUser;
			if (is_null($user)) {

				$userWitnessRepository = $this->container->get('doctrine')->getRepository(UserWitness::class);
				$userWitness = $userWitnessRepository->findOneByUsername($value->getUsernameCanonical());
				if (is_null($userWitness)) {

					if (!is_null($currentUser)) {

						// Check max changes count
						if ($userWitnessRepository->countByUser($currentUser) > 2) {
							$this->context->buildViolation('Le nombre limite de 2 changements est déjà atteint.')
								->atPath('username')
								->addViolation();
						}

						// Check min change delay
						else if ($userWitnessRepository->existsNewerByUserFromDate($currentUser, (new \DateTime())->sub(new \DateInterval('P1D')))) {
							$this->context->buildViolation('Seul 1 nouveau nom d\'utilisateur est possible par tranche de 24h.')
								->atPath('username')
								->addViolation();
						}

					}

				} else {
					$exists = $userWitness->getUser() !== $currentUser;
				}

			}
			if ($exists) {
				$this->context->buildViolation('Le nom d\'utilisateur est déjà utilisé.')
					->atPath('username')
					->addViolation();
			}

		}
	}

}