<?php
/**
 *Woocommerce Customer  Add Field .
 *
 * @package  Payamito
 * @category Integration
 */

namespace Payamito\Woocommerce\Field;



use Payamito_OTP;
use Payamito\Woocommerce\Funtions\Functions;


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('Field')) :

    class Field
    {

        protected static $instance = null;

        private $OTP = null;
 /**
         * Start the Class when called
         *
         * @since   1.0.0
         */
        public static function get_instance()
        {
            // If the single instance hasn't been set, set it now.
            if (null == self::$instance) {

                self::$instance = new self;
            }
            return self::$instance;
        }

        function __construct()
        {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            };
            
            if (self::check_once()) {

                global $pwoo_otp_options;

                if ($pwoo_otp_options==false || $pwoo_otp_options['active'] == false) {
                    return;
                }

                add_action('woocommerce_after_order_notes',[$this, 'fields']);

                add_action('woocommerce_after_checkout_validation',[$this,'check_field'],10,2);

                add_action("woocommerce_checkout_update_user_meta", [$this, "submit"],10,2);
            }

            add_action('wp_enqueue_scripts', [$this, 'wp_enqueue_scripts']);

            add_action('wp_ajax_payamito_woocommerce', [$this, 'ajax']);

            add_action('wp_ajax_nopriv_payamito_woocommerce', [$this, 'ajax']);
        }

        public function check_field($data,$errors)
        {
           global $pwoo_otp_options;
           if($pwoo_otp_options['active']==false){
               return;
           }

            $phone_number=$_REQUEST['phone_number']?sanitize_text_field($_REQUEST['phone_number']):'';

            $otp=$_REQUEST['otp']?sanitize_text_field($_REQUEST['otp']):'';

            $otp=payamito_to_english_number($otp);

            $phone_number=payamito_to_english_number($phone_number);

            $this->check_phone_number($phone_number,$errors);

            $this->check_otp($phone_number,$otp,$errors);
           
            return;
           
        }
        public function check_phone_number($phone_number,$errors){
            global $pwoo_otp_options;
            if($pwoo_otp_options['force_enter']==false || empty($pwoo_otp_options['force_enter'])){

                return;
            }
            if(  empty($phone_number) || !payamito_verify_moblie_number($phone_number)){

                return  $errors->add('validation', __( 'Please Enter a valide phone number','payamito-woocommerce' ));  
              }
        }
        public function check_otp($phone_number,$otp,$errors){

            global $pwoo_otp_options;
            if($pwoo_otp_options['force_otp']==false || empty($pwoo_otp_options['force_otp'])){

                return;
            }
            if(empty($otp) || !Payamito_OTP::payamito_validation_session($phone_number,$otp)){

                return  $errors->add('validation', __( 'We cannot validate your phone number.please enter currect otp','payamito-woocommerce' ));  
  
              }
        }

       
        /**
         * enqueue scripts
         *
         * @since   1.0.0
         */
        public function wp_enqueue_scripts()
        {
            wp_enqueue_script("payamito-woocommerce-front-app-js",  PAYAMITO_WC_URL . "assets/js/app.js", array('jquery'), false, true);

            wp_enqueue_script("payamito-woocommerce-front-notification-js",  PAYAMITO_WC_URL . "assets/js/notification.js", array('jquery'), false, true);

            wp_localize_script("payamito-woocommerce-front-notification-js", "general", array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                "OTP_Success" => __("Send OTP success", "payamito-woocommerce"),
                "OTP_Fail" => __("Send OTP failed", "payamito-woocommerce"),
                'Send' => __("Send request failed please contact with support team ", "payamito-woocommerce"),
                'OTP_Correct' => __("OTP is wrong", "payamito-woocommerce"),
                'invalid' => __("phone_number number is incorrct", "payamito-woocommerce"),
                'error' => __("Error", "payamito-woocommerce"),
                'success' => __("Success", "payamito-woocommerce"),
                "warning" => __("Warning", "payamito-woocommerce"),
                'enter' => __('Enter OTP number ', 'payamito-woocommerce'),
                'second' => __('Second', 'payamito-woocommerce'),
            ));

            wp_enqueue_script("payamito-woocommerce-front-spinner-js",  PAYAMITO_WC_URL . "assets/js/spinner.js", array('jquery'), false, true);

            ////////style///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            wp_enqueue_style("payamito-woocommerce-front-app-css",  PAYAMITO_WC_URL . "assets/css/app.css");

            wp_enqueue_style("payamito-woocommerce-front-notification-css",  PAYAMITO_WC_URL . "assets/css/notification.css");

            wp_enqueue_style("payamito-woocommerce-front-spinner-css",  PAYAMITO_WC_URL . "assets/css/spinner.css");

            wp_enqueue_style("payamito-woocommerce-front-otp-css",  PAYAMITO_WC_URL . "assets/css/otp-error.css");
        }

        /**
         * handling OTP ajax request
         *
         * @since 1.0.0
         *
         * @return void
         */

        public function ajax()
        {

            if (!payamito_wc()->functions::is_request("ajax")) {
                wp_die();
            }
            $phone_number = payamito_to_english_number(sanitize_text_field($_REQUEST['phone_number']));

            if (
                !isset($phone_number) ||
                empty($phone_number)  ||
                !is_numeric($phone_number)
            ) {
                wp_die();
            }
            $this->phone_number_confirmation($phone_number);
        }

        /**
         * phone_number number validation and sending SMS
         *
         * @since 1.0.0
         *
         * @return void
         */
        public  function phone_number_confirmation($phone_number)
        {
            global $pwoo_otp_options;

            $options = $pwoo_otp_options;

            if ($options['force_otp' != '1']) {
                return;
            }
            if (!payamito_verify_moblie_number($phone_number)) {

                $this->ajax_response(-1, self::message(0));
            }
            Payamito_OTP::payamito_resent_time_check($phone_number,$pwoo_otp_options['again_send_time_otp']);

            if ($options['pattern_active'] == true) {

                $pattern_id = trim($options['pattern_id']);

                if (empty($pattern_id)) {

                    return;
                }

                if (!is_array($options['pattern']) || count($options['pattern']) == 0) {

                    return;
                }
                $pattern = $this->set_otp_pattern($options['pattern'], $options['number_of_code_otp']);

                $result = payamito_wc()->send->Send_pattern($phone_number, $pattern, $pattern_id);

                if ($result['result'] === true && !empty($this->OTP) ) {
                    $phone_number=(string)$phone_number;
                    $OTP=(string)$this->OTP;

                    Payamito_OTP::payamito_set_session($phone_number, $OTP);

                    return  $this->ajax_response(1, self::message(1));
                } else {

                    return  $this->ajax_response(-1, $result['message']);
                }
            } else {

                $messages = trim($options['text']);

                if (empty($messages)) {
                    return;
                }
                $messages_value = $this->set_value($messages, $options['number_of_code_otp']);

                $result = payamito_wc()->send->Send($phone_number, $messages_value);

                if ($result === true) {

                    Payamito_OTP::payamito_set_session($phone_number, $this->OTP);
                    return  $this->ajax_response(1, self::message(1));
                } else {

                    return  $this->ajax_response(-1, $result['message']);
                }
            }
        }
       
        public function set_otp_pattern($pattern, $count = 4)
        {
            $send_pattern = [];
            foreach ($pattern as $item) {

                switch ($item['opt_tags']) {
                    case 'OTP':
                    case '{OTP}':
                        $this->OTP = Payamito_OTP::payamito_generate_otp($count);
                        $send_pattern[$item['otp_user_otp']] = $this->OTP;
                        break;
                    case 'site_name':
                    case '{site_name}':
                        $send_pattern[$item['otp_user_otp']] = get_bloginfo('name');
                        break;
                }
            }
            return $send_pattern;
        }

        public function set_value($text, $count = 4)
        {

            $tags = ['{site_name}', '{OTP}'];
            $value = [];

            foreach ($tags as  $tag) {

                switch ($tag) {
                    case "OTP":
                    case "{OTP}":
                        $this->OTP = Payamito_OTP::payamito_generate_otp($count);
                        array_push($value, $this->OTP);
                        break;
                    case "site_name":
                    case "{site_name}":
                        array_push($value, get_bloginfo('name'));
                        break;
                }
            }

            $message = str_replace($tags, $value, $text);

            return $message;
        }


        /**
         * ajax response message
         *
         * @access public
         * @since 1.0.0
         * @return array
         * @static
         */
        public static function message($key)
        {
            $messages = array(
                __('phone_number number is incorrect', 'payamito-edd'),
                __('OTP sent successfully', 'payamito-edd'),
                __('Failed to send OTP ', 'payamito-edd'),
                __('An unexpected error occurred. Please contact support ', 'payamito-edd'),
                __('Enter OTP number ', 'payamito-edd'),
                __(' OTP is Incorrect ', 'payamito-edd'),
            );
            return $messages[$key];
        }
        /**
         * ajax response
         *The response to the OTP request is given in Ajax
         * @access public
         * @since 1.0.0
         * @static
         */
        public  function  ajax_response(int $type = -1, $message, $redirect = null)
        {
            wp_send_json(array('e' => $type, 'message' => $message, "re" => $redirect));
            die;
        }
        /**
         * Register custom fields after the plugin is safely loaded.
         * products list custom filed 
         * requied
         */
        /**
         * Register the orders field
         *
         * @since  1.0.0
         * @return void
         */
        function fields()
        {
            if (Functions::is_request('ajax')) {
                return;
            }
            global $pwoo_otp_options;

            if ($pwoo_otp_options['active'] != true) {

                return;
            }
            $phone_number = $this->get_user_phone_number($pwoo_otp_options['meta_key']);

            if ($pwoo_otp_options['once'] == true && !empty($phone_number)) {
                
                return;
            }

            $this->field_phone_number($pwoo_otp_options);

            $this->field_otp($pwoo_otp_options);
        }
        
        public function get_user_phone_number($meta_key)
        {
            $phone_number = get_user_meta(get_current_user_id(), $meta_key, true);
            if (empty($phone_number)) {
                $phone_number = get_user_meta(get_current_user_id(), 'payamito_phone_number', true);
            }
            return $phone_number;
        }

        public function field_phone_number($options)
        {

            $default = get_user_meta(get_current_user_id(), $options['meta_key'], true);

            $required = $options['force_enter'] == true ? true : false;

            $args = array(
                "title" => __('Phone number', 'payamito-woocommerce '),
                'type'            => 'number',
                'placeholder'           => "Phone number ",
                "default" => $default,
                'label'                 => __('Phone number ', 'payamito-woocommerce '),
                'required' => $required,
                "order" => 1,
                
            );
            if (function_exists('woocommerce_form_field')) {
                woocommerce_form_field("phone_number", $args);
            }
            $args = array(
                'field_type'   => 'text',
                "class" =>["payamito-woocommerce-dispaly"] ,
                "default" => wp_create_nonce("payamito_woocommerce"),
             
            );
            if (function_exists('woocommerce_form_field')) {
                woocommerce_form_field("nonce", $args);
            }
        }

        public function field_otp($options)
        {
            global $pwoo_otp_options;
            if ($options['force_otp'] != true) {
                return;
            }
            $args = array(
                "title" => __('OTP', 'payamito-woocommerce '),
                'field_type'            => 'text',
                'placeholder'           => "OTP",
                'label'                 => __('OTP ', 'payamito-woocommerce '),
                'required' => true,
        
            );
            if (function_exists('woocommerce_form_field')) {
                woocommerce_form_field("otp", $args);
            }
            $args = array(
                'field_type'            => 'text',
                "default" => __('Send OTP ', 'payamito-woocommerce '),
                "input_class" =>["payamito-woocommerce-send-otp"] ,
                'required' => true,
                'custom_attributes'=>['readonly'=>true],
                "order" => 3,
            );
            if (function_exists('woocommerce_form_field')) {
                woocommerce_form_field("send_otp", $args);
            }
            $args = array(
                'field_type'  => 'text',
                "class" =>["payamito-woocommerce-dispaly"] ,
                "default" => $pwoo_otp_options['again_send_time_otp'],
              
            );
            if (function_exists('woocommerce_form_field')) {
                woocommerce_form_field("otp_time", $args);
            }
        }

        public function submit( $user_id)
        {
            global $pwoo_otp_options;
            $slug = payamito_wc()::$slug;

            $phone_number = sanitize_text_field(payamito_to_english_number($_REQUEST['phone_number']));

            if (!isset($_REQUEST['nonce']) || !wp_verify_nonce($_REQUEST['nonce'], $slug)) {

                wp_die("nonce is invalid");
            }
          
            update_user_meta( $user_id, $pwoo_otp_options['meta_key'], esc_attr($phone_number) );
              unset($_SESSION[$phone_number]);
              unset($_SESSION[$phone_number . 'T']);

        }

        public static  function check_once()
        {
            global $options;
            $phone_number = get_user_meta(get_current_user_id(), "phone_number", true);
            if ($phone_number == false) {
                return true;
            }
            if (!isset($options['once_get'])) {
                return false;
            }
            if ($options['once_get'] === true) {
                return true;
            }
        }
    }
endif;
