<?php


use Payamito\Woocommerce\Modules\Base;
use Payamito\Woocommerce\Modules\Abandoned\Settings;


/**
 *  Recover Abandoned cart module
 * @package 
 * @since 1.1
 * 
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (!class_exists("Payamito_WC_Abandoned")) {

    final class Payamito_WC_Abandoned extends Base
    {

        public $modules = [];

        public function get_name()
        {
            return "abandoned";
        }

        public function __construct()
        {
            $this->init();
        }

        public function init()
        {
            $this->define();
            $this->include();
            $this->class();
        }

        protected function include()
        {
            #settings
            include_once PAYAMITO_WC_Module_DIR . '/abandonment/admin/class-settings.php';
            #db
            include_once PAYAMITO_WC_Module_DIR . '/abandonment/class-db.php';
            #abandonment
            include_once PAYAMITO_WC_Module_DIR . '/abandonment/class-abandonment.php';
            #tempalte
            include_once PAYAMITO_WC_Module_DIR . '/abandonment/class-template.php';
            #helper
            include_once PAYAMITO_WC_Module_DIR . '/abandonment/class-helper.php';
        }

        public function define()
        {
            if (!defined('PAYAMITO_WC_ABANDONED')) {

                define('PAYAMITO_WC_ABANDONED', "1.0.0");
            }
        }
        protected function class()
        {
            if(is_admin()  ){

               $settings=new Settings;
               $settings->add_settings();
            }

            Payamito\Woocommerce\Modules\Abandoned\Abandonment::get_instance();
            
        }

        public function activate()
        {

            Payamito\Woocommerce\Modules\Abandoned\DB::create();

            // Schedule an action if it's not already scheduled.
          

            do_action("payamito_wc_abandoned_activate");
        }

        public function deactivate()
        {
            wp_clear_scheduled_hook('payamito_wc_abandoned');

            do_action("payamito_wc_abandoned_deactivate");
        }
    }
}

function payamito_wc_abandoned()
{

    return  Payamito_WC_Abandoned::get_instance();
}
