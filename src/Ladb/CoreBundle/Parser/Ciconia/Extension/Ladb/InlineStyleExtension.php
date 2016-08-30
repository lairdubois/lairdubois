<?php

namespace Ladb\CoreBundle\Parser\Ciconia\Extension\Ladb;

use Ciconia\Common\Tag;
use Ciconia\Common\Text;
use Ciconia\Extension\ExtensionInterface;
use Ciconia\Markdown;
use Ciconia\Renderer\RendererAwareInterface;
use Ciconia\Renderer\RendererAwareTrait;

class InlineStyleExtension implements ExtensionInterface, RendererAwareInterface {

	use RendererAwareTrait;

	/**
	 * @var Markdown
	 */
	private $markdown;

	/**
	 * {@inheritdoc}
	 */
	public function register(Markdown $markdown)
	{
		$this->markdown = $markdown;

		$markdown->on('inline', array($this, 'processStrikeThrough'), 70);
		$markdown->on('inline', array($this, 'processMultipleStar'), 69);
		$markdown->on('inline', array($this, 'processMultipleUnderscore'), 69);
	}

	/**
	 * Multiple stars in words
	 *
	 * It is not reasonable to italicize just part of a word, especially when you're dealing with code and names often
	 * appear with multiple stars.
	 *
	 * @param Text $text
	 */
	public function processMultipleStar(Text $text) {
		$text->replace('/(\[?\w+\*\w+\*\w[\w*]*\]?)/', function (Text $w, Text $word) {
			$stars = $word->split('//')->filter(function (Text $item) {
				return $item == '*';
			});

			if (count($stars) >= 2) {
				$word->replaceString('*', '\\*');
				$this->markdown->emit('escape.special_chars', [ $word ]);
			}

			return $word;
		});
	}

	/**
	 * Multiple underscore in words
	 *
	 * It is not reasonable to italicize just part of a word, especially when you're dealing with code and names often
	 * appear with multiple underscores.
	 *
	 * @param Text $text
	 */
	public function processMultipleUnderscore(Text $text) {
		$text->replace('/(\[?\w+\_\w+\_\w[\w*]*\]?)/', function (Text $w, Text $word) {
			$underscores = $word->split('//')->filter(function (Text $item) {
				return $item == '_';
			});

			if (count($underscores) >= 2) {
				$word->replaceString('_', '\\_');
				$this->markdown->emit('escape.special_chars', [ $word ]);
			}

			return $word;
		});
	}

	/**
	 * Strike-through `~~word~~` to `<del>word</del>`
	 *
	 * @param Text $text
	 */
	public function processStrikeThrough(Text $text)
	{
		/** @noinspection PhpUnusedParameterInspection */
		$text->replace('{ (~~) (?=\S) (.+?) (?<=\S) \1 }sx', function (Text $w, Text $a, Text $target) {
			return $this->getRenderer()->renderTag('del', $target, Tag::TYPE_INLINE);
		});
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return 'ladbInlineStyle';
	}

}