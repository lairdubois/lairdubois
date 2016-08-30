<?php

namespace Ladb\CoreBundle\Parser\Ciconia\Extension\Ladb;

use Ciconia\Common\Text;
use Ciconia\Extension\ExtensionInterface;

class EscapeHtmlExtension implements ExtensionInterface {

	public function register(\Ciconia\Markdown $markdown) {
		$markdown->on('initialize', [$this, 'escapeBrackets'], 0);
	}

	public function escapeBrackets(Text $text) {
		$text->replace('{<([^<>]+)}i', function (Text $w, Text $tag) {
			return '&lt;'.$tag;
		});
	}

	public function getName() {
		return 'ladbEscapeHtml';
	}

}
