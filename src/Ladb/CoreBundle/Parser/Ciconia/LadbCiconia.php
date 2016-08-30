<?php

namespace Ladb\CoreBundle\Parser\Ciconia;

use Ciconia\Ciconia;
use Ladb\CoreBundle\Parser\Ciconia\Extension\Core;
use Ladb\CoreBundle\Parser\Ciconia\Extension\Ladb;
use Ladb\CoreBundle\Parser\Ciconia\Renderer\LadbHtmlRenderer;

class LadbCiconia extends Ciconia {

	protected function getDefaultRenderer() {
		return new LadbHtmlRenderer();
	}

	protected function getDefaultExtensions() {
		return array(

			new Core\WhitespaceExtension(),
			new Core\HeaderExtension(),
			new Core\ParagraphExtension(),
			new Core\LinkExtension(),
			new Core\HorizontalRuleExtension(),
			new Core\ListExtension(),
			new Core\BlockQuoteExtension(),
			new Core\InlineStyleExtension(),
			new Core\EscaperExtension(),

			new Ladb\EscapeHtmlExtension(),
			new Ladb\WhiteSpaceExtension(),
			new Ladb\UrlAutoLinkExtension(),
			new Ladb\MentionExtension(),
			new Ladb\InlineStyleExtension(),

		);
	}

}