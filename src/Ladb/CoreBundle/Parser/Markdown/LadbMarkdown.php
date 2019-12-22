<?php
/**
 * @copyright Copyright (c) 2014 Carsten Brandt
 * @license https://github.com/cebe/markdown/blob/master/LICENSE
 * @link https://github.com/cebe/markdown#readme
 */

namespace Ladb\CoreBundle\Parser\Markdown;

use cebe\markdown\Parser;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Markdown parser for github flavored markdown.
 *
 * @author Carsten Brandt <mail@cebe.cc>
 */
class LadbMarkdown extends Parser {

	private $userManager;
	private $router;

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

	public function __construct($userManager, $router) {
		$this->userManager = $userManager;
		$this->router = $router;
	}

	/////

	private function _truncateUrl($value, $removeProtocol = true, $lengthL = 15, $lengthR = 15, $separator = '...', $charset = 'UTF-8') {
		if ($removeProtocol) {
			$value = ltrim($value, 'htps:/');
		}
		$valueLength = mb_strlen($value, $charset);
		if ($valueLength > $lengthL + $lengthR) {
			return rtrim(mb_substr($value, 0, $lengthL, $charset)).$separator.ltrim(mb_substr($value, $valueLength - $lengthR, $lengthR, $charset));
		}
		return $value;
	}

	private function _isLocalUrl($url) {
		return preg_match('/^(?:http|https|)(?::\/\/|)(?:[a-z]+.|)lairdubois.fr/i', $url);
	}

	/////

	/**
	 * Consume lines for a paragraph
	 *
	 * Allow headlines, lists and code to break paragraphs
	 */
	protected function consumeParagraph($lines, $current) {
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
		$isLocalUrl = $this->_isLocalUrl($href);
		$target = $isLocalUrl ? '' : ' target="_blank"';
		$text = $this->_truncateUrl(htmlspecialchars(urldecode($block[1]), ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8'));
		if ($isLocalUrl) {
			try {

				// Extract url components
				$path = parse_url($href, PHP_URL_PATH);
				$query = parse_url($href, PHP_URL_QUERY);
				$fragment = parse_url($href, PHP_URL_FRAGMENT);

				// Try to retrieve route params
				$routeParams = $this->router->match($path);

				// Check if route is 'show'
				if (preg_match('/_show$/i', $routeParams['_route']) && isset($routeParams['id']) && is_null($query) && is_null($fragment)) {

					// Route is 'show' add widget url attribute
					$widgetHref = $this->router->generate(
						str_replace('show', 'widget', $routeParams['_route']),
						array( 'id' => intval($routeParams['id']) )
					);

					return '<div data-loader="ajax" data-src="'.$widgetHref.'" class="ladb-entity-widget"><div class="ladb-box ladb-box-lazy"><a href="'.$href.'">'.$text.'</a></div></div>';
				}

			} catch (\Exception $e) {
			}
			return '<a href="'.$href.'" class="ladb-link ladb-link-intern"><span>'.$text.'</span></a>';
		}
		return '<a href="'.$href.'" target="_blank" class="ladb-link ladb-link-extern"><span>'.$text.'</span></a>';
	}

	/**
	 * Set target to _blank and truncate Url
	 */
	protected function renderUrl($block) {
		$href = htmlspecialchars($block[1], ENT_COMPAT | ENT_HTML401, 'UTF-8');
		$target = $this->_isLocalUrl($href) ? ' class="ladb-link ladb-link-intern"' : ' class="ladb-link ladb-link-extern" target="_blank"';
		$text = $this->_truncateUrl(htmlspecialchars(urldecode($block[1]), ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8'));
		return '<a href="'.$href.'"'.$target.'><span>'.$text.'</span></a>';
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
		$href = htmlspecialchars($block['url'], ENT_COMPAT | ENT_HTML401, 'UTF-8');
		$target = $this->_isLocalUrl($href) ? '' : ' target="_blank"';
		return '<a href="'.$href.'"'.$target
		.(empty($block['title']) ? '' : ' title="'.htmlspecialchars($block['title'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE, 'UTF-8').'"')
		.'>'.$this->_truncateUrl($this->renderAbsy($block['text'])).'</a>';
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
