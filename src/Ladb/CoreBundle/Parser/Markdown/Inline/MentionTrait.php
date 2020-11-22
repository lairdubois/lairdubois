<?php

namespace Ladb\CoreBundle\Parser\Markdown\Inline;

/**
 * Adds mention inline elements
 */
trait MentionTrait {

	/**
	 * Parses the mention feature.
	 * @marker @
	 */
	protected function parseMention($markdown) {
		if (preg_match('/^@([A-Za-z0-9]{3,})/', $markdown, $matches)) {
			return [
				[
					'mention', [['text', $matches[1]]]
				],
				strlen($matches[0])
			];
		}
		return [['text', $markdown[0]], 1];
	}

	protected function renderMention($block) {
		$user = $this->userManager->findUserByUsername($this->renderAbsy($block[1]));
		if (!is_null($user)) {
			return '<a href="/@'.$user->getUsernameCanonical().'" class="ladb-mention"><span>'.$user->getDisplayName().'</span></a>';
		}
		return '<span class="ladb-mention-unknown"><span>'.$this->renderAbsy($block[1]).'</span></span>';
	}

}
