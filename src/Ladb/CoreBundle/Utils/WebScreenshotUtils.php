<?php

namespace Ladb\CoreBundle\Utils;

use Doctrine\Common\Persistence\ObjectManager;
use Ladb\CoreBundle\Entity\Core\Picture;

class WebScreenshotUtils extends AbstractContainerAwareUtils {

	const NAME = 'ladb_core.web_screenshot_utils';

	/////

	public function captureToPicture($url, $width, $height, $clipWidth = 0, $clipHeight = 0) {

		// Process URL
		$url = trim(urldecode($url));
		if ($url == '') {
			return null;
		}

		if (!stristr($url, 'http://') and !stristr($url, 'https://')) {
			$url = 'http://' . $url;
		}

		$url = strip_tags($url);
		$url = str_replace(';', '', $url);
		$url = str_replace('"', '', $url);
		$url = str_replace('\'', '/', $url);
		$url = str_replace('<?', '', $url);
		$url = str_replace('<?', '', $url);
		$url = str_replace('\077', ' ', $url);
		$url = escapeshellcmd($url);

		$urlComponents = parse_url($url);
		if (!isset($urlComponents['host'])) {
			return null;
		}
		$host = $urlComponents['host'];

		// Create picture
		$picture = new Picture();
		$picture->setMasterPath(sha1(uniqid(mt_rand(), true)).'.jpg');
		$pictureFile = $picture->getAbsoluteMasterPath();

		// PHP-PhantomJS Capture /////

		$browserFactory = new \HeadlessChromium\BrowserFactory($this->getParameter('chromium'));

		// starts headless chrome
		$browser = $browserFactory->createBrowser(array(
			'windowSize' => array( $width, $height ),
		));

		// creates a new page and navigate to an url
		$page = $browser->createPage();
		$page->navigate($url)->waitForNavigation();

		// screenshot - Say "Cheese"! 😄
		$page->screenshot()->saveToFile($pictureFile);

		// bye
		$browser->close();

		/////

		if (is_file($pictureFile)) {

			list($width, $height) = getimagesize($pictureFile);
			$picture->setWidth($width);
			$picture->setHeight($height);
			$picture->setHeightRatio100($width > 0 ? $height / $width * 100 : 100);

			$this->getDoctrine()->getManager()->persist($picture);
			$this->getDoctrine()->getManager()->flush();

			return $picture;
		}
		return null;
	}

}