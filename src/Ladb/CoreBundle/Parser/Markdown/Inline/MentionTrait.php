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
		if (preg_match('/@([A-Za-z0-9]{3,})/', $markdown, $matches)) {
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
		return '<a href="https://www.lairdubois.fr/'.$this->renderAbsy($block[1]).'" target="_blank">@'.$this->renderAbsy($block[1]).'</a>';
	}

}
