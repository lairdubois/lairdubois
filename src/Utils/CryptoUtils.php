<?php

namespace App\Utils;

use Imagine\Gd\Font;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\Point;
use Imagine\Image\Palette\RGB;
use App\Entity\AbstractPublication;
use App\Entity\Core\Picture;
use App\Entity\Core\User;
use App\Entity\Core\View;
use App\Model\HiddableInterface;

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