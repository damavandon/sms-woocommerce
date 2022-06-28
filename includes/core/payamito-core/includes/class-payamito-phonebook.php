<?php

if ( ! defined( 'ABSPATH' ) ) exit;

if (!class_exists("Payamito_PhoneBook")) {

  class Payamito_PhoneBook
  {
    public $groups;

    protected static $instance = null;
    private $username = null;
    private $password = null;
    private $connection = false;
    
    const prefix='payamito_phonebook';
    const link = "http://api.payamakpanel.com/post/contacts.asmx?wsdl";
   
    // If the single instance hasn't been set, set it now.
    public static function get_instance()
    {
      if (null == self::$instance) {

        self::$instance = new self;
      }

      self::$instance->init();

      return self::$instance;
    }

    private function init()
    {

      $options = get_option('payamito');
      if (!isset($options['username']) || !isset($options['password'])) return;
      $is_set_vital = $this->SetVitalParam($options['username'], $options['password']);

      if ($is_set_vital === false) return;

      add_action("payamito_init_admin", [$this, 'InitSubmenu']);

      $this->run();
    }

    private function run()
    {

      if (!$this->IsPhonebookPage()) return;
      if (!$this->connection = $this->CheckConnection()) return;
      add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }
    private function IsPhonebookPage()
    {
      return (!(isset($_GET['page']) &&  $_GET['page'] !== self::getPrefix()));
    }
    private function  CheckConnection()
    {
      if (is_numeric(payamito_code_to_message(payamito_get_crediet()))) return true;return false;
    }
    public function  enqueue_scripts()
    {
      wp_enqueue_script( "payamito-tabulator-js", PAYAMITO_URL . "/assets/js/tabulator.min.js", [ 'jquery' ],false, true );

      wp_enqueue_style( "payamito-tabulator-css", PAYAMITO_URL . "/assets/css/tabulator.min.css" );

    }
    private function initSoapClient(){

      ini_set("soap.wsdl_cache_enabled", 0);
      $soapClient= new SoapClient(self::getUrl(), array('encoding'=>'UTF-8'));
      return $soapClient;
    }
    public function getRemoteGroups()
    {
  
      $soap=$this->initSoapClient();
			
			$parameters['username'] =$this->username;
			$parameters['password'] =$this->password;
	
			try {
				$groups=$soap->GetGroups($parameters)->GetGroupsResult;
	
			}
			catch (exception $e) {
					return -1001;
			}
			return $this->groups=json_decode($groups);
    }

    private function addGroup(){

      $soap=$this->initSoapClient();

    }
    public function prepareGroupes(){
      
    }
    public function SetVitalParam($username,  $password)
    {
      if (!empty(trim($username))) $this->username = $username;
      if (!empty(trim($password))) $this->password = $password;

      if (is_null($this->username) || is_null($this->password)) return false;
      return true;
    }

    function InitSubmenu()
    {

      if (class_exists('KIANFR')) {

        KIANFR::createOptions(self::getPrefix(), array(
          'framework_title'    =>  esc_html__('Phonebook', 'payamito'),
          'menu_title' =>  esc_html__('Phonebook', 'payamito'),
          'menu_title' =>  esc_html__('Phonebook', 'payamito'),
          'menu_slug'  => self::getPrefix(),
          'menu_type' => 'submenu',
          'menu_parent' => 'payamito',
          'theme'              => 'light',
          'menu_position'      => '2',
          'show_sub_menu'      => false,
          'show_reset_section' => false,
          'show_reset_all'     => false,
          'show_all_options'   => false,
          'sticky_header'      => false,
          'footer_text'        => '',
        ));
        KIANFR::createSection(self::getPrefix(), array(
          'title' => esc_html__('Phonebook', 'payamito'),
          'fields' => array(
            array(
              'id'        => 'phonebook',
              'type'      => 'group',
              'fields'    => array(
                array(
                  'id'    => 'groupName',
                  'type'  => 'text',
                  'title' => esc_html__('GroupName', 'payamito'),
                ),
                array(
                  'id'    => 'descriptions',
                  'type'  => 'text',
                  'title' => esc_html__('Descriptions', 'payamito'),
                ),
                array(
                  'id'    => 'showtochilds',
                  'type'  => 'switcher',
                  'title' => esc_html__('Show to childs', 'payamito'),
                ),

                array(
                  'type'    => 'subheading',
                  'title' => esc_html__('Configuration', 'payamito'),
                ),
                array(
                  'id'    => 'auto_add_contact',
                  'type'  => 'switcher',
                  'title' => esc_html__('Auto add contact', 'payamito'),
                ),
                array(
                  'id'     => 'user_meta',
                  'type'   => 'repeater',
                  'title' => esc_html__('User Meta', 'payamito'),
                  'fields' => array(
                    array(
                      'id'    => 'user_meta',
                      'type'  => 'text',
                      'placeholder' => esc_html__('For example digits_phone', 'payamito'),
                    ),

                  ),
                ),
              ),
              'default'   => array(
                //$this->GetGroups(),
              ),
            ),
          )
        ));
      }
    }

    public static function getPrefix(){
      return self::prefix;
    }

    public static function getUrl(){
      return self::link;
    }
  }
}
add_action('payamito_loaded', ['Payamito_PhoneBook', 'get_instance'],);
