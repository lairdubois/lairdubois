<?php

namespace Ladb\CoreBundle\Parser\Ciconia\Renderer;

use Ciconia\Common\Tag;
use Ciconia\Renderer\HtmlRenderer;

class LadbHtmlRenderer extends HtmlRenderer {

	public function renderHeader($content, array $options = array()) {
		$options = $this->createResolver()
			->setRequired(array('level'))
			->setAllowedValues(array('level' => array(1, 2, 3, 4, 5, 6)))
			->resolve($options);

		return sprintf('<h%2$s>%1$s</h%2$s>', $content, min($options['level'] + 2, 6));
	}

	public function renderLink($content, array $options = array()) {
		$options = $this->createResolver()
			->setRequired(array('href'))
			->setDefaults(array('href' => '#', 'title' => ''))
			->setAllowedTypes('href', 'string')
			->setAllowedTypes('title', 'string')
			->resolve($options);

		$tag = new Tag('a');
		$tag->setText($content);
		$tag->setAttribute('href', $options['href']);
		$tag->setAttribute('target', '_blank');

		if ($options['title']) {
			$tag->setAttribute('title', $options['title']);
		}

		return $tag->render();
	}

}