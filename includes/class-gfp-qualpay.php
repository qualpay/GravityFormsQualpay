<?php
/* @package   GFP_Qualpay\GFP_Qualpay
 * @author    Naomi C. Bush for gravity+ for Qualpay <support@gravityplus.pro>
 * @copyright 2018 gravity+
 * @license   GPL-2.0+
 * @since     1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class GFP_Qualpay
 *
 * Main plugin class
 *
 * @since  1.0.0
 *
 * @author Naomi C. Bush for gravity+ for Qualpay <support@gravityplus.pro>
 */
class GFP_Qualpay {

	/**
	 * Constructor
	 *
	 * @since  1.0.0
	 *
	 * @author Naomi C. Bush for gravity+ for Qualpay <support@gravityplus.pro>
	 */
	public function construct() {
	}

	/**
	 * Register WordPress hooks
	 *
	 * @since  1.0.0
	 *
	 * @author Naomi C. Bush for gravity+ for Qualpay <support@gravityplus.pro>
	 */
	public function run() {

		register_activation_hook( GFP_QUALPAY_FILE, array( 'GFP_Qualpay', 'activate' ) );

		add_action( 'gform_loaded', array( $this, 'gform_loaded' ) );

	}

	public static function activate(){

		add_role( 'qualpay_customer', 'Qualpay Customer' );

	}

	/**
	 * Create GF Add-On
	 *
	 * @since  1.0.0
	 *
	 * @author Naomi C. Bush for gravity+ for Qualpay <support@gravityplus.pro>
	 */
	public function gform_loaded() {

		if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {

			return;

		}

		GFForms::include_addon_framework();

		GFForms::include_payment_addon_framework();

		GFAddOn::register( 'GFP_Qualpay_Addon' );

	}

	/**
	 * Return GF Add-On object
	 *
	 * @since  1.0.0
	 *
	 * @author Naomi C. Bush for gravity+ for Qualpay <support@gravityplus.pro>
	 *
	 * @return GFP_Qualpay_Addon
	 */
	public function get_addon_object() {

		return GFP_Qualpay_Addon::get_instance();

	}

}