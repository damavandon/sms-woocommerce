<?php

/**
 * @package   Payamito
 * @link      https://payamito.com/
 *
 * Plugin Name:       Payamito SMS Woocommerce
 * Plugin URI:        https://payamito.com/lib
 * Description:       Easily sms any activity that needs to be notified in WooCommerce with this plugin. Designed with ❤ in Payamito team
 * Version:           1.2.1
 * Author:            Payamito
 * Author URI:        https://payamito.com/
 * Text Domain:       payamito-woocommerce      
 * Domain Path:       /languages
 * Requires PHP: 7.0
 */

if (!defined('ABSPATH')) {
    die('direct access abort ');
}
if (!defined('PAYAMITO_WC_PLUGIN_FILE')) {

    define('PAYAMITO_WC_PLUGIN_FILE', __FILE__);
}

require_once __DIR__ . '/Define-constants.php';
require_once __DIR__ . '/includes/Autoloader.php';
require_once __DIR__ . '/includes/functions.php';

if (!class_exists('Payamito_Woocommerce')) {

    include_once PAYAMITO_WC_DIR . '/includes/payamito-woocommerce.php';
}

register_activation_hook(__FILE__, 'payamito_wc_activate');
register_deactivation_hook(__FILE__, 'payamito_wc_deactivate');

if (!function_exists("payamito_wc_set_locale")) {
    function payamito_wc_set_locale()
    {
        $dirname = str_replace('//', '/', wp_normalize_path(dirname(__FILE__)));
        $mo = $dirname . '/languages/' . 'payamito-woocommerce-' . get_locale() . '.mo';
        load_textdomain('payamito-woocommerce', $mo);
    }
}

payamito_wc_set_locale();
function payamito_wc_activate()
{
    do_action("payamito_wc_activate");

    require_once PAYAMITO_WC_DIR . '/includes/class-install.php';

    Payamito\Woocommerce\Install::install(PAYAMITO_WC_COR_VER, PAYAMITO_WC_PLUGIN_FILE, PAYAMITO_WC_COR_DIR);
    require_once payamito_wc_load_core() . '/includes/class-payamito-activator.php';
    Payamito_Activator::activate();
    Payamito_Woocommerce::modules_activate_or_deactivate("activate");
}

function payamito_wc_deactivate()
{
    do_action("payamito_wc_deactivate");
    require_once payamito_wc_load_core() . '/includes/class-payamito-deactivator.php';
    Payamito_Deactivator::deactivate();
    Payamito_Woocommerce::modules_activate_or_deactivate("deactivate");
}
/**
 * @return object|Payamito_Woocommerce|null
 */
function payamito_wc()
{
    return Payamito_Woocommerce::get_instance();
}
payamito_wc();
