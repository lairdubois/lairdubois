<?php

use Doctrine\Common\Annotations\AnnotationRegistry;

$loader = require '/var/cache/ladb/vendor/autoload.php';

// intl
if (!function_exists('intl_get_error_code')) {
    require_once '/var/cache/ladb/vendor/symfony/symfony/src/Symfony/Component/Locale/Resources/stubs/functions.php';

    $loader->add('', '/var/cache/ladb/vendor/symfony/symfony/src/Symfony/Component/Locale/Resources/stubs');
}

AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

return $loader;
