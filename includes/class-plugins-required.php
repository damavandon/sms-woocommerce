<?php

namespace Payamito\Woocommerce\Required;

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}
if (!class_exists('Required')) {

	class Required
	{
		public $id;
		public $parent;
		public $slug;

		function __construct()
		{
			add_action('tgmpa_register', [$this, 'required_plugins']);

			if (class_exists('TGM_Plugin_Activation')) {

				$this->id = 'payamitowoocommerce';

				$this->slug = 'payamito_wc';

				$this->parent = 'plugins.php';
			}
		}

		public function required_plugins()
		{
			if(!function_exists('tgmpa')){
				
				return;
			}

			/*
	 * Array of plugin arrays. Required keys are name and slug.
	 * If the source is NOT from the .org repo, then source is also required.
	 */
			$plugins = array(
				array(
					'name'      => 'Woocommerce',
					'slug'      => "woocommerce",
					'force_activation' => true,
					'required'  => true,
					'version'            => '', 
				),
			);
			$config = array(
				'id'           => $this->id,              // Unique ID for hashing notices for multiple instances of TGMPA.
				'default_path' => '',                      // Default absolute path to bundled plugins.
				//'menu'         => $this->slug, // Menu slug.
				//'parent_slug'  => $this->parent,            // Parent menu slug.
				'capability'   => 'install_plugins',    // Capability needed to view plugin install page, should be a capability associated with the parent menu used.
				'has_notices'  => true,                    // Show admin notices or not.
				'dismissable'  => false,                    // If false, a user cannot dismiss the nag message.
				'is_automatic' => false,                   // Automatically activate plugins after installation or not.
				'dismiss_msg'  => __(' Plugin Payamito:Woocommerce requires the installation of Woocommerce ', 'payamito-woocommerce'),

			);

			tgmpa($plugins, $config);
		}
	}
}
