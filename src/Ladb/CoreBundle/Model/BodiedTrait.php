<?php

namespace Ladb\CoreBundle\Model;

trait BodiedTrait {

	// Body /////

	public function setBody($body) {
		$this->body = $body;
		return $this;
	}

	public function getBody() {
		return $this->body;
	}

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
		return $this->getHtmlBody();
	}

}