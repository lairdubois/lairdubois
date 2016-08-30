<?php

namespace Ladb\CoreBundle\Parser\Ciconia\Extension\Ladb;

use Ciconia\Common\Text;
use Ciconia\Extension\ExtensionInterface;

class MentionExtension implements ExtensionInterface {

	public function register(\Ciconia\Markdown $markdown) {
		$markdown->on('inline', [$this, 'processMentions'], 20);
	}

	public function processMentions(Text $text) {
		$text->replace('/(^|[^a-zA-Z0-9.])@([A-Za-z]+[A-Za-z0-9]+)/', function (Text $w, Text $before, Text $username) {
			return $before.'[@'.$username.'](http://www.lairdubois.fr/'.strtolower($username).')';
		});
	}

	public function getName() {
		return 'ladbMention';
	}

}
