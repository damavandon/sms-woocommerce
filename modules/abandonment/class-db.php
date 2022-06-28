<?php

namespace Payamito\Woocommerce\Modules\Abandoned;

use Payamito_DB;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
if (!class_exists('DB')) {

    class DB
    {
        /**
         *  Create tables
         * @since 1.1.0
         * @return void
         */
        public static function create()
        {

            if (!function_exists(" dbDelta")) {
                include_once ABSPATH . 'wp-admin/includes/upgrade.php';
            }

            self::create_cart_abandonment_table();
            self::create_history_table();
        }

        /**
         *  return table name
         * @since 1.1.0
         * @return string
         * @param string $name
         */
        public static function table_name(string $name)
        {
            global $wpdb;
            $table_name = "";
            $tables = self::tables();
            if (in_array($name, $tables)) {
                $table_name = $wpdb->prefix . 'payamito_wc_' . $name;
            } else {
                $table_name = null;
            }
            return $table_name;
        }

        /**
         *  all tables of plugin
         * @since 1.1.0
         * @return array
         */
        private static function tables()
        {

            $tables = ['cart_abandonment', 'history'];
            $tables = (array) array_unique(apply_filters("payamito_wc_tables", $tables));
            return $tables;
        }

        /**
         *  Create  abandonment table
         * @since 1.1.0
         * @return void
         */
        public static function create_cart_abandonment_table()
        {
            global $wpdb;
            $name = self::table_name("cart_abandonment");

            $sql = "CREATE TABLE IF NOT EXISTS {$name} (
			id BIGINT(20) NOT NULL AUTO_INCREMENT,
			checkout_id int(11) NOT NULL,
            user_id int(11),
			phone VARCHAR(11),
			cart_contents LONGTEXT,
			cart_total DECIMAL(10,2),
			session_id VARCHAR(60) NOT NULL,
			other_fields LONGTEXT,
			order_status ENUM( 'normal','abandoned','completed','lost') NOT NULL DEFAULT 'normal',
			unsubscribed  boolean DEFAULT 0,
   			time DATETIME DEFAULT NULL,
            PRIMARY KEY(`id`)
		) ";

            $test= dbDelta($sql);
        }

        /**
         *  Create history table
         * @since 1.1.0
         * @return void
         */
        public static function create_history_table()
        {
            global $wpdb;

            $cart_abandonment_history_db  = self::table_name("history");

            $sql = "CREATE TABLE IF NOT EXISTS {$cart_abandonment_history_db} (
			 `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			 `template_id` BIGINT(20) NOT NULL,
			 `session_id` VARCHAR(60),
			 `scheduled_time` DATETIME,
             `queue`VARCHAR(500),
             PRIMARY KEY(`id`)
		)";

 dbDelta($sql);
        }

        public static function select( $table_name)
        {
            $selecteds = Payamito_DB::select($table_name,[],null,["*"],null);
            return $selecteds;
        }
    }
}
