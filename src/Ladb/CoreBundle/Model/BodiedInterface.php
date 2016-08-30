<?php

namespace Ladb\CoreBundle\Model;

interface BodiedInterface {

	// Body /////

	public function setBody($body);

	public function getBody();

	// HtmlBody /////

	public function setHtmlBody($htmlBody);

	public function getHtmlBody();

}
