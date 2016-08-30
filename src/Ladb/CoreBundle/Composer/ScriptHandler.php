<?php

namespace Ladb\CoreBundle\Composer;

use Composer\Script\Event;
use Symfony\Component\Filesystem\Filesystem;

class ScriptHandler {

	public static function updateDirectoryStructure(Event $event) {

		$uploadsDir = 'uploads';
		$downloadsDir = 'downloads';
		$keysDir = 'keys';

		$fs = new Filesystem();
		$fs->mkdir(array( $uploadsDir, $downloadsDir, $keysDir ));

	}

}