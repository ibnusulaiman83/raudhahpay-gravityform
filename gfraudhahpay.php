<?php
/**
 * Plugin Name: Raudhah Pay for Gravity Forms
 * Plugin URI:
 * Description: Raudhah Pay Payment Gateway | <a href="https://cloud.raudhahpay.com/user/register" target="_blank">Sign up Now</a>.
 * Author: Raudhah Pay
 * Author URI:
 * Version: 0.1.0.2
 * Requires PHP: 7.0
 * Requires at least: 4.6
 * License: GPLv3
 * Text Domain: gfraudhahpay
 * Domain Path: /languages/
 * WC requires at least: 3.0
 */

define('GF_RAUDHAHPAY_VERSION', '0.2.0.0');

add_action('gform_loaded', ['RaudhahpayGFSBootstrap', 'load'], 5);

class RaudhahpayGFSBootstrap
{
    public static function load()
    {
        if (! method_exists('GFForms', 'include_payment_addon_framework')) {
            return;
        }

        include_once 'includes/RaudhahPayGravityConnect.php';
        include_once 'includes/RaudhahPayGravityApi.php';
        include_once 'includes/GfNoticeHelper.php';
        include_once 'class-gf-raudhahpay.php';

        GFAddOn::register('GFRaudhahPay');
    }
}

function gf_raudhahpay()
{
    return GFRaudhahPay::getInstance();
}

add_filter('gform_phone_formats', 'my_phone_format');
function my_phone_format($phone_formats) {
    $phone_formats['my'] = array(
        'label'       => 'Malaysia',
        'mask'        => false,
        'regex'       => '/^(601)[0-46-9]*[0-9]{7,8}$/',
        'instruction' => 'The mobile number must contain country code without + sign. Example 6013xxxxxxx',
    );

    return $phone_formats;
}

/*
 * 1. Setting timeout value for cURL.
 * 2. Setting timeout for the HTTP request
 * 3. Setting timeout in HTTP request args
 * Using a high value for priority to ensure the function runs
 * after any other added to the same action hook.
 * Ideally for production should not be more than 30s
 */
add_action('http_api_curl', 'override_curl_timeout', 9999, 1);
function override_curl_timeout($handle) {
    curl_setopt( $handle, CURLOPT_CONNECTTIMEOUT, 15 );
    curl_setopt( $handle, CURLOPT_TIMEOUT, 15 );
}

add_filter('http_request_timeout', 'override_http_request_timeout', 9999);
function override_http_request_timeout( $timeout_value ) {
    return 15;
}

add_filter('http_request_args', 'override_http_request_args', 9999, 1);
function override_http_request_args($r) {
    $r['timeout'] = 15;
    return $r;
}
