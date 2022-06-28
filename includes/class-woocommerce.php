<?php

namespace Payamito\Woocommerce;

use Payamito\Woocommerce\Funtions\Functions;
use WC_Order;


if (!defined('ABSPATH')) {

    die('Invalid request.');
}

if (!class_exists('P_Woocommerce')) :

    class P_Woocommerce
    {
        private static $_instance;

        public $order;

        public $order_id;

        public $status;

        public $options;

        // If the single instance hasn't been set, set it now.
        public static function get_instance()
        {
            if (!self::$_instance) {

                self::$_instance = new self();
            }
            return self::$_instance;
        }

        public function __construct()
        {
            $this->hooks();
        }

        public function hooks()
        {
            add_action('plugins_loaded', [$this, 'add_hooks']);
        }

        public function add_hooks()
        {
            if (!class_exists('WooCommerce')) {
                return;
            }
            add_action('woocommerce_order_status_changed', array($this, 'order_status_changed'), 99, 3);
            add_action('woocommerce_new_order', array($this, 'new_order'), 99, 3);
        }

        public function init_send($action)
        {
            $this->options = Functions::option_preparation(get_option('payamito_woocommerce_options'));

            if (!is_array($this->options) || $this->options['active'] == false) {
                return;
            }

            $this->admin($action, $this->options['administrator']);

            $this->vendor($action, $this->options['vendor']);

            $this->user($action,  $this->options['user']);
        }

        public function order_status_changed($order_id, $old_status, $new_status)
        {
            if ($old_status == $new_status) {
                return;
            }
            $this->order_id = $order_id;
            $this->status = $new_status;

            $this->order = new WC_Order($this->order_id);
            $this->init_send($new_status);
        }

        public function new_order($order_id, $order)
        {
            $this->order_id = $order_id;
            $this->order = $order;
            $this->status = 'pending';

            $this->order = new WC_Order($this->order_id);
            $this->init_send('pending');
        }


        public  function set_message($action, $option)
        {

            if ($option[$action . '_pattern_active']) {

                $pattern = $option[$action . '_pattern'];

                $pattern_id = trim($option[$action . "_pattern_id"]);

                if (is_array($pattern) && count($pattern) > 0 && is_numeric($pattern_id)) {

                    return array('type' => 1, 'message' => $pattern, 'pattern_id' => $pattern_id);
                } else {

                    return null;
                }
            } else {

                $text = trim($option[$action . '_text']);

                if ($text == '') {

                    return null;
                } else {
                    $message = $this->set_value($text);
                    return array('type' => 2, 'message' => $message);
                }
            }
        }

        public function set_value($text)
        {
            $tags = Functions::get_tags();

            $value = [];

            foreach ($tags as  $tag) {

                array_push($value, $this->get_tag_value($tag['tag']));
            }

            $message = str_replace($tags, $value, $text);

            return $message;
        }

        public  function ready_send($action, $option)
        {
            if (!$option['active']) {

                return false;
            }

            if (!$option[$action]) {

                return false;
            }

            $message = $this->set_message($action, $option);

            if (is_null($message)) {

                return false;
            }

            return $message;
        }

        public  function set_pattern($pattern)
        {
            $send_pattern = [];

            foreach ($pattern as  $item) {

                $send_pattern[$item[1]] = Functions::english_number_to_pershian($this->get_tag_value($item[0]));
            }
            return $send_pattern;
        }


        public function admin($action, $option)
        {
            $message = $this->ready_send($action, $option);

            if ($message === false) {

                return;
            }
            $phone_numbers =array_column($option['phone_number'],'admin_phone_number');
            $phone_number=array_unique($phone_numbers);

            if (count($phone_numbers) == '0') {

                return;
            }

            foreach ($phone_numbers as $phone_number) {

                $this->start_send($message, $phone_number,);
            }
        }

        public function vendor($action, $option)
        {
            $message = $this->ready_send($action, $option);

            if ($message === false) {

                return;
            }
            $phone_number = get_user_meta(get_current_user_id(), $option['meta_key'], true);

            if ($phone_number == false || $phone_number == '') {

                return;
            }

            $this->start_send($message, $phone_number);
        }


        public function user($action, $option)
        {
            $message = $this->ready_send($action, $option);

            if ($message === false) {

                return;
            }
            $customer_id = $this->order->get_user_id();
            $phone_number = get_user_meta($customer_id, $option['meta_key'], true);

            if ($phone_number == false || $phone_number == '') {

                return;
            }

            $this->start_send($message, $phone_number);
        }

        public function start_send($message, $phone_number)
        {
            switch ($message['type']) {

                case 1:
                    $send_pattern = $this->set_pattern($message['message']);

                    payamito_wc()->send->Send_pattern($phone_number, $send_pattern, $message['pattern_id']);
                    break;

                case 2:

                    payamito_wc()->send->Send($phone_number, $message['message']);
                    break;
            }
        }
        /**
         *  get tag value 
         *@param string,array tag,vendor_items_array
         * @since  1.0.0
         * @return array
         */
        public function get_tag_value($tag, $vendor_items_array = array())
        {
            $price    = strip_tags($this->order_prop($this->order, 'formatted_order_total', array('', false)));
            $price    = html_entity_decode($price);

            $country = WC()->countries;

            $post = get_post($this->order_id);

            $payment_method  = $this->order_prop($this->order, 'payment_method');

            $payment_gateways = WC()->payment_gateways->payment_gateways();

            $all_product_list = $this->all_items($this->order);

            $all_items        = !empty($all_product_list['items']) ? $all_product_list['items'] : array();

            $all_items_qty    = !empty($all_product_list['items_qty']) ? $all_product_list['items_qty'] : array();

            $vendor_items       = !empty($vendor_items_array['items']) ? $vendor_items_array['items'] : array();

            $vendor_items_qty   = !empty($vendor_items_array['items_qty']) ? $vendor_items_array['items_qty'] : array();

            $vendor_price       = !empty($vendor_items_array['price']) ? array_sum((array) $vendor_items_array['price']) : 0;
            $vendor_price       = strip_tags(wc_price($vendor_price));
            $vendor_price       = !empty($vendor_items_array['price']) ? array_sum((array) $vendor_items_array['price']) : 0;
            switch ($tag) {
                case 'all_product_list':

                case '{all_product_list}':
                    return  $this->all_items($this->order);
                    break;
                case 'vendor_price':
                case '{vendor_price}':
                    return  strip_tags(wc_price($vendor_price));
                    break;
                case 'payment_method':

                case '{payment_method}':
                    $payment_gateways = array();
                    if (WC()->payment_gateways()) {
                    }
                    return $payment_method  = (isset($payment_gateways[$payment_method]) ? esc_html($payment_gateways[$payment_method]->get_title()) : esc_html($payment_method));

                    break;
                case 'shipping_method':

                case '{shipping_method}':
                    return esc_html($this->order_prop($this->order, 'shipping_method'));
                    break;
                case 'billing_country':

                case '{billing_country}':
                    return  $bill_country = (isset($country->countries[$this->order_prop($this->order, 'billing_country')])) ? $country->countries[$this->order_prop($this->order, 'billing_country')] : $this->order_prop($this->order, 'billing_country');
                    break;
                case 'billing_state':

                case '{billing_state}':
                    return $bill_state   = ($this->order_prop($this->order, 'billing_country') && $this->order_prop($this->order, 'billing_state') && isset($country->states[$this->order_prop($this->order, 'billing_country')][$this->order_prop($this->order, 'billing_state')])) ? $country->states[$this->order_prop($this->order, 'billing_country')][$this->order_prop($this->order, 'billing_state')] : $this->order_prop($this->order, 'billing_state');

                    break;
                case 'shipping_country':

                case '{shipping_country}':
                    return $shipp_country = (isset($country->countries[$this->order_prop($this->order, 'shipping_country')])) ? $country->countries[$this->order_prop($this->order, 'shipping_country')] : $this->order_prop($this->order, 'shipping_country');

                    break;
                case 'shipping_state':

                case '{shipping_state}':
                    return $shipp_state   = ($this->order_prop($this->order, 'shipping_country') && $this->order_prop($this->order, 'shipping_state') && isset($country->states[$this->order_prop($this->order, 'shipping_country')][$this->order_prop($this->order, 'shipping_state')])) ? $country->states[$this->order_prop($this->order, 'shipping_country')][$this->order_prop($this->order, 'shipping_state')] : $this->order_prop($this->order, 'shipping_state');

                    break;
                case 'b_first_name':

                case '{b_first_name}':
                    return $this->order_prop($this->order, 'billing_first_name');
                    break;
                case 'b_last_name':

                case '{b_last_name}':
                    return $this->order_prop($this->order, 'billing_last_name');
                    break;
                case 'b_company':

                case '{b_company}':
                    return $this->order_prop($this->order, 'billing_company');
                    break;
                case 'b_address_1':

                case '{b_address_1}':
                    return $this->order_prop($this->order, 'billing_address_1');
                    break;
                case 'b_address_2':

                case '{b_address_2}':
                    return $this->order_prop($this->order, 'billing_address_2');
                    break;
                case 'b_state':

                case '{b_state}':
                    return   $bill_state   = ($this->order_prop($this->order, 'billing_country') && $this->order_prop($this->order, 'billing_state') && isset($country->states[$this->order_prop($this->order, 'billing_country')][$this->order_prop($this->order, 'billing_state')])) ? $country->states[$this->order_prop($this->order, 'billing_country')][$this->order_prop($this->order, 'billing_state')] : $this->order_prop($this->order, 'billing_state');
                    break;
                case 'b_city':

                case '{b_city}':
                    return $this->order_prop($this->order, 'billing_city');
                    break;
                case 'b_country':

                case '{b_country}':
                    return  $bill_country = (isset($country->countries[$this->order_prop($this->order, 'billing_country')])) ? $country->countries[$this->order_prop($this->order, 'billing_country')] : $this->order_prop($this->order, 'billing_country');
                    break;
                case 'b_postcode':

                case '{b_postcode}':
                    return $this->order_prop($this->order, 'billing_postcode');
                    break;
                case 'sh_first_name':

                case '{sh_first_name}':
                    return $this->order_prop($this->order, 'shipping_first_name');
                    break;
                case 'sh_last_name':

                case '{sh_last_name}':
                    return $this->order_prop($this->order, 'shipping_last_name');
                    break;
                case 'sh_company':

                case '{sh_company}':
                    return $this->order_prop($this->order, 'shipping_company');
                    break;
                case 'sh_address_1':

                case '{sh_address_1}':
                    return $this->order_prop($this->order, 'shipping_address_1');
                    break;
                case 'sh_address_2':

                case '{sh_address_2}':
                    return $this->order_prop($this->order, 'shipping_address_2');
                    break;
                case 'sh_state':

                case '{sh_state}':
                    return  $shipp_state   = ($this->order_prop($this->order, 'shipping_country') && $this->order_prop($this->order, 'shipping_state') && isset($country->states[$this->order_prop($this->order, 'shipping_country')][$this->order_prop($this->order, 'shipping_state')])) ? $country->states[$this->order_prop($this->order, 'shipping_country')][$this->order_prop($this->order, 'shipping_state')] : $this->order_prop($this->order, 'shipping_state');

                    break;
                case 'sh_city':

                case '{sh_city}':
                    return $this->order_prop($this->order, 'shipping_city');
                    break;
                case 'sh_postcode':

                case '{sh_postcode}':
                    return $this->order_prop($this->order, 'shipping_postcode');
                    break;
                case 'sh_country':

                case '{sh_country}':
                    return  $shipp_country = (isset($country->countries[$this->order_prop($this->order, 'shipping_country')])) ? $country->countries[$this->order_prop($this->order, 'shipping_country')] : $this->order_prop($this->order, 'shipping_country');
                    break;
                case 'phone':

                case '{phone}':
                    return get_post_meta($this->order_id, '_billing_phone', true);
                    break;
                case 'mobile':

                case '{mobile}':
                    return $this->customer_mobile($this->order_id);
                    break;
                case 'email':

                case '{email}':
                    return $this->order_prop($this->order, 'billing_email');
                    break;
                case 'order_id':

                case '{order_id}':
                    return $this->order_prop($this->order, 'order_number');
                    break;
                case 'post_id':

                case '{post_id}':
                    return $this->order_id;
                    break;
                case 'price':

                case '{price}':
                    return  $price;
                    break;
                case 'date':

                case '{date}':
                    return $this->order_date($this->order);
                    break;
                case 'status':

                case '{status}':
                    return $this->status_name($this->status, true);
                    break;
                case 'all_items':

                case '{all_items}':
                    return implode(' - ', $all_items);
                    break;
                case 'all_items_qty':

                case '{all_items_qty}':
                    return  implode(' - ', $all_items_qty);
                    break;
                case 'count_items':

                case '{count_items}':
                    return count($all_items);
                    break;
                case 'vendor_items':

                case '{vendor_items}':
                    return  implode(' - ', $vendor_items);
                    break;
                case 'vendor_items_qty':

                case '{vendor_items_qty}':
                    return implode(' - ', $vendor_items_qty);
                    break;
                case 'count_vendor_items':

                case '{count_vendor_items}':
                    return count($vendor_items);
                    break;
                case 'transaction_id':

                case '{transaction_id}':
                    return  get_post_meta($this->order_id, '_transaction_id', true);
                    break;
                case 'description':

                case '{description}':
                    return  nl2br(esc_html($post->post_excerpt));
                    break;
            }
        }

        public function order_prop($order, $prop, $args = array())
        {
            $method = 'get_' . $prop;

            if (method_exists($order, $method)) {
                if (empty($args) || !is_array($args)) {
                    return $order->$method();
                } else {
                    return call_user_func_array(array($order, $method), $args);
                }
            }

            return !empty($order->{$prop}) ? $order->{$prop} : '';
        }

        /**
         *set order order id for tag value.
         *@param array order
         * @since  1.0.0
         * @return array
         */
        public function order_id($order)
        {
            return $this->order_prop($order, 'id');
        }

        /**
         *  all items of order
         *@param array  order
         * @since  1.0.0
         * @return array
         */
        public function all_items($order)
        {

            $order_products = $this->prodcut_lists($order);

            $items = array();
            foreach ((array) $order_products as $item_datas) {
                foreach ((array) $item_datas as $item_data) {
                    $this->prepare_items($items, $item_data);
                }
            }

            $items['product_ids'] = array_keys($order_products);

            return $items;
        }
        public function customer_mobile($order_id)
        {
            return get_post_meta($order_id, '_' . $this->customer_mobile_meta(), true);
        }
        public function customer_mobile_meta()
        {
            return apply_filters('pwoosms_mobile_meta', 'billing_phone');
        }

        /**
         *  set order date for tag value.
         *@param array order
         * @since  1.0.0
         * @return array
         */
        public function order_date($order)
        {

            $order_date = $this->order_prop($order, 'date_paid');
            if (empty($order_date)) {
                $order_date = $this->order_prop($order, 'date_created');
            }
            if (empty($order_date)) {
                $order_date = $this->order_prop($order, 'date_modified');
            }
            if (!empty($order_date)) {
                if (method_exists($order_date, 'getOffsetTimestamp')) {
                    $order_date = gmdate('Y-m-d H:i:s', $order_date->getOffsetTimestamp());
                }
            } else {
                $order_date = date_i18n('Y-m-d H:i:s');
            }

            return $this->jalali_date_converter($order_date);
        }

        /**
         *  convert date
         *@param array date_time
         * @since  1.0.0
         * @return array
         */
        public function jalali_date_converter($date_time)
        {
            global $pwoo_general_options;

            if (!isset($pwoo_general_options['jalali_date']) || $pwoo_general_options['jalali_date'] == '0') {
                return $date_time;
            }
            if (empty($date_time)) {
                return '';
            }

            $_date_time = explode(' ', $date_time);
            $date       = !empty($_date_time[0]) ? explode('-', $_date_time[0], 3) : '';
            $time       = !empty($_date_time[1]) ? $_date_time[1] : '';

            if (count($date) != 3 || $date[0] < 2000) {
                return $date_time;
            }

            list($year, $month, $day) = $date;

            $date = Functions::jalali_converter($year, $month, $day, '/') . ' - ' . $time;

            return trim(trim($date), '- ');
        }

        /**
         *  set prodcut lists for tag value.
         *@param array,string order,field
         * @since  1.0.0
         * @return array
         */
        public function prodcut_lists($order, $field = '')
        {

            $products = array();
            $fields   = array();

            foreach ((array) $this->order_prop($order, 'items') as $product) {

                $parent_product_id = !empty($product['product_id']) ? $product['product_id'] : $this->product_id($product);
                $product_id        = $this->product_prop($product, 'variation_id');
                $product_id        = !empty($product_id) ? $product_id : $parent_product_id;

                $item = array(
                    'id'         => $product_id,
                    'product_id' => $parent_product_id,
                    'qty'        => !empty($product['qty']) ? $product['qty'] : 0,
                    'total'      => !empty($product['total']) ? $product['total'] : 0,
                );

                if (!empty($field) && isset($item[$field])) {
                    $fields[] = $item[$field];
                }

                $products[$parent_product_id][] = $item;
            }

            if (!empty($field)) {
                $products[$field] = $fields;
            }

            return $products;
        }


        public function prepare_items(&$items, $item_data)
        {

            if (!empty($item_data['id'])) {
                $title                = $this->variable_product_title($item_data['id']);
                $items['items'][]     = $title;
                $items['items_qty'][] = $title . ' (' . $item_data['qty'] . ')';
                $items['price'][]     = $item_data['total'];
            }
        }

        /**
         * prop .method
         *@param object,string product,prop
         * @since  1.0.0
         * @return mixed
         */
        public function product_prop($product, $prop)
        {
            $method = 'get_' . $prop;

            return method_exists($product, $method) ? $product->$method() : (!empty($product->{$prop}) ? $product->{$prop} : '');
        }

        public function variable_product_title($product)
        {

            $product_id = $this->product_id($product);

            if (!is_object($product)) {
                $product = wc_get_product($product);
            }

            $attributes = $this->product_prop($product, 'variation_attributes');
            $parent_id  = $this->product_prop($product, 'parent_id');

            if (!empty($attributes) && !empty($parent_id)) {

                $parent = wc_get_product($parent_id);

                $variation_attributes = $this->product_prop($parent, 'variation_attributes');

                $variable_title = array();
                foreach ((array) $attributes as $attribute_name => $options) {

                    $attribute_name = str_ireplace('attribute_', '', $attribute_name);

                    foreach ((array) $variation_attributes as $key => $value) {
                        $key = str_ireplace('attribute_', '', $key);

                        if (sanitize_title($key) == sanitize_title($attribute_name)) {
                            $attribute_name = $key;
                            break;
                        }
                    }

                    if (!empty($options) && substr(strtolower($attribute_name), 0, 3) !== 'pa_') {
                        $variable_title[] = $attribute_name . ':' . $options;
                    }
                }

                $product_title = get_the_title($parent_id);

                if (!empty($variable_title)) {
                    $product_title .= ' (' . implode(' - ', $variable_title) . ')';
                }
            } else {
                $product_title = get_the_title($product_id);
            }

            return html_entity_decode(urldecode($product_title));
        }

        /**
         * set product id for tag value.
         *@param mixed product
         * @since  1.0.0
         * @return string|int
         */
        public function product_id($product = '')
        {
            if (empty($product)) {

                $product_id = get_the_ID();
            } else if (is_numeric($product)) {

                $product_id = $product;
            } else if (is_object($product)) {

                $product_id = $this->product_prop($product, 'id');
            } else {

                $product_id = false;
            }

            return $product_id;
        }
        /**
         * set status name for tag value.
         *@param string,mixed status,pending
         * @since  1.0.0
         * @return string
         */
        public function status_name($status, $pending = false)
        {
            $status = wc_get_order_status_name($status);

            if ($status == 'created') {

                $pending_label = _x('Pending payment', 'Order status', 'woocommerce');
                $status        = $pending ? $pending_label : $pending_label . ' (بلافاصله بعد از ثبت سفارش)';
            }

            return $status;
        }
    }
endif;
