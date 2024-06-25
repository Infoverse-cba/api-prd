<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('generate_license_key')) {

    function generate_license_key($length = 25) {

        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $license_key = '';

        $characters_length = strlen($characters);

        for ($i = 0; $i < $length; $i++) {
            if ($i > 0 && $i % 5 == 0) {
                $license_key .= '-';
            }

            $license_key .= $characters[rand(0, $characters_length - 1)];
        }

        return $license_key;

    }
}
