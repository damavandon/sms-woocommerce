<?php

namespace Payamito\Woocommerce\Modules\Abandoned;

use WP_Term_Query;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
if (!class_exists("Helper")) {

    class  Helper
    {

        /**
         * Member Variable
         *
         * @var object instance
         */
        private static $instance;


        public static $coupen_id=null;

        /**
         *  Initiator
         */
        public static function get_instance()
        {
            if (!isset(self::$instance)) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public static function tags(bool $pack = false)
        {
            $tags = [
                [
                    'tag' => "customer_first_name",
                    'desc' => esc_html__('Customer firstname', 'payamito-woocommerce')
                ],
                [
                    'tag' => "customer_last_name",
                    'desc' => esc_html__('Customer lastname', 'payamito-woocommerce')
                ],
                [
                    'tag' => "customer_full_name",
                    'desc' => esc_html__('Customer fullname', 'payamito-woocommerce')
                ],

                [
                    'tag' => "coupon_code",
                    'desc' => esc_html__('Coupon code', 'payamito-woocommerce')
                ],
                [
                    'tag' => "checkout_url",
                    'desc' => esc_html__('Checkout url', 'payamito-woocommerce')
                ],
                [
                    'tag' => "site_url",
                    'desc' => esc_html__('Site url', 'payamito-woocommerce')
                ],
                [
                    'tag' => "site_title",
                    'desc' => esc_html__('Site title', 'payamito-woocommerce')
                ],
            ];

            if ($pack === true) {
                $packed = [];
                foreach ($tags as $tag) {
                    $packed[$tag['tag']] = $tag["desc"];
                }
                $packed = array_unique($packed);
                return $packed;
            }
            return $tags;
        }

        public static  function meta_keys()
        {
            global $wpdb;

            $final = array();
            $sql="SELECT DISTINCT `meta_key` FROM `{$wpdb->usermeta}`";
            $results = $wpdb->get_results($sql, ARRAY_A);
            if (is_array($results)) {
                foreach ($results as  $result) {
                    $final[$result['meta_key']] = $result['meta_key'];
                }
            }
            return  $final;
        }

        public static function prepare_template()
        {

            $templates = get_option("payamito_wc_abandonment");

            if ($templates['active'] != '1') {
                false;
            }
            return  $templates;
        }

        public static function set_units($unit)
        {
            switch (strtoupper($unit)) {
                case 'MINUTE':
                    return 60;
                    break;
                case 'HOUR':
                    return (60 * 60);
                    break;
                case 'DAY':
                    return (60 * 60 * 24);
                    break;
            }
        }
        public static function units($calculate_seconds = false)
        {
            if ($calculate_seconds == false) {
                return [
                    'MINUTE' => esc_html__('Minute', 'payamito-woocommerce'),
                    'HOUR' => esc_html__('Hour', 'payamito-woocommerce'),
                    'DAY' => esc_html__('Day', 'payamito-woocommerce')
                ];
            }
            if ($calculate_seconds == true) {
                return [
                    'MINUTE' => (60),
                    'HOUR' => (60 * 60),
                    'DAY' => (60 * 60 * 24),
                ];
            }
        }

        public static function prepare_abandonments($abandonments)
        {

            if (is_array($abandonments) && count($abandonments) != 0) {

                foreach ($abandonments as $index => $abandonmented) {
                    $abandonments[$index]['id'] = $abandonmented['id'];
                    $abandonments[$index]['cart_contents'] = unserialize($abandonmented['cart_contents']);
                    $abandonments[$index]['time'] = $abandonmented['time'];
                    $other_fields = unserialize($abandonmented['other_fields']);
                    $abandonments[$index]['other_fields'] = array_filter($other_fields, fn ($value) => !is_null($value) && $value !== '');
                }
                return  $abandonments;
            } else {
                return [];
            }
        }

        public static function  prepare_send_time($abandonment_time, $tempate_seconds)
        {
            $t = "+" . $tempate_seconds . ' ' . 'seconds' . ' ';
            $timestamp = strtotime($t . $abandonment_time);
            $r = date_i18n("Y-m-d H:i:s", $timestamp);
            return $r;
        }
        /**
         * Sanitize post array.
         *
         * @return array
         */
        public static function sanitize_post_data()
        {
            $input_post_values = array(
                'billing_company'     => array(
                    'default'  => '',
                    'sanitize' => FILTER_SANITIZE_STRING,
                ),
                'billing_email'               => array(
                    'default'  => '',
                    'sanitize' => FILTER_SANITIZE_EMAIL,
                ),
                'billing_address_1'   => array(
                    'default'  => '',
                    'sanitize' => FILTER_SANITIZE_STRING,
                ),
                'billing_address_2'   => array(
                    'default'  => '',
                    'sanitize' => FILTER_SANITIZE_STRING,
                ),
                'billing_state'       => array(
                    'default'  => '',
                    'sanitize' => FILTER_SANITIZE_STRING,
                ),
                'billing_postcode'    => array(
                    'default'  => '',
                    'sanitize' => FILTER_SANITIZE_STRING,
                ),
                'shipping_first_name' => array(
                    'default'  => '',
                    'sanitize' => FILTER_SANITIZE_STRING,
                ),
                'shipping_last_name'  => array(
                    'default'  => '',
                    'sanitize' => FILTER_SANITIZE_STRING,
                ),
                'shipping_company'    => array(
                    'default'  => '',
                    'sanitize' => FILTER_SANITIZE_STRING,
                ),
                'shipping_country'    => array(
                    'default'  => '',
                    'sanitize' => FILTER_SANITIZE_STRING,
                ),
                'shipping_address_1'  => array(
                    'default'  => '',
                    'sanitize' => FILTER_SANITIZE_STRING,
                ),
                'shipping_address_2'  => array(
                    'default'  => '',
                    'sanitize' => FILTER_SANITIZE_STRING,
                ),
                'shipping_city'       => array(
                    'default'  => '',
                    'sanitize' => FILTER_SANITIZE_STRING,
                ),
                'shipping_state'      => array(
                    'default'  => '',
                    'sanitize' => FILTER_SANITIZE_STRING,
                ),
                'shipping_postcode'   => array(
                    'default'  => '',
                    'sanitize' => FILTER_SANITIZE_STRING,
                ),
                'billing_order_comments'      => array(
                    'default'  => '',
                    'sanitize' => FILTER_SANITIZE_STRING,
                ),
                'billing_first_name'                => array(
                    'default'  => '',
                    'sanitize' => FILTER_SANITIZE_STRING,
                ),
                'billing_last_name'             => array(
                    'default'  => '',
                    'sanitize' => FILTER_SANITIZE_STRING,
                ),
                'billing_phone'               => array(
                    'default'  => '',
                    'sanitize' => FILTER_SANITIZE_STRING,
                ),
                'billing_country'             => array(
                    'default'  => '',
                    'sanitize' => FILTER_SANITIZE_STRING,
                ),
                'billing_city'                => array(
                    'default'  => '',
                    'sanitize' => FILTER_SANITIZE_STRING,
                ),
                'post_id'             => array(
                    'default'  => 0,
                    'sanitize' => FILTER_SANITIZE_NUMBER_INT,
                ),
            );

            $sanitized_post = array();
            foreach ($input_post_values as $key => $input_post_value) {

                if (isset($_POST[$key])) { //phpcs:ignore WordPress.Security.NonceVerification.Missing
                    $sanitized_post[$key] = filter_input(INPUT_POST, $key, $input_post_value['sanitize']);
                } else {
                    $sanitized_post[$key] = $input_post_value['default'];
                }
            }
            return $sanitized_post;
        }

        public static function prepare_body_sms($pattern, $abandonment, $options)
        {


            $coupon_active = false;

            if (isset($options['active']) &&  $options['active'] == '1') {
                $coupon_active = true;
            }

            foreach ($pattern['abandonment_pattern_variable'] as $index => $item) {
                $value = "";
                $tag = $item[0];

                $fields = $abandonment['other_fields'];

                switch ($tag) {
                    case 'customer_first_name':
                        $value = isset($fields['billing_first_name']) ? $fields['billing_first_name'] : "";
                        break;

                    case 'customer_last_name':
                        $value = isset($fields['billing_last_name']) ? $fields['billing_last_name'] : "";

                        break;
                    case 'customer_full_name':
                        $first_name = isset($fields['billing_first_name']) ? $fields['billing_first_name'] : "";
                        $last_name = isset($fields['billing_last_name'])  ? $fields['billing_last_name'] : "";
                        $value = $first_name . " " . $last_name;

                        break;
                    case 'checkout_url':
                        $value = self::get_checkout_url($abandonment['checkout_id'], ['session_id' => $abandonment['session_id']]);
                        break;
                    case 'site_title':
                        $value = get_bloginfo('name');
                        break;
                    case 'site_url':
                        $value =  get_home_url();
                        break;

                    case 'coupon_code':
                        $individual_use = $options['coupon_only_active'] == '1' ? 'yes' : "no";
                        $date_expires = $options['expire'];
                       $x=(int) $date_expires['width'];
                       $y= $date_expires['unit'];
                       $date_expires=$x*$y;
                        $coupen_data = [
                            'discount_type'       => $options['type'],
                            'description'         => __("This coupon sended by SMS", 'payamito-woocommerce'),
                            'coupon_amount'       => $options['amount'],
                            'individual_use'      => $individual_use,
                            'product_ids'         => implode(',', $options['products']),
                            'exclude_product_ids' => implode(',', $options['exclude_products']),
                            'product_categories'  => serialize($options['product_categories']),
                            'product_exclude_categories' => serialize($options['product_exclude_categories']),
                            'usage_limit_per_user' => $options['usage_limit_per_user'],
                            'limit_usage_to_x_items' => $options['limit_usage_to_x_items'],
                            'usage_limit'         => $options['usage_limit'],
                            'date_expires'        => self::coupen_expire($abandonment['time'], $date_expires),
                            'apply_before_tax'    => 'yes',
                            'free_shipping'       => false,
                            'coupon_generated_by' => __('Payamito woocommerce', 'payamito-woocommerce'),
                        ];
                        if ($coupon_active) {
                            $value = self::generate_coupon_code($coupen_data);
                        } else {
                            $value = "";
                        }
                        break;
                    default:
                        $value = "";
                        break;
                }
                $pattern['abandonment_pattern_variable'][$index][0] = $value;
            }

            $pattern_with_value = [];

            foreach ($pattern['abandonment_pattern_variable'] as $item) {
                $pattern_with_value[$item[1]] = $item[0];
            }
            return $pattern_with_value;
        }

        /**
         *  Generate new coupon code for abandoned cart.
         *
         * @param string $discount_type discount type.
         * @param float  $amount amount.
         * @param string $expiry expiry.
         * @param string $free_shipping is free shipping.
         * @param string $individual_use use coupon individual.
         */
        public static function generate_coupon_code(array $coupon_post_data)
        {

            $coupon_code = '';

            $coupon_code = wp_generate_password(8, false, false);

            $new_coupon_id = wp_insert_post(
                array(
                    'post_title'   => $coupon_code,
                    'post_content' => '',
                    'post_status'  => 'publish',
                    'post_author'  => 1,
                    'post_type'    => 'shop_coupon',
                )
            );
            self::$coupen_id = $new_coupon_id;

            foreach ($coupon_post_data as $key => $value) {
                update_post_meta($new_coupon_id, $key, $value);
            }

            return $coupon_code;
        }


        /**
         * Get checkout url.
         *
         * @param  integer $post_id    post id.
         * @param  string  $token_data token data.
         * @return string
         */
        public static function get_checkout_url($post_id, $token_data)
        {

            $token        =  self::generate_token((array) $token_data);
            $checkout_url = get_permalink($post_id) . '?wcf_ac_token=' . $token;
            return esc_url($checkout_url);
        }

        /**
         *  Geberate the token for the given data.
         *
         * @param array $data data.
         */
        public static function generate_token($data)
        {
            return urlencode(base64_encode(http_build_query($data)));
        }

        public static function coupen_expire($time, $frequency)
        {

            $send_time = Helper::prepare_send_time($time, $frequency);
            return $send_time;
        }

        public static function get_products()
        {
            $args = array(
                'post_type'      => 'product',
                'post_status'      => 'publish',
            );
            $products = get_posts($args);

            if (is_array($products) && count($products) != 0) {
                return $products;
            } else {
                false;
            }
        }
        public static function get_categories()
        {

            $term_args = array(
                'taxonomy'               => 'product_cat',
            );
            $term_query = new WP_Term_Query($term_args);
            $categories = $term_query->terms;
            return $categories;
        }

        /**
         * Sanitize phone number.
         * Allows only numbers and "+" (plus sign).
         *
         * @since 1.0.0
         * @param string $phone Phone number.
         * @return string
         */
        static  function  sanitize_phone_number($phone)
        {
            return preg_replace('/[^\d+]/', '', $phone);
        }
    }
}
