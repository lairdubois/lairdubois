<?php

namespace Ladb\CoreBundle\Model;

trait HtmlBodiedTrait {

	use BodiedTrait;

	// HtmlBody /////

	public function setHtmlBody($htmlBody) {
		$this->htmlBody = $htmlBody;
		return $this;
	}

	public function getHtmlBody() {
		return $this->htmlBody;
	}

	// BodyExtract /////

	public function getBodyExtract() {
		return strip_tags(mb_strimwidth($this->getHtmlBody(), 0, 250, '[...]'));
	}

}