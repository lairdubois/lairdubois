<?php

namespace App\Model;

trait BodiedTrait {

	// Body /////

	public function setBody($body) {
		$this->body = $body;
		return $this;
	}

	public function getBody() {
		return $this->body;
	}

	// BodyExtract /////

	public function getBodyExtract() {
		return strip_tags(mb_strimwidth($this->getHtmlBody(), 0, 250, '[...]'));
	}

}