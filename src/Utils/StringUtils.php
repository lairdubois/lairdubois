<?php

namespace App\Utils;

class StringUtils {

    public function canonicalize(string $string) {
        $oldLocale = setlocale(LC_ALL, '0');
        setlocale(LC_ALL, 'en_US.UTF-8');
        $result = str_replace('°', '', $string);
        $result = iconv('UTF-8', 'ASCII//TRANSLIT', $result);
        $result = preg_replace("/[^a-zA-Z0-9]/", '', $result);
        $result = strtolower($result);
        $result = trim($result, '-');
        setlocale(LC_ALL, $oldLocale);
        return $result;
    }

}