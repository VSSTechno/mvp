<?php

namespace Store\Api;

require_once __DIR__ . '/../config.loader.php';

/**
 * do_request
 * Helper function for making requests to online store
 *
 * @global Array $config site wide configuration
 *
 * @param String $r      RESTful method to use (GET POST etc)
 * @param String $u      url to make the request to
 * @param Array  $a      the request data
 * @param Array  $p      parameters to send on request
 *
 * @return Array parsed request data
 *
 * @throws Exception on missing extension or request returning an error
 */
function do_request ($r, $u, array $a = array(), array $p = array()) {
    global $config;

    if (!isset($f)) $f = false;

    if (!function_exists('json_decode') || !function_exists('json_encode')) {
        throw new \Exception('PHP JSON extension required for the store');
    }

    if (!function_exists('curl_init')) {
        throw new \Exception('PHP CURL extension required for the store');
    }

    if ($config['printful_key'] === 'aaaaaaaa-bbbb-cccc:dddd-eeeeeeeeeeee') {
        throw new \Exception('Unconfigured store printful_key');
    }

    $url = 'https://api.theprintful.com/' . $u;
    if (!empty($p)) {
        $url .= '?' . http_build_query($p);
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERPWD, $config['printful_key']);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $r);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);

    if (!empty($a)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($a));
    }

    $res = curl_exec($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($errno) {
        throw new \Exception('do_request: ' . $error, $errno);
    }

    $res = json_decode($res, true);
    if (!isset($res['code']) || !isset($res['result'])) {
        throw new \Exception('do_request: Invalid API response');
    }

    $status = (int) $res['code'];

    if ($status < 200 || $status >= 300) {
        throw new \Exception($res['result'], $status);
    }

    return $res['result'];
}

/**
 * get_varients
 * Returns list of varients for the product
 *
 * @param String $i id of product
 *
 * @return Array list of variends
 */
function get_varients ($i) {
    if (!isset($i)) {
        throw new \Exception('get_varients: Missing required product id');
    }

    return do_request('GET', "products/variant/$i");
}