<?php

namespace Ladb\CoreBundle\Utils;

use Imagine\Gd\Font;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\Point;
use Imagine\Image\Palette\RGB;
use Ladb\CoreBundle\Entity\AbstractPublication;
use Ladb\CoreBundle\Entity\Core\Picture;
use Ladb\CoreBundle\Entity\Core\User;
use Ladb\CoreBundle\Entity\Core\View;
use Ladb\CoreBundle\Model\HiddableInterface;

class CryptoUtils extends AbstractContainerAwareUtils {

	const NAME = 'ladb_core.crypto_utils';

	/////

	public function encryptString($data) {
		return base64_encode(openssl_encrypt($data, 'aes-256-ctr', $this->getParameter('secret'), 0, '1234567812345678'));
	}

	public function decryptString($data) {
		return openssl_decrypt(base64_decode($data), 'aes-256-ctr', $this->getParameter('secret'), 0, '1234567812345678');
	}

}