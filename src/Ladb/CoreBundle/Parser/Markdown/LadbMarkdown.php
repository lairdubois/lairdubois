<?php
/**
 * @copyright Copyright (c) 2014 Carsten Brandt
 * @license https://github.com/cebe/markdown/blob/master/LICENSE
 * @link https://github.com/cebe/markdown#readme
 */

namespace Ladb\CoreBundle\Parser\Markdown;

use cebe\markdown\Parser;

/**
 * Markdown parser for github flavored markdown.
 *
 * @author Carsten Brandt <mail@cebe.cc>
 */
class LadbMarkdown extends Parser {

	private $userManager;

	// include block element parsing using traits
	use \cebe\markdown\block\HeadlineTrait;
	use \cebe\markdown\block\ListTrait {
		// Check Ul List before headline
		identifyUl as private;
		identifyUl as protected identifyBUl;
		consumeUl as protected consumeBUl;
	}
	use \cebe\markdown\block\QuoteTrait;
	use \cebe\markdown\block\RuleTrait {
		// Check Hr before checking lists
		identifyHr as private;
		identifyHr as protected identifyAHr;
		consumeHr as protected consumeAHr;
	}

	// include inline element parsing using traits
	use Inline\EmojiTrait;
	use \cebe\markdown\inline\EmphStrongTrait;
	use \cebe\markdown\inline\LinkTrait {
		parseImage as private;
		identifyReference as private;
	}
	use \cebe\markdown\inline\StrikeoutTrait;
	use \cebe\markdown\inline\UrlLinkTrait;
	use Inline\MentionTrait;

	/**
	 * @var boolean whether to interpret newlines as `<br />`-tags.
	 * This feature is useful for comments where newlines are often meant to be real new lines.
	 */
	public $enableNewlines = true;

	/**
	 * @var integer the headline level offset
	 * This feature define the headline level offset.
	 */
	public $headingLevelOffset = 2;

	private $html5 = true;

	/////

	public function __construct($userManager) {
		$this->userManager = $userManager;
	}

	/////

	private function _truncateUrl($value, $length = 30, $preserve = false, $separator = '...', $charset = 'UTF-8') {
		if (mb_strlen($value, $charset) > $length && mb_strpos($value, 'http', 0, $charset) !== false) {
			if ($preserve) {

				// If breakpoint is on the last word, return the value without separator.
				if (false === ($breakpoint = mb_strpos($value, ' ', $length, $charset))) {
					return $value;
				}

				$length = $breakpoint;
			}

			return rtrim(mb_substr($value, 0, $length, $charset)).$separator;
		}
		return $value;
	}

	/////

	/**
	 * Consume lines for a paragraph
	 *
	 * Allow headlines, lists and code to break paragraphs
	 */
	protected function consumeParagraph($lines, $current)
	{
		// consume until newline
		$content = [];
		for ($i = $current, $count = count($lines); $i < $count; $i++) {
			$line = $lines[$i];
			if ($line === ''
				|| ltrim($line) === ''
				|| !ctype_alpha($line[0]) && (
					$this->identifyQuote($line, $lines, $i) ||
					$this->identifyUl($line, $lines, $i) ||
					$this->identifyOl($line, $lines, $i) ||
					$this->identifyHr($line, $lines, $i)
				)
				|| $this->identifyHeadline($line, $lines, $i))
			{
				break;
			} else {
				$content[] = $line;
			}
		}
		$block = [
			'paragraph',
			'content' => $this->parseInline(implode("\n", $content)),
		];
		return [$block, --$i];
	}

	///// Link

	/**
	 * Set target to _blank and truncate Url
	 */
	protected function renderAutoUrl($block) {
		$href = htmlspecialchars($block[1], ENT_COMPAT | ENT_HTML401, 'UTF-8');
		$text = $this->_truncateUrl(htmlspecialchars(urldecode($block[1]), ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8'));
		return '<a href="'.$href.'" target="_blank">'.$text.'</a>';
	}

	/**
	 * Set target to _blank and truncate Url
	 */
	protected function renderUrl($block) {
		$href = htmlspecialchars($block[1], ENT_COMPAT | ENT_HTML401, 'UTF-8');
		$text = $this->_truncateUrl(htmlspecialchars(urldecode($block[1]), ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8'));
		return '<a href="'.$href.'" target="_blank">'.$text.'</a>';
	}

	/**
	 * Set target to _blank and truncate Url
	 */
	protected function renderLink($block) {
		if (isset($block['refkey'])) {
			if (($ref = $this->lookupReference($block['refkey'])) !== false) {
				$block = array_merge($block, $ref);
			} else {
				return $block['orig'];
			}
		}
		return '<a href="'.htmlspecialchars($block['url'], ENT_COMPAT | ENT_HTML401, 'UTF-8').'"'
		.(empty($block['title']) ? '' : ' title="'.htmlspecialchars($block['title'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE, 'UTF-8').'"')
		.' target="_blank">'.$this->_truncateUrl($this->renderAbsy($block['text'])).'</a>';
	}

	///// Headline

	/**
	 * Renders a headline
	 */
	protected function renderHeadline($block) {
		$tag = 'h'.min(6, $block['level'] + $this->headingLevelOffset);
		return "<$tag>".$this->renderAbsy($block['content'])."</$tag>\n";
	}


	///// Text

	/**
	 * Parses a newline indicated by two spaces on the end of a markdown line.
	 */
	protected function renderText($text) {
		return strtr($text[1], ["  \n" => "<br>", "\n" => "<br>"]); // whether to interpret newlines as `<br />`-tags.
	}

}
