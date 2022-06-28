<?php
if (!defined('ABSPATH')) exit;

if (!class_exists("Payamito_Getway")) {
	class Payamito_Getway
	{

		private static $instance;
		private $username;
		private $password;
		private $from;

		private $send_endpoint = 'http://api.payamak-panel.com/post/Send.asmx?wsdl';


		public function __construct()
		{

			$connection = Payamito_Connection::instance();

			$this->username = apply_filters('payamito_username', $connection->username);
			$this->password = apply_filters('payamito_password', $connection->password);
			$this->from     = apply_filters('payamito_from', $connection->from);
		}

		public function send_pattern($to, $text, $bodyid)
		{

			do_action('payamito_before_send_pattern', $to, $text, $bodyid);

			ini_set("soap.wsdl_cache_enabled", 0);

			$client                   = new \SoapClient($this->send_endpoint, ['exceptions' => false]);
			$client->soap_defencoding = 'UTF-8';
			$client->decode_utf8      = true;
			$args                     = [
				"username" => $this->username,
				"password" => $this->password,
				"to"       => $to,
				"text"     => $text,
				'bodyId'   => $bodyid,
			];

			$args = apply_filters('payamito_send_pattern_args', $args);

			try {
				$result = $client->SendByBaseNumber($args)->SendByBaseNumberResult;
			} catch (exception $e) {

				$result = -1001;
			}

			do_action('payamito_after_send_pattern', $result, $args);

			return $result;
		}

		public function send($to, $text)
		{

			do_action('payamito_before_send', $to, $text);

			ini_set("soap.wsdl_cache_enabled", 0);

			$client                   = new \SoapClient($this->send_endpoint, ['exceptions' => false]);
			$client->soap_defencoding = 'UTF-8';
			$client->decode_utf8      = true;
			$args                     = [
				"username" => $this->username,
				"password" => $this->password,
				"from"     => $this->from,
				"to"       => $to,
				"text"     => $text,
				"isflash"  => false
			];

			$args = apply_filters('payamito_send_args', $args);

			try {
				$result = $client->SendSimpleSMS($args)->SendSimpleSMSResult;
			} catch (exception $e) {
				$result = -1001;
			}

			if (is_null($result)) {
				$result = -100;
			}
			do_action('payamito_after_send', $result, $args);

			return $result;
		}
		public function payamito_group_send($to, $text,$sendernumber=null)
		{
			do_action('payamito_before_group_send', $to, $text);
			ini_set("soap.wsdl_cache_enabled", "0");
			try {
				$client = new SoapClient('http://api.payamak-panel.com/post/send.asmx?wsdl', array('encoding' => 'UTF-8'));
				$parameters['username'] = $this->username;
				$parameters['password'] = $this->password;
				$sendernumber===null?$parameters['from']=$this->from:$parameters['from']=$sendernumber;
				$parameters['to'] = $to;
				$parameters['text'] = $text;
				$parameters['isflash'] = true;
				$parameters['udh'] = "";
				$parameters['recId'] = array(0);
				$parameters['status'] = 0x0;
				$result =	 $client->SendSms($parameters)->SendSmsResult;
			} catch (SoapFault $ex) {
				return -1001;
			}
			do_action('payamito_after_group_send', $to, $text);
			return $result;
		}
		public function crediet()
		{


			ini_set("socredietap.wsdl_cache_enabled", "0");

			$sms_client = new SoapClient('http://api.payamak-panel.com/post/Send.asmx?wsdl', array('encoding' => 'UTF-8'));

			$parameters['username'] = $this->username;
			$parameters['password'] = $this->password;

			try {
				$crediet = (string) $sms_client->GetCredit($parameters)->GetCreditResult;
			} catch (exception $e) {
				return -1001;
			}
			return $crediet;
		}

		public static function instance()
		{
			$class = static::class;

			if (!isset(self::$instance[$class])) {
				self::$instance[$class] = new $class();
			}

			return self::$instance[$class];
		}
	}
}
