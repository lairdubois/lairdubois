<?php
/**
 * @copyright Copyright (c) 2014 Carsten Brandt
 * @license https://github.com/cebe/markdown/blob/master/LICENSE
 * @link https://github.com/cebe/markdown#readme
 */

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
		if (preg_match('/^@([A-Za-z][A-Za-z0-9]+)/', $markdown, $matches)) {
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
