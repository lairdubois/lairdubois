<?php
/**
 * @copyright Copyright (c) 2014 Carsten Brandt
 * @license https://github.com/cebe/markdown/blob/master/LICENSE
 * @link https://github.com/cebe/markdown#readme
 */

namespace App\Parser\Markdown\Inline;

/**
 * Adds emoji inline elements
 */
trait EmojiTrait {

	/**
	 * Parses the emoji feature.
	 * @marker :
	 */
	protected function parseEmoji($markdown) {
		if (preg_match('/^:([a-z0-9_]+):/', $markdown, $matches)) {
			return [
				[
					'emoji', [['text', $matches[1]]]
				],
				strlen($matches[0])
			];
		}
		return [['text', $markdown[0]], 1];
	}

	protected function renderEmoji($block) {
		$client = new \Emojione\Client(new \Emojione\Ruleset());
		return $client->toImage(':'.$this->renderAbsy($block[1]).':');
	}

}
