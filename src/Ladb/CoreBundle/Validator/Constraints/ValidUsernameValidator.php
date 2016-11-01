<?php

namespace Ladb\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Ladb\CoreBundle\Entity\User;

class ValidUsernameValidator extends ConstraintValidator {

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

			$unauthorizedUsernames = array(

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
				'trouvailles',
				'blog',
				'xylotheque',
				'fournisseurs',
				'financement',
				'outils',
				'api',

			);

			if (in_array($value->getUsernameCanonical(), $unauthorizedUsernames)) {
				$this->context->buildViolation('Ce nom d\'utilisateur n\'est pas autorisé')
					->atPath('username')
					->addViolation();
			}
			if (strlen($value->getUsername()) > 25) {
				$this->context->buildViolation('Le nom d\'utilisateur est trop long')
					->atPath('username')
					->addViolation();
			}
			if (preg_match('/\s/', $value->getUsername())) {
				$this->context->buildViolation('Le nom d\'utilisateur ne doit pas contenir d\'espaces')
					->atPath('username')
					->addViolation();
			}
			if (!preg_match('/^[a-zA-Z0-9]+$/', $value->getUsername())) {
				$this->context->buildViolation('Le nom d\'utilisateur ne doit pas contenir de caractères accentués ou de symboles')
					->atPath('username')
					->addViolation();
			}

		}
	}

}