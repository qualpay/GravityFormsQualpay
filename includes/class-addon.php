<?php
/* @package   GFP_Qualpay\GFP_Qualpay_Addon
 * @author    Jankee Patel from Qualpay 
 * @copyright 2018 gravity+
 * @license   GPL-2.0+
 * @since     1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


/**
 * Class GFP_Qualpay_Addon
 *
 * Gravity Forms Payment Add-On definition
 *
 * Sections:
 *
 * 1. Variables
 * 2. Initialization
 * 3. Plugin Settings
 * 4. Currencies
 * 5. Feed Settings
 * 6. Form Display
 * 7. Form Validation
 * 8. Form Submission Processing
 * 9. Entry Details
 * 10. Webhooks
 * 11. Reports
 * 12. Uninstall
 *
 * @todo split sections into multiple files?
 *
 * @since  1.0.0
 *
 * @author Jankee Patel from Qualpay 
 */
class GFP_Qualpay_Addon extends GFPaymentAddOn {

	/**************************************************
	 * 1. VARIABLES                                   *
	 *                                                *
	 **************************************************/

	/**
	 * @var string Version number of the Add-On
	 */
	protected $_version;

	/**
	 * @var string Gravity Forms minimum version requirement
	 */
	protected $_min_gravityforms_version;

	/**
	 * @var string URL-friendly identifier used for form settings, add-on settings, text domain localization...
	 */
	protected $_slug;

	/**
	 * @var string Relative path to the plugin from the plugins folder
	 */
	protected $_path;

	/**
	 * @var string Full path to the plugin. Example: __FILE__
	 */
	protected $_full_path;

	/**
	 * @var string URL to the App website.
	 */
	protected $_url;

	/**
	 * @var string Title of the plugin to be used on the settings page, form settings and plugins page.
	 */
	protected $_title;

	/**
	 * @var string Short version of the plugin title to be used on menus and other places where a less verbose string
	 *      is useful.
	 */
	protected $_short_title;

	/**
	 * @var array Members plugin integration. List of capabilities to add to roles.
	 */
	protected $_capabilities = array();

	// ------------ Permissions -----------
	/**
	 * @var string|array A string or an array of capabilities or roles that have access to the settings page
	 */
	protected $_capabilities_settings_page = array();

	/**
	 * @var string|array A string or an array of capabilities or roles that have access to the form settings
	 */
	protected $_capabilities_form_settings = array();

	/**
	 * @var string|array A string or an array of capabilities or roles that can uninstall the plugin
	 */
	protected $_capabilities_uninstall = array();

	// ------------ Payment Settings -----------
	/**
	 * @var bool
	 */
	protected $_requires_credit_card = true;

	/**
	 * @var bool
	 */
	protected $_supports_callbacks = true;

	/**
	 * @var bool
	 */
	protected $is_payment_gateway = true;

	/**
	 * @var bool
	 */
	protected $_multiple_feeds = true;

	/**
	 * @var bool
	 */
	protected $_single_feed_submission = false;

	/**
	 * Qualpay API Requestor
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @var GFP_Qualpay_API | null
	 */
	protected $_gfp_qualpay_api = null;

	/**
	 * Validation results for plugin settings fields
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @var array
	 */
	private $_plugin_settings_validation_result = array();

	/**
	 * Processed transactions
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @var array
	 */
	private $_transactions = array();

	/**
	 * Used during form processing to hold current Qualpay customer we're processing transactions for
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @var string
	 */
	private $_current_customer_id = '';

	/**
	 * Used during form processing to hold current WordPress user we're processing transactions for
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @var string
	 */
	private $_current_user_id = '';

	/**
	 * Add-On instance
	 *
	 * @since 1.0.0
	 *
	 * @var GFP_Qualpay_Addon
	 */
	private static $_instance = null;


	/**************************************************
	 * 2. INITIALIZATION                              *
	 *                                                *
	 **************************************************/

	/**
	 * @see    parent
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param $args
	 */
	function __construct( $args ) {

		$this->_version                    = $args[ 'version' ];
		$this->_slug                       = $args[ 'plugin_slug' ];
		$this->_min_gravityforms_version   = $args[ 'min_gf_version' ];
		$this->_path                       = $args[ 'path' ];
		$this->_full_path                  = $args[ 'full_path' ];
		$this->_url                        = $args[ 'url' ];
		$this->_title                      = $args[ 'title' ];
		$this->_short_title                = $args[ 'short_title' ];
		$this->_capabilities               = $args[ 'capabilities' ];
		$this->_capabilities_settings_page = $args[ 'capabilities_settings_page' ];
		$this->_capabilities_form_settings = $args[ 'capabilities_form_settings' ];
		$this->_capabilities_uninstall     = $args[ 'capabilities_uninstall' ];

		parent::__construct();

		
	}

	/**
	 * Needed for GF Add-On Framework functions
	 *
	 * @since 1.0.0
	 *
	 * @return GFP_Qualpay_Addon|null
	 */
	public static function get_instance() {

		if ( self::$_instance == null ) {

			self::$_instance = new self(
				array(
					'version'                    => GFP_QUALPAY_CURRENT_VERSION,
					'min_gf_version'             => '2.3',
					'plugin_slug'                => GFP_QUALPAY_SLUG,
					'path'                       => plugin_basename( GFP_QUALPAY_FILE ),
					'full_path'                  => GFP_QUALPAY_FILE,
					'title'                      => 'Gravity Forms Qualpay',
					'short_title'                => 'Qualpay',
					'url'                        => 'https://www.qualpay.com',
					'capabilities'               => array(
						'gravityforms_qualpay_plugin_settings',
						'gravityforms_qualpay_form_settings',
						'gravityforms_qualpay_uninstall'
					),
					'capabilities_settings_page' => array( 'gravityforms_qualpay_plugin_settings' ),
					'capabilities_form_settings' => array( 'gravityforms_qualpay_form_settings' ),
					'capabilities_uninstall'     => array( 'gravityforms_qualpay_uninstall' )
				) );

		}

		return self::$_instance;

	}
	
	/**
	 * Get Qualpay API Requestor
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param string $mode
	 *
	 * @return GFP_Qualpay_API|null
	 */
	public function get_qualpay_api( $mode = 'test', $api_key = '' ) {

		if ( empty( $this->_gfp_qualpay_api ) ) {
			
			$this->_gfp_qualpay_api = new GFP_Qualpay_API( $mode, empty( $api_key ) ? $this->get_plugin_setting( "api_key_{$mode}" ) : $api_key, 'Gravity Forms Qualpay', $this->get_developer_id(), new GFP_Qualpay_API_Logger() );

		} else {

			$this->_gfp_qualpay_api->set_api_mode( $mode );

		}


		return $this->_gfp_qualpay_api;

	}

	/**
	 * Developer ID
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @return string
	 */
	public function get_developer_id() {

		return 'GravityForms' . GFP_QUALPAY_CURRENT_VERSION;
		
	}

	/**
	 * @see    parent
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param $previous_version
	 */
	public function upgrade( $previous_version ) {

		do_action( "gform_{$this->_slug}_upgrade", $previous_version, $this );


		return;

	}

	/**
	 * @see    parent
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 */
	public function init_admin() {

		$settings =  get_option( 'gravityformsaddon_gravityformsqualpay_settings' );
		$api_key_test = $settings['api_key_test'];
		$merchant_id_test = $settings['merchant_id_test'];
		$merchant_id_live = $settings['merchant_id_live'];
		$api_key_live = $settings['api_key_live'];
			
        if (esc_attr($api_key_test) != '') {
            if ($api_key_test != '') {
                $fist_sandbox = substr($api_key_test, 0, 4);
                $last_sandbox = substr($api_key_test, -4);
               $this->api_key_test = $fist_sandbox . '****' . $last_sandbox;
            }
		}
		if (esc_attr($api_key_live) != '') {
            if ($api_key_live != '') {
                $fist_sandbox = substr($api_key_live, 0, 4);
                $last_sandbox = substr($api_key_live, -4);
               $this->api_key_live = $fist_sandbox . '****' . $last_sandbox;
            }
		}

		$this->api_key_test = $this->api_key_test;
		$this->merchant_id_test = $settings['merchant_id_test'];
		$this->merchant_id_live = $settings['merchant_id_live'];
		$this->api_key_live = $this->api_key_live;

		parent::init_admin();
		
		add_filter( 'gform_predefined_choices', array( $this, 'gform_predefined_choices' ) );

		add_filter( 'gform_entry_detail_meta_boxes', array( $this, 'gform_entry_detail_meta_boxes' ), 10, 3 );

		remove_action( 'gform_payment_details', array( $this, 'entry_info' ), 10 );
	
	}

	/**
	 * @see    parent
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 */
	public function init() {

		parent::init();

		remove_filter( 'gform_entry_post_save', array( $this, 'entry_post_save' ), 10 );

		add_filter( 'gform_field_input', array( $this, 'gform_field_input' ), 10, 5 );

		add_filter( 'gform_payment_methods', array( $this, 'gform_payment_methods' ), 10, 3 );

		add_action( 'gform_register_init_scripts', array( $this, 'gform_register_init_scripts' ), 10, 3 );

		add_filter( 'gform_field_validation', array( $this, 'gform_field_validation' ), 10, 4 );
	}

	public function init_ajax() {

		add_action( 'wp_ajax_gaddon_qualpay_transient_key', array( $this, 'ajax_qualpay_transient_key' ) );

		add_action( 'wp_ajax_nopriv_gaddon_qualpay_transient_key', array( $this, 'ajax_qualpay_transient_key' ) );

		add_action( 'wp_ajax_gaddon_payment_action', array( $this, 'ajax_payment_action' ) );


		parent::init_ajax();
	}

	/**
	 * @see    parent
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @return array
	 */
	public function styles() {

		$styles = array();

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || isset( $_GET[ 'gform_debug' ] ) ? '' : '.min';

		$current_form = $this->get_current_form();

		$styles[] =
			array(
				'handle'  => 'gfp_qualpay_embedded',
				'src'     => 'https://app.qualpay.com/hosted/embedded/css/qp-embedded.css',
				'version' => GFP_QUALPAY_CURRENT_VERSION,
				'enqueue' => array(
					array( 'field_types' => array( 'creditcard' ) ),
				)
			);
			
		return array_merge( parent::styles(), $styles );
	}

	/**
	 * @see    parent
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @return array
	 */
	public function scripts() {

		$scripts = parent::scripts();

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || isset( $_GET[ 'gform_debug' ] ) ? '' : '.min';

		$scripts[] =
			array(
				'handle'    => 'gfp_qualpay_admin',
				'src'       => GFP_QUALPAY_URL . "/includes/js/admin{$suffix}.js",
				'version'   => GFP_QUALPAY_CURRENT_VERSION,
				'deps'      => array( 'jquery' ),
				'in_footer' => false,
				'enqueue'   => array(
					array(
						'admin_page' => array( 'entry_view','form_editor', 'form_settings', 'plugin_settings', 'plugin_page', 'entry_detail', 'results' )
					),
				),
				'strings'   => array(
					'payment_action_nonce'   => wp_create_nonce( 'gaddon_payment_action' ),
					'payment_action_warning' => __( "Warning! This action cannot be undone. 'OK' to continue, 'Cancel' to stop", 'gravityformsqualpay' )
				)
			);

		$scripts[] =
			array(
				'handle'    => 'gfp_qualpay_embedded',
				'src'       => 'https://app.qualpay.com/hosted/embedded/js/qp-embedded-sdk.min.js',
				'version'   => GFP_QUALPAY_CURRENT_VERSION,
				'deps'      => array( 'jquery' ),
				'in_footer' => false,
				'enqueue'   => array(
					array( $this, 'enqueue_creditcard_token_script' ),
				)
			);
			
		$scripts[] =
			array(
				'handle'    => 'gfp_qualpay_frontend',
				'src'       => GFP_QUALPAY_URL . "/includes/js/frontend{$suffix}.js",
				'version'   => GFP_QUALPAY_CURRENT_VERSION,
				'deps'      => array( 'jquery', 'gfp_qualpay_embedded' ),
				'in_footer' => true,
				'enqueue'   => array( array( $this, 'enqueue_creditcard_token_script' ) ),
				'callback'  => array( $this, 'frontend_script_enqueue_callback' )
			);

		/**
		 * @todo need to unset gaddon_token script?
         */


		return array_merge( parent::scripts(), $scripts );
	}


	/**************************************************
	 * 3. PLUGIN SETTINGS                             *
	 *                                                *
	 **************************************************/

	/**
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @return string
	 */
	public function plugin_settings_icon() {

		return '<img style="height:1em;" src="' . GFP_QUALPAY_URL . 'includes/images/qualpay-icon-white.png">';

	}

	/**
	 * @see    parent
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @return array
	 */

	public function plugin_settings_fields() {
		
		$settings_fields = array();
		
		$set_array = array(
			'merchant_id_test'	=> $this->merchant_id_test,
			'api_key_test'		=> $this->api_key_test,
			'merchant_id_live'	=> $this->merchant_id_live,
			'api_key_live'	=> $this->api_key_live
		);

		$this->set_settings($set_array);
		
		
		$settings_fields[] = array(
			'title'       => __( 'Authentication', 'gravityformsqualpay' ),
			'description' => sprintf( __( 'This connects your Qualpay account to process your payments. If you don\'t already have a Qualpay account, you can %ssign up for one here%s.', 'gravityformsqualpay' ), '<a href="https://www.qualpay.com/get-started">', '</a> <br /><br />
				<b> NOTE: You must enter a valid Merchant ID and API Security Key for at least one environment</b>'  ) . "<br /><br />",
			'fields'      => array(
				array(
					'name'              => 'merchant_id_test',
					'label'             => __( 'Sandbox Merchant ID', 'gravityformsqualpay' ),
					'type'              => 'text',
					//'required'          => true,
					'save_callback'     => array( $this, 'save_key' ),
					'feedback_callback' => array( $this, 'check_plugin_settings_validation_result' ),
				),
				array(
					'name'                => 'api_key_test',
					'label'               => __( 'Sandbox API Security Key', 'gravityformsqualpay' ),
					'type'                => 'text',
				//	'required'            => true,
					'validation_callback' => array( $this, 'validate_api_key' ),
					'save_callback'       => array( $this, 'save_key' ),
					'feedback_callback'   => array( $this, 'check_plugin_settings_validation_result' ),
					'size'                => 32
					
				),
				array(
					'name'              => 'merchant_id_live',
					'label'             => __( 'Production Merchant ID', 'gravityformsqualpay' ),
					'type'              => 'text',
					'save_callback'     => array( $this, 'save_key' ),
					'feedback_callback' => array( $this, 'check_plugin_settings_validation_result' ),
				),
				array(
					'name'                => 'api_key_live',
					'label'               => __( 'Production API Security Key', 'gravityformsqualpay' ),
					'type'                => 'text',
					'validation_callback' => array( $this, 'validate_api_key' ),
					'save_callback'       => array( $this, 'save_key' ),
					'feedback_callback'   => array( $this, 'check_plugin_settings_validation_result' ),
					'size'                => 32
				)
			)
		);

	/*	$settings_fields[] = array(
			'title'       => __( 'Webhooks', 'gravityformsqualpay' ),
			'description' => $this->get_webhooks_section_description(),
			'fields'      => array(
				array(
					'name'  => 'webhook_id_test',
					'type'  => 'hidden',
					'label' => ''
				),
				array(
					'name'  => 'webhook_id_live',
					'type'  => 'hidden',
					'label' => ''
				),
				array(
					'name'  => 'webhook_secret_test',
					'type'  => 'hidden',
					'label' => ''
				),
				array(
					'name'  => 'webhook_secret_live',
					'type'  => 'hidden',
					'label' => ''
				)
			),
			'dependency'  => 'api_key_test'
		);

		$settings_fields[] = array(
			'title'       => '',
			'description' => '',
			'fields'      => array()
		);
		*/

		return $settings_fields;
	}

	/**
	 * Webhooks section description
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @return string
	 */
	private function get_webhooks_section_description() {

		$this->log_debug( __METHOD__ );


		$webhooks_description = '';


		$test_webhook_status = $this->get_webhook_status( 'test' );

		$test_webhook_status_class = ( 'ACTIVE' == $test_webhook_status ) ? 'gf_keystatus_valid_text' : 'gf_keystatus_invalid_text';


		$live_webhook_status = $this->get_webhook_status( 'live' );

		$live_webhook_status_class = ( 'ACTIVE' == $live_webhook_status ) ? 'gf_keystatus_valid_text' : 'gf_keystatus_invalid_text';


		if ( ! empty( $test_webhook_status ) ) {

			ob_start();

			include( trailingslashit( GFP_QUALPAY_PATH ) . 'includes/views/plugin-settings-webhooks.php' );

			$webhooks_description = ob_get_contents();

			ob_end_clean();

		}


		return $webhooks_description;
	}

	/**
	 * Get webhook status
	 *
	 * If webhook doesn't exist, create it and save ID and secret to plugin settings.
	 * If it does exist, check the status and refresh the secret saved to the plugin settings
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param $mode
	 *
	 * @return string
	 */
	private function get_webhook_status( $mode ) {

		$this->log_debug( __METHOD__ );


		$webhook_status = '';


		$settings = $this->get_plugin_settings();

		if ( empty( $settings[ "webhook_id_{$mode}" ] ) ) {

			if ( ! empty( $settings[ "api_key_{$mode}" ] ) && ! empty( $settings[ "merchant_id_{$mode}" ] ) ) {

				$this->get_qualpay_api( $mode );

				$webhook = $this->_gfp_qualpay_api->add_webhook(
				        'Gravity Forms Qualpay',
                        $this->get_callback_url(),
                        $settings[ "merchant_id_{$mode}" ],
                        array(),
                        array(
                                'subscription_suspended',
                            'subscription_payment_success',
                            'subscription_payment_failure',
                            'subscription_complete'
                        )
                );

				if ( $webhook[ 'success' ] && ! empty( $webhook[ 'response' ][ 'data' ] ) ) {

					$settings[ "webhook_id_{$mode}" ] = $webhook[ 'response' ][ 'data' ][ 'webhook_id' ];

					$settings[ "webhook_secret_{$mode}" ] = $webhook[ 'response' ][ 'data' ][ 'security_key' ]; //NOTE: documentation has this as 'secret'


					parent::update_plugin_settings( $settings );


					$webhook_status = $webhook[ 'response' ][ 'data' ][ 'status' ];

				}

			}

		} else {

			if ( ! empty( $settings[ "api_key_{$mode}" ] ) ) {

				$this->get_qualpay_api( $mode );

				$webhook = $this->_gfp_qualpay_api->get_webhook( $settings[ "webhook_id_{$mode}" ] );

				if ( $webhook[ 'success' ] && ! empty( $webhook[ 'response' ][ 'data' ] ) ) {

					$settings[ "webhook_secret_{$mode}" ] = $webhook[ 'response' ][ 'data' ][ 'secret' ];


					parent::update_plugin_settings( $settings );


					$webhook_status = $webhook[ 'response' ][ 'data' ][ 'status' ];

				}

			}

		}


		return $webhook_status;
	}

	/**
	 * Webhook notification URL
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @return string
	 */
	private function get_callback_url() {

		$this->log_debug( __METHOD__ );


		$url = defined( 'GRAVITYFORMSQUALPAY_CALLBACK' ) && ! empty( GRAVITYFORMSQUALPAY_CALLBACK ) ? trailingslashit( GRAVITYFORMSQUALPAY_CALLBACK ) : trailingslashit( home_url() );

		$callback_url = add_query_arg( 'callback', $this->_slug, $url );


		return $callback_url;
	}

	/**
	 * Save key
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param $field
	 * @param $field_setting
	 *
	 * @return string
	 **/
	public function save_key( $field, $field_setting ) {

		if ( ! empty( $field_setting ) ) {

			if (strpos($field_setting, '****') !== false) {
				$settings1 =  get_option( 'gravityformsaddon_gravityformsqualpay_settings' );
				$api_key_test1 = $settings1[$field['name']];
				$field_setting = $api_key_test1;
			}

			$field_setting = trim( $field_setting );

		}

		return $field_setting;
	}

	/**
	 * Validate API key (and merchant ID if API key is valid)
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param $field
	 * @param $field_setting
	 */
	public function validate_api_key( $field, $field_setting ) {
		
		if (strpos($field_setting, '****') !== false) {
            $settings1 =  get_option( 'gravityformsaddon_gravityformsqualpay_settings' );
			$api_key_test1 = $settings1[$field['name']];
            $field_setting = $api_key_test1;
		}
	
		if ( rgar( $field, 'required' ) && rgblank( $field_setting ) ) {

			$this->set_field_error( $field, rgar( $field, 'error_message' ) );

			return;

		}

		if ( ! empty( $field_setting ) ) {

			$mode = str_replace( 'api_key_', '', $field[ 'name' ] );

			$this->get_qualpay_api( $mode, $field_setting );

			$valid_api_key = $this->_gfp_qualpay_api->validate_api_key( $field_setting );

			$this->_plugin_settings_validation_result[ $field[ 'name' ] ] = $valid_api_key;

			if ( $valid_api_key ) {

				$this->validate_merchant_id( $mode );

			} else {

				$this->set_field_error( $field, __( 'Invalid security key. Please try again.', 'gravityformsqualpay' ) );

			}

		}

	}

	/**
	 * Validate merchant ID
	 *
	 * We have to wait to validate merchant ID because it requires a valid API key
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 */
	private function validate_merchant_id( $environment ) {

			$settings = $this->get_posted_settings();

		if ( empty( $settings[ "merchant_id_{$environment}" ] ) ) {

			$this->set_field_error( array( 'name' => "merchant_id_{$environment}" ), __( 'Merchant ID cannot be empty', 'gravityformsqualpay') );

			return;

		}

				$this->get_qualpay_api( $environment );

				$valid_merchant_id = $this->_gfp_qualpay_api->validate_merchant_id( $settings[ "merchant_id_{$environment}" ] );

				$this->_plugin_settings_validation_result[ "merchant_id_{$environment}" ] = $valid_merchant_id;

				if ( ! $valid_merchant_id ) {

					$this->set_field_error( array( 'name' => "merchant_id_{$environment}" ), __( 'Invalid merchant ID.', 'gravityformsqualpay' ) );

				}

	}

	/**
	 * Check validation result for plugin settings field
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param       $value
	 * @param array $field
	 *
	 * @return bool | string    Possible values: true|false|empty string
	 */
	public function check_plugin_settings_validation_result( $value, $field ) {
		
		if ( empty( $this->_plugin_settings_validation_result ) ) {

			return '';

		}


		return rgar( $this->_plugin_settings_validation_result, $field[ 'name' ] );
	}

	/**************************************************
	 * CURRENCIES                                     *
	 *                                                *
	 **************************************************/

	/**
	 * Gravity Forms-formatted currency information
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @return array
	 */
	public function gf_formatted_currencies() {

		return array(
			'USD' => array(
				'name'               => __( 'United States Dollar', 'ppp-stripe' ),
				'symbol_left'        => '$',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'AED' => array(
				'name'               => __( 'United Arab Emirates Dirham', 'ppp-stripe' ),
				'symbol_left'        => '&#1583;.&#1573;',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'AFN' => array(
				'name'               => __( 'Afghan Afghani', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => '&#1547;',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'ALL' => array(
				'name'               => __( 'Albanian Lek', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'L',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'AMD' => array(
				'name'               => __( 'Armenian Dram', 'ppp-stripe' ),
				'symbol_left'        => 'AMD',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'ANG' => array(
				'name'               => __( 'Netherlands Antillean Gulden', 'ppp-stripe' ),
				'symbol_left'        => '&#402;',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'AOA' => array(
				'name'               => __( 'Angolan Kwanza', 'ppp-stripe' ),
				'symbol_left'        => 'Kz',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'ARS' => array(
				'name'               => __( 'Argentine Peso', 'ppp-stripe' ),
				'symbol_left'        => 'ARS$',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'AUD' => array(
				'name'               => __( 'Australian Dollar', 'ppp-stripe' ),
				'symbol_left'        => 'A$',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'AWG' => array(
				'name'               => __( 'Aruban Florin', 'ppp-stripe' ),
				'symbol_left'        => '&#402;',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'AZN' => array(
				'name'               => __( 'Azerbaijani Manat', 'ppp-stripe' ),
				'symbol_left'        => '&#1084;&#1072;&#1085;',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'BAM' => array(
				'name'               => __( 'Bosnia & Herzegovina Convertible Mark', 'ppp-stripe' ),
				'symbol_left'        => 'KM',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'BBD' => array(
				'name'               => __( 'Barbadian Dollar', 'ppp-stripe' ),
				'symbol_left'        => 'Bbd$',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'BDT' => array(
				'name'               => __( 'Bangladeshi Taka', 'ppp-stripe' ),
				'symbol_left'        => '&#2547;',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'BGN' => array(
				'name'               => __( 'Bulgarian Lev', 'ppp-stripe' ),
				'symbol_left'        => '&#1083;&#1074;',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'BIF' => array(
				'name'               => __( 'Burundian Franc', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'BIF',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 0
			),
			'BMD' => array(
				'name'               => __( 'Bermudian Dollar', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'BMD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'BND' => array(
				'name'               => __( 'Brunei Dollar', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'BND',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'BOB' => array(
				'name'               => __( 'Bolivian Boliviano', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'BOB',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'BRL' => array(
				'name'               => __( 'Brazilian Real', 'ppp-stripe' ),
				'symbol_left'        => 'R$',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'BSD' => array(
				'name'               => __( 'Bahamian Dollar', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'BSD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'BWP' => array(
				'name'               => __( 'Botswana Pula', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'BWP',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'BZD' => array(
				'name'               => __( 'Belize Dollar', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'BZD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'CAD' => array(
				'name'               => __( 'Canadian Dollar', 'ppp-stripe' ),
				'symbol_left'        => 'CAD$',
				'symbol_right'       => 'CAD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'CDF' => array(
				'name'               => __( 'Congolese Franc', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'CDF',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'CHF' => array(
				'name'               => __( 'Swiss Franc', 'ppp-stripe' ),
				'symbol_left'        => 'Fr',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => "'",
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'CLP' => array(
				'name'               => __( 'Chilean Peso', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'CLP',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 0,
				'american_express'   => false
			),
			'CNY' => array(
				'name'               => __( 'Chinese Renminbi Yuan', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'CNY',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'COP' => array(
				'name'               => __( 'Colombian Peso', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'COP',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'CRC' => array(
				'name'               => __( 'Costa Rican Colón', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'CRC',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'CVE' => array(
				'name'               => __( 'Cape Verdean Escudo', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'CVE',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'CZK' => array(
				'name'               => __( 'Czech Koruna', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => '&#75;&#269;',
				'symbol_padding'     => ' ',
				'thousand_separator' => ' ',
				'decimal_separator'  => ',',
				'decimals'           => 2,
				'american_express'   => false
			),
			'DJF' => array(
				'name'               => __( 'Djiboutian Franc', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'DJF',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 0,
				'american_express'   => false
			),
			'DKK' => array(
				'name'               => __( 'Danish Krone', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'kr.',
				'symbol_padding'     => ' ',
				'thousand_separator' => '.',
				'decimal_separator'  => ',',
				'decimals'           => 2
			),
			'DOP' => array(
				'name'               => __( 'Dominican Peso', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'DOP',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'DZD' => array(
				'name'               => __( 'Algerian Dinar', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'DZD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'EEK' => array(
				'name'               => __( 'Estonian Kroon', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'EEK',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'EGP' => array(
				'name'               => __( 'Egyptian Pound', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'EGP',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'ETB' => array(
				'name'               => __( 'Ethiopian Birr', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'ETB',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'EUR' => array(
				'name'               => __( 'Euro', 'ppp-stripe' ),
				'symbol_left'        => '&#8364;',
				'symbol_right'       => '',
				'symbol_padding'     => '',
				'thousand_separator' => ' ',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'FJD' => array(
				'name'               => __( 'Fijian Dollar', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'FJD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'FKP' => array(
				'name'               => __( 'Falkland Islands Pound', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'FKP',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'GBP' => array(
				'name'               => __( 'British Pound', 'ppp-stripe' ),
				'symbol_left'        => '&#163;',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'GEL' => array(
				'name'               => __( 'Georgian Lari', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'GEL',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'GIP' => array(
				'name'               => __( 'Gibraltar Pound', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'GIP',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'GMD' => array(
				'name'               => __( 'Gambian Dalasi', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'GMD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'GNF' => array(
				'name'               => __( 'Guinean Franc', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'GNF',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 0,
				'american_express'   => false
			),
			'GTQ' => array(
				'name'               => __( 'Guatemalan Quetzal', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'GTQ',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'GYD' => array(
				'name'               => __( 'Guyanese Dollar', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'GYD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'HKD' => array(
				'name'               => __( 'Hong Kong Dollar', 'ppp-stripe' ),
				'symbol_left'        => 'HK$',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'HNL' => array(
				'name'               => __( 'Honduran Lempira', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'HNL',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'HRK' => array(
				'name'               => __( 'Croatian Kuna', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'HRK',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'HTG' => array(
				'name'               => __( 'Haitian Gourde', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'HTG',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'HUF' => array(
				'name'               => __( 'Hungarian Forint', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'Ft',
				'symbol_padding'     => ' ',
				'thousand_separator' => '.',
				'decimal_separator'  => ',',
				'decimals'           => 2
			),
			'IDR' => array(
				'name'               => __( 'Indonesian Rupiah', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'IDR',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'ILS' => array(
				'name'               => __( 'Israeli New Sheqel', 'ppp-stripe' ),
				'symbol_left'        => '&#8362;',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'INR' => array(
				'name'               => __( 'Indian Rupee', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'INR',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'ISK' => array(
				'name'               => __( 'Icelandic Króna', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'ISK',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'JMD' => array(
				'name'               => __( 'Jamaican Dollar', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'JMD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'JPY' => array(
				'name'               => __( 'Japanese Yen', 'ppp-stripe' ),
				'symbol_left'        => '&#165;',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '',
				'decimals'           => 0
			),
			'KES' => array(
				'name'               => __( 'Kenyan Shilling', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'KES',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'KGS' => array(
				'name'               => __( 'Kyrgyzstani Som', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'KGS',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'KHR' => array(
				'name'               => __( 'Cambodian Riel', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'KHR',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'KMF' => array(
				'name'               => __( 'Comorian Franc', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'KMF',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 0
			),
			'KRW' => array(
				'name'               => __( 'South Korean Won', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'KRW',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 0
			),
			'KYD' => array(
				'name'               => __( 'Cayman Islands Dollar', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'KYD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'KZT' => array(
				'name'               => __( 'Kazakhstani Tenge', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'KZT',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'LAK' => array(
				'name'               => __( 'Lao Kip', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'LAK',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'LBP' => array(
				'name'               => __( 'Lebanese Pound', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'LBP',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'LKR' => array(
				'name'               => __( 'Sri Lankan Rupee', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'LKR',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'LRD' => array(
				'name'               => __( 'Liberian Dollar', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'LRD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'LSL' => array(
				'name'               => __( 'Lesotho Loti', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'LSL',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'LTL' => array(
				'name'               => __( 'Lithuanian Litas', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'LTL',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'LVL' => array(
				'name'               => __( 'Latvian Lats', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'LVL',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'MAD' => array(
				'name'               => __( 'Moroccan Dirham', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'MAD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'MDL' => array(
				'name'               => __( 'Moldovan Leu', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'MDL',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'MGA' => array(
				'name'               => __( 'Malagasy Ariary', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'MGA',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 0
			),
			'MKD' => array(
				'name'               => __( 'Macedonian Denar', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'MKD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'MNT' => array(
				'name'               => __( 'Mongolian Tögrög', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'MNT',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'MOP' => array(
				'name'               => __( 'Macanese Pataca', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'MOP',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'MRO' => array(
				'name'               => __( 'Mauritanian Ouguiya', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'MRO',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'MUR' => array(
				'name'               => __( 'Mauritian Rupee', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'MUR',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'MVR' => array(
				'name'               => __( 'Maldivian Rufiyaa', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'MVR',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'MWK' => array(
				'name'               => __( 'Malawian Kwacha', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'MWK',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'MXN' => array(
				'name'               => __( 'Mexican Peso', 'ppp-stripe' ),
				'symbol_left'        => 'MXN$',
				'symbol_right'       => 'MXN',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'MYR' => array(
				'name'               => __( 'Malaysian Ringgit', 'ppp-stripe' ),
				'symbol_left'        => '&#82;&#77;',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'MZN' => array(
				'name'               => __( 'Mozambican Metical', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'MZN',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'NAD' => array(
				'name'               => __( 'Namibian Dollar', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'NAD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'NGN' => array(
				'name'               => __( 'Nigerian Naira', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'NGN',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'NIO' => array(
				'name'               => __( 'Nicaraguan Córdoba', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'NIO',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'NOK' => array(
				'name'               => __( 'Norwegian Krone', 'ppp-stripe' ),
				'symbol_left'        => 'Kr',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'NPR' => array(
				'name'               => __( 'Nepalese Rupee', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'NPR',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'NZD' => array(
				'name'               => __( 'New Zealand Dollar', 'ppp-stripe' ),
				'symbol_left'        => 'NZ$',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'PAB' => array(
				'name'               => __( 'Panamanian Balboa', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'PAB',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'PEN' => array(
				'name'               => __( 'Peruvian Nuevo Sol', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'PEN',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'PGK' => array(
				'name'               => __( 'Papua New Guinean Kina', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'PGK',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'PHP' => array(
				'name'               => __( 'Philippine Peso', 'ppp-stripe' ),
				'symbol_left'        => '&#8369;',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'PKR' => array(
				'name'               => __( 'Pakistani Rupee', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'PKR',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'PLN' => array(
				'name'               => __( 'Polish Złoty', 'ppp-stripe' ),
				'symbol_left'        => '&#122;&#322;',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => '.',
				'decimal_separator'  => ',',
				'decimals'           => 2
			),
			'PYG' => array(
				'name'               => __( 'Paraguayan Guaraní', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'PYG',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 0,
				'american_express'   => false
			),
			'QAR' => array(
				'name'               => __( 'Qatari Riyal', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'QAR',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'RON' => array(
				'name'               => __( 'Romanian Leu', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'RON',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'RSD' => array(
				'name'               => __( 'Serbian Dinar', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'RSD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'RUB' => array(
				'name'               => __( 'Russian Ruble', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'RUB',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'RWF' => array(
				'name'               => __( 'Rwandan Franc', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'RWF',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 0
			),
			'SAR' => array(
				'name'               => __( 'Saudi Riyal', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'SAR',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'SBD' => array(
				'name'               => __( 'Solomon Islands Dollar', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'SBD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'SCR' => array(
				'name'               => __( 'Seychellois Rupee', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'SCR',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'SEK' => array(
				'name'               => __( 'Swedish Krona', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'kr',
				'symbol_padding'     => ' ',
				'thousand_separator' => ' ',
				'decimal_separator'  => ',',
				'decimals'           => 2
			),
			'SGD' => array(
				'name'               => __( 'Singapore Dollar', 'ppp-stripe' ),
				'symbol_left'        => 'S$',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'SHP' => array(
				'name'               => __( 'Saint Helenian Pound', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'SHP',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'SLL' => array(
				'name'               => __( 'Sierra Leonean Leone', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'SLL',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'SOS' => array(
				'name'               => __( 'Somali Shilling', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'SOS',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'SRD' => array(
				'name'               => __( 'Surinamese Dollar', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'SRD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'STD' => array(
				'name'               => __( 'São Tomé and Príncipe Dobra', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'STD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'SVC' => array(
				'name'               => __( 'Salvadoran Colón', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'SVC',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'SZL' => array(
				'name'               => __( 'Swazi Lilangeni', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'SZL',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'THB' => array(
				'name'               => __( 'Thai Baht', 'ppp-stripe' ),
				'symbol_left'        => '&#3647;',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'TJS' => array(
				'name'               => __( 'Tajikistani Somoni', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'TJS',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'TOP' => array(
				'name'               => __( 'Tongan Paʻanga', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'TOP',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'TRY' => array(
				'name'               => __( 'Turkish Lira', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'TRY',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'TTD' => array(
				'name'               => __( 'Trinidad and Tobago Dollar', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'TTD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'TWD' => array(
				'name'               => __( 'New Taiwan Dollar', 'ppp-stripe' ),
				'symbol_left'        => 'NT$',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'TZS' => array(
				'name'               => __( 'Tanzanian Shilling', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'TZS',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'UAH' => array(
				'name'               => __( 'Ukrainian Hryvnia', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'UAH',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'UGX' => array(
				'name'               => __( 'Ugandan Shilling', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'UGX',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'UYU' => array(
				'name'               => __( 'Uruguayan Peso', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'UYU',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'UZS' => array(
				'name'               => __( 'Uzbekistani Som', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'UZS',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'VEF' => array(
				'name'               => __( 'Venezuelan Bolívar', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'VEF',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'VND' => array(
				'name'               => __( 'Vietnamese Đồng', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'VND',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'VUV' => array(
				'name'               => __( 'Vanuatu Vatu', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'VUV',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 0
			),
			'WST' => array(
				'name'               => __( 'Samoan Tala', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'WST',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'XAF' => array(
				'name'               => __( 'Central African Cfa Franc', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'XAF',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 0
			),
			'XCD' => array(
				'name'               => __( 'East Caribbean Dollar', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'XCD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'XOF' => array(
				'name'               => __( 'West African Cfa Franc', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'XOF',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 0,
				'american_express'   => false
			),
			'XPF' => array(
				'name'               => __( 'Cfp Franc', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'XPF',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 0,
				'american_express'   => false
			),
			'YER' => array(
				'name'               => __( 'Yemeni Rial', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'YER',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'ZAR' => array(
				'name'               => __( 'South African Rand', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'ZAR',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			),
			'ZMW' => array(
				'name'               => __( 'Zambian Kwacha', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'ZMW',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2
			)
		);
	}

	/**************************************************
	 * FORM FIELDS                                    *
	 *                                                *
	 **************************************************/

	/**
	 * Add Qualpay plans to predefined choices for product field
	 *
	 * @todo cache this, because it calls on every form load
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param $predefined_choices
	 *
	 * @return mixed
	 */
	public function gform_predefined_choices( $predefined_choices ) {

        $environment = $this->get_qualpay_mode( $this->get_current_form() );

        if ( empty( $environment ) ) {

            return $predefined_choices;

        }

		$this->get_qualpay_api( $environment );

		$plans = $this->_gfp_qualpay_api->get_plans( array() );

		if ( $plans[ 'success' ] && ! empty( $plans[ 'response' ][ 'data' ] ) ) {

			$predefined_choice_options = array();

			foreach ( $plans[ 'response' ][ 'data' ] as $plan ) {

				$predefined_choice_options[] = "{$plan['plan_name']}|{$plan['plan_code']}|:{$plan['amt_tran']}";

			}

			if ( ! empty( $predefined_choice_options ) ) {

				$predefined_choices[ __( 'Qualpay Plans', 'gravityforms' ) ] = $predefined_choice_options;

			}

		}


		return $predefined_choices;
	}

	/****************************************************************************************************************
	 * FORM SETTINGS                                                                                                *
	 *                                                                                                              *
	 * @todo before deleting feed, check if another feed depends on it for customer information                     *
	 *                                                                                                              *
	 * @todo before allowing use customer information from previous feed, check that another feed had customer info *
	 *                                                                                                              *
	 ****************************************************************************************************************/

	/**
	 * @see    GFAddOn::form_settings_icon
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay  
	 *
	 * @return string
	 */
	public function form_settings_icon() {
		
		return '<img style="height:1em;" src="' . GFP_QUALPAY_URL . 'includes/images/qualpay-icon-white.png">';
	}

	/**
	 * @see    parent
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay  
	 *
	 * @return string
	 */
	public function form_settings_page_title() {

		return __( 'Qualpay Feed Setting', 'gravityformsqualpay' );

	}

	public function is_feed_edit_page() {
		$view        = rgget( 'view' );
		$id          = rgget( 'id' );
		$form        = GFAPI::get_form( $id );
		$environment = $this->get_qualpay_mode( $form );
		
		return $view === 'settings' && rgget( 'subview' ) === $this->get_slug();
	}

	/**
	 * Form Settings page
	 *
	 * Allow for both form settings and feeds
	 *
	 * @see    GFAddOn::form_settings_page
	 *
	 * @see    GFFeedAddOn::form_settings
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay  
	 *
	 * @param $form
	 */
	public function form_settings( $form ) {

		if ( $this->is_detail_page() ) {

			$feed_id = $this->get_current_feed_id();

			$this->feed_edit_page( $form, $feed_id );
			
		} else {
		
			$this->maybe_save_form_settings( $form );

			$form = $this->get_current_form();

			$settings = $this->get_form_settings( $form );

			$this->set_settings( $settings );

			//$sections = $this->form_settings_fields( $form );

			GFCommon::display_admin_message();

			$page_title = $this->form_settings_page_title();

			if ( empty( $page_title ) ) {

				//$page_title = rgar( $sections[ 0 ], 'title' );

				$sections[ 0 ][ 'title' ] = false;

			}

			?>

            <h3><span><?php echo '<img style="height:1em;" src="' . GFP_QUALPAY_URL . 'includes/images/qualpay-icon-white.png">'; ?><?php echo esc_html__( $page_title, 'gravityformsqualpay' ); ?></span></h3>
			
			<?php

			//$this->render_settings( $sections );

			$environment = $this->get_qualpay_mode( $form );

			//if ( ! empty( $environment ) ) {

			    $this->feed_list_page( $form );

			//}

		}

	}

	/**
	 * Qualpay environment setting for form
	 *
	 * @see    GFAddOn::form_settings_fields
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay  
	 *
	 * @param $form
	 *
	 * @return array
	 */
	public function form_settings_fields( $form ) {
		
		$choices = array();
		$choices[] = array(
			'label' => __( 'Sandbox', 'gravityformsqualpayform' ),
			'value' => 'test',
		);
		$choices[] = array(
			'label' => __( 'Production', 'gravityformsqualpayform' ),
			'value' => 'live'
		);

		return array(
			'environment' => array(
				'title' => '',
				'fields' => array(
				array(
				'label'        => __( 'Environment', 'gravityformsqualpayform' ),
				'type'         => 'radio',
				'name'         => 'mode',
				'tooltip'      => 'Select the environment that will be used to process this
				form. This cannot be changed later.',
				'choices'      => $choices,
				'horizontal'   => true,
				'default_value' => $this->get_qualpay_mode($form),
				)
				)
			)
		);
	}

	/**
	 * Don't add default save button if environment is already set
     *
     * Environment cannot be changed after it's already been set, otherwise form data & entries will not work
     *
     * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay  
	 *
	 * @param $sections
	 *
	 * @return array
	 */
	public function add_default_save_button( $sections ) {
		
	    if ( ! $this->is_form_settings( 'gravityformsqualpay' ) ) {
			return parent::add_default_save_button( $sections );
        }

        if ( ! empty( $_GET['fid'] ) || isset($_GET['fid']) ) {
	        return parent::add_default_save_button( $sections );
        }

	    $environment = $this->get_qualpay_mode( $this->get_current_form() );

	   // if ( empty( $environment ) ) {
			return parent::add_default_save_button( $sections );

	    //}

	    return $sections;
	}

	/**
	 * Get merchant settings for chosen environment
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay  
	 *
	 * @param array $form
	 *
	 * @return false|null|true
	 */
	public function maybe_save_form_settings( $form ) {

		$result = parent::maybe_save_form_settings( $form );

		if ( empty( $result ) ) {

			return $result;

		}

		$form = $this->get_current_form();

		$environment = $this->get_qualpay_mode( $form );
		
		$this->get_qualpay_api( $environment );


		$merchant_settings = $this->_gfp_qualpay_api->get_merchant_settings( $this->get_plugin_setting( "merchant_id_{$environment}" ) );

		if ( $merchant_settings[ 'success' ] && ! empty( $merchant_settings[ 'response' ][ 'data' ] ) ) {

			foreach ( $merchant_settings[ 'response' ][ 'data' ][ 'payment_profiles' ] as $profile ) {

				$profiles[] = array(
					'id'   => $profile[ 'profile_id' ],
					'name' => empty( $profile[ 'profile_name' ] ) ? '' : $profile[ 'profile_name' ]
				);

			}

			if ( ! empty( $profiles ) ) {

				update_option( 'gravityformsaddon_' . $this->_slug . '_payment_profiles', $profiles );

			}

			$currencies = array();

			$currency_codes = json_decode( GFP_Qualpay_API::get_currency_codes(), true );

			foreach ( $merchant_settings[ 'response' ][ 'data' ][ 'payments_accepted' ] as $payment ) {

				$card_types[] = $payment[ 'card_type' ];

				foreach ( $payment[ 'currency' ] as $currency_code ) {

					if ( ! in_array( $currency_codes[ $currency_code ][ 'alpha' ], $currencies ) ) {

						$currencies[] = $currency_codes[ $currency_code ][ 'alpha' ];
					}

				}

			}


			if ( ! empty( $card_types ) ) {

				update_option( 'gravityformsaddon_' . $this->_slug . '_card_types', $card_types );

			}

			if ( ! empty( $currencies ) ) {

				update_option( 'gravityformsaddon_' . $this->_slug . '_currencies', $currencies );

			}

		}

		return $result;
	}

	/**
	 * Get merchant's supported Qualpay card types
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay  
	 *
	 * @return mixed
	 */
	public function get_card_types() {

		return get_option( 'gravityformsaddon_' . $this->_slug . '_card_types' );
	}

	/**
	 * Get merchant's supported currencies
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay  
	 *
	 * @return mixed
	 */
	public function get_currencies() {

		return get_option( 'gravityformsaddon_' . $this->_slug . '_currencies' );
	}

	/**
	 * Get merchant's payment profiles
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay  
	 *
	 * @return mixed
	 */
	public function get_payment_profiles() {
		
		//return get_option( 'gravityformsaddon_' . $this->_slug . '_payment_profiles' );
		$form = $this->get_current_form();

		$environment = $this->get_qualpay_mode( $form );

		$merchant_settings = $this->_gfp_qualpay_api->get_merchant_settings_api_key( $this->get_plugin_setting( "merchant_id_{$environment}" ),$environment ,  $this->get_plugin_setting( "api_key_{$environment}" ) );

		if ( $merchant_settings[ 'success' ] && ! empty( $merchant_settings[ 'response' ][ 'data' ] ) ) {

			foreach ( $merchant_settings[ 'response' ][ 'data' ][ 'payment_profiles' ] as $profile ) {

				$profiles[] = array(
					'id'   => $profile[ 'profile_id' ],
					'name' => empty( $profile[ 'label' ] ) ? '' : $profile[ 'label' ]
				);

			}
		}
		return $profiles;
	}

	/**
	 * @see    GFFeedAddOn::can_create_feed
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay  
	 *
	 * @return bool
	 */
	public function can_create_feed() {

		 $settings = $this->get_plugin_settings();

		return ((! empty( $settings[ "merchant_id_test" ] ) && ! empty( $settings[ "api_key_test" ] )) ||  (! empty( $settings[ "merchant_id_live" ] ) && ! empty( $settings[ "api_key_live" ] )) );

	}

	public function feed_list_title() {}

	/**
	 * @see    GFFeedAddOn::feed_list_columns
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay  
	 *
	 * @return array
	 */
	public function feed_list_columns() {

		return array(
			'feedName'     => esc_html__( 'Name', 'gravityformsqualpay' ),
			'payment_type' => esc_html__( 'Payment Type', 'gravityformsqualpay' ),
			'transaction'  => esc_html__( 'Transaction Type', 'gravityformsqualpay' )
		);

	}

	/**
	 * Payment type feed list column
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay  
	 *
	 * @param $feed
	 *
	 * @return string
	 */
	public function get_column_value_payment_type( $feed ) {

		switch ( rgar( $feed[ 'meta' ], 'payment_type' ) ) {

			case 'subscription' :

				return esc_html__( 'Subscription', 'gravityformsqualpay' );

				break;

			case 'one_time' :

				return esc_html__( 'One-Time', 'gravityformsqualpay' );

				break;

		}

	}

	/**
	 * Transaction type feed list column
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay  
	 *
	 * @param $feed
	 *
	 * @return string
	 */
	public function get_column_value_transaction( $feed ) {

		switch ( rgar( $feed[ 'meta' ], 'payment_type' ) ) {

			case 'subscription' :

				return ucwords( $this->get_setting( 'plan_type', '', $feed[ 'meta' ] ) );

				break;

			case 'one_time' :

				return ucwords( $this->get_setting( 'transaction_type', '', $feed[ 'meta' ] ) );

				break;

		}

	}

	/**
	 * @see    GFFeedAddOn::feed_settings_fields
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay  
	 *
	 * @return array
	 */
	public function feed_settings_fields() {

		$submit_form_js = "jQuery(this).parents('form').submit();jQuery( this ).parents( 'form' ).find(':input').prop('disabled', true );";

		$settings = $this->get_plugin_settings();
		$choices = array();
		
		if(! empty( $settings[ "merchant_id_test" ] ) && ! empty( $settings[ "api_key_test" ] ) ) {
			$choices[] = array(
				'label' => __( 'Sandbox', 'gravityformsqualpayform' ),
				'value' => 'test',
			);
		} 
		if(! empty( $settings[ "merchant_id_live" ] ) && ! empty( $settings[ "api_key_live" ] ) ) {
			$choices[] = array(
				'label' => __( 'Production', 'gravityformsqualpayform' ),
				'value' => 'live'
			);
		}

		$feed_field_name = array(
			'name'     => 'feedName',
			'type'     => 'text',
			'required' => true,
			'label'    => __( 'Name', 'gravityformsqualpay' ),
			'tooltip'  => __( 'Name for this feed', 'gravityformsqualpay' ),
			'class'    => 'medium',
		);

		$feed_mode = array(
			'name'     => 'mode',
			'type'     => 'radio',
			'required' => true,
			'label'    => __( 'Environment', 'gravityformsqualpayform' ),
			'tooltip'      => 'Select the environment that will be used to process this
				form. You need to add credentials in plugin settings page and you can see your options here.',
			'class'    => 'medium',
			'choices'      => $choices,
			'horizontal'   => true,
			'onchange' => $submit_form_js
		);


		$feed_field_payment_type = array(
			'name'     => 'payment_type',
			'type'     => 'select',
			'required' => true,
			'label'    => esc_html__( 'Payment Type', 'gravityformsqualpay' ),
			'choices'  => array(
				array(
					'label' => esc_html__( 'Select a payment type', 'gravityformsqualpay' ),
					'value' => ''
				),
				array(
					'label' => esc_html__( 'One-Time Payment', 'gravityformsqualpay' ),
					'value' => 'one_time'
				),
				array(
					'label' => esc_html__( 'Subscription', 'gravityformsqualpay' ),
					'value' => 'subscription'
				),
			),
			'onchange' => $submit_form_js
		);


		$feed_field_transaction_fields = array(
			'name'       => 'transaction_fields[]',
			'type'       => 'select',
			'multiple'   => 'multiple',
			'required'   => true,
			'label'      => esc_html__( 'Transaction Fields', 'gravityformsqualpay' ),
			'tooltip'    => '<h6>' . esc_html__( 'Transaction Fields', 'gravityformsqualpay' ) . '</h6>' . esc_html__( 'Select the product fields that will be included in this transaction. Choose Form Total to include all product fields.', 'gravityformsqualpay' ),
			'choices'    => $this->product_amount_choices(),
			'rules' => array(
				array(
					'field'  => 'payment_type',
					'value'    => 'one_time',
				 ),
				 array(
					'field'  => 'plan_type',
					'value'    => 'one_off',
				 ),
			)
		);

		$feed_field_plan_type = array(
			'name'     => 'plan_type',
			'type'     => 'select',
			'required' => true,
			'label'    => esc_html__( 'Plan Type', 'gravityformsqualpay' ),
			'choices'  => array(
				array(
					'label' => esc_html__( 'Select a plan type', 'gravityformsqualpay' ),
					'value' => ''
				),
				array(
					'label' => esc_html__( 'On Plan', 'gravityformsqualpay' ),
					'value' => 'existing'
				),
				array(
					'label' => esc_html__( 'Off Plan', 'gravityformsqualpay' ),
					'value' => 'one_off'
				),
			),
			'onchange' => $submit_form_js
		);


		$feed_field_transaction_type = array(
			'name'       => 'transaction_type',
			'type'       => 'select',
			'required'   => true,
			'label'      => esc_html__( 'Transaction Type', 'gravityformsqualpay' ),
			'choices'    => array(
				array(
					'label' => esc_html__( 'Select a transaction type', 'gravityformsqualpay' ),
					'value' => ''
				),
				array(
					'label' => esc_html__( 'Sale', 'gravityformsqualpay' ),
					'value' => 'sale'
				),
				array(
					'label' => esc_html__( 'Authorization', 'gravityformsqualpay' ),
					'value' => 'authorization'
				),
			),
			'dependency' => array( 'field' => 'payment_type', 'values' => array( 'one_time' ) ),
			'onchange' => $submit_form_js

		);


		$feed_field_plan_field = array(
			'name'       => 'plan_code',
			'type'       => 'field_select',
			'label'      => esc_html__( 'Plan', 'gravityformsqualpay' ),
			'tooltip'    => '<h6>' . esc_html__( 'Plan', 'gravityformsqualpay' ) . '</h6>' . esc_html__( 'Choose plan.', 'gravityformsqualpay' ),
			'args'       => array(
				'field_types'    => array( 'product' ),
				'input_types'    => array( 'select', 'radio' ),
				'append_choices' => $this->get_plan_choices(),
				'disable_first_choice' => true
			),
			
			'description'    => esc_html__( 'Note: Plans must be created and managed from the Qualpay Manager.', 'gravityformsqualpay' ),
			'dependency' => array( 'field' => 'plan_type', 'values' => array( 'existing' ) )
		);


		$feed_field_plan_desc = array(
			'name'       => 'plan_desc',
			'type'       => 'select_custom',
			'label'      => esc_html__( 'Subscription Description', 'gravityformsqualpay' ),
			'choices'    => $this->get_field_map_choices( rgget( 'id' ) ),
			'dependency' => array( 'field' => 'plan_type', 'values' => array( 'one_off' ) )
		);

		$feed_field_plan_frequency = array(
			'name'       => 'plan_frequency',
			'type'       => 'select',
			'required'   => true,
			'label'      => esc_html__( 'Subscription Frequency', 'gravityformsqualpay' ),
			'choices'    => array(
				array(
					'label' => esc_html__( 'Select plan frequency', 'gravityformsqualpay' ),
					'value' => ''
				),
				array(
					'label' => esc_html__( 'Weekly', 'gravityformsqualpay' ),
					'value' => 0
				),
				array(
					'label' => esc_html__( 'Bi-Weekly', 'gravityformsqualpay' ),
					'value' => 1
				),
				array(
					'label' => esc_html__( 'Monthly', 'gravityformsqualpay' ),
					'value' => 3
				),
				array(
					'label' => esc_html__( 'Quarterly', 'gravityformsqualpay' ),
					'value' => 4
				),
				array(
					'label' => esc_html__( 'BiAnnually', 'gravityformsqualpay' ),
					'value' => 5
				),
				array(
					'label' => esc_html__( 'Annually', 'gravityformsqualpay' ),
					'value' => 6
				)
			),
			'onchange'   => "if(3==jQuery('#plan_frequency').val()){jQuery('#gaddon-setting-row-plan_interval').show('slow'); } else {jQuery('#gaddon-setting-row-plan_interval').hide('slow'); }",
			'dependency' => array( 'field' => 'plan_type', 'values' => array( 'one_off' ) )
		);

		$feed_field_plan_interval = array(
			'name'       => 'plan_interval',
			'type'       => 'select',
			'label'      => esc_html__( 'Monthly Interval', 'gravityformsqualpay' ),
			'tooltip'    => '<h6>' . esc_html__( 'Monthly Interval', 'gravityformsqualpay' ) . '</h6>' . esc_html__( 'Number of months in the subscription cycle e.g. bill every 2 months', 'gravityformsqualpay' ),
			'choices'    => $this->get_numeric_choices( 1, 12 ),
			'class'      => ( 3 == $this->get_setting( 'plan_frequency' ) ) ? '' : 'hidden',
			'dependency' => array( 'field' => 'plan_type', 'values' => array( 'one_off' ) )
		);

		$feed_field_plan_duration = array(
			'name'          => 'plan_duration',
			'type'          => 'text',
			'input_type'    => 'number',
			//'required'      => true,
			'label'         => esc_html__( 'Duration', 'gravityformsqualpay' ),
			'tooltip'       => '<h6>' . esc_html__( 'Plan Duration', 'gravityformsqualpay' ) . '</h6>' . esc_html__( 'Number of billing cycles, used for installment payments. Leave blank if you are billing your customer indefinitely.', 'gravityformsqualpay' ),
			'min'           => - 1,
			//'default_value' => - 1,
			'dependency'    => array( 'field' => 'plan_type', 'values' => array( 'one_off' ) )
		);

		$feed_field_setup_fee = array(
			'name'       => 'setup_fee',
			'type'       => 'setup_fee',
			'label'      => esc_html__( 'Setup Fee', 'gravityformsqualpay' ),
			'dependency' => array( 'field' => 'plan_type', 'values' => array( 'one_off' ) )
		);

		$feed_field_cancel_setup_fail = array(
			'name'       => 'cancel_setup_fail',
			'type'       => 'checkbox',
			'label'      => esc_html__( 'Cancel On Setup Fail', 'gravityformsqualpay' ),
			'dependency' => array( 'field' => 'plan_type', 'values' => array( 'one_off' ) ),
			'choices'    => array(
				array(
					'name'   => 'cancel_setup_fail',
					'label'  => 'Enabled',
					'value'  => true,
				)
			),
		);


		$feed_field_start_date = array(
			'name'       => 'start_date',
			'type'       => 'field_select',
			'label'      => esc_html__( 'Start Date', 'gravityformsqualpay' ),
			/*'args'       => array( 
				'fields' => 
					array( 'field_types'    => array( 'date' ) ),
				 
				'disable_first_choice' => true), */

			'args'       => array(
					'field_types'    => array( 'date' ),
				//	'disable_first_choice' => true,
			),  
			'dependency' => 'plan_type'
		);

		$use_previous_feed_customer_info = $this->get_setting( 'use_previous_feed_customer_info' );

		$feed_field_use_previous_feed_customer_info = array(
			'name'       => 'customer_info_checkbox',
			'type'       => ( $use_previous_feed_customer_info || 1 < count( $this->get_active_feeds( rgget( 'id' ) ) ) ) ? 'checkbox' : 'hidden',
			'horizontal' => true,
			'choices'    => array(
				array(
					'label'         => esc_html__( 'Use customer information from previous feed', 'gravityformsqualpay' ),
					'name'          => 'use_previous_feed_customer_info',
					'default_value' => 0,
					'onchange'      => "if(jQuery(this).prop('checked')){jQuery('#gaddon-setting-row-customer_info').hide('slow').find('select').val('');jQuery('#gaddon-setting-row-billing').hide('slow').find('select').val('');jQuery('#gaddon-setting-row-shipping').hide('slow').find('select').val(''); } else {jQuery('#gaddon-setting-row-customer_info').show('slow');jQuery('#gaddon-setting-row-billing').show('slow');jQuery('#gaddon-setting-row-shipping').show('slow'); }"
				),
			)
		);

		$feed_field_customer_information = array(
			'name'       => 'customer_info',
			'type'       => 'field_map',
			'label'      => esc_html__( 'Customer Information', 'gravityformsqualpay' ),
			// 'dependency' => array(
			// 	'field'      => 'use_previous_feed_customer_info',
			// 	'comparison' => 'isnot',
			// 	'values'     => ( '1' )
			// ),
			'rules'      => array(
				array(
				   'field'  => 'use_previous_feed_customer_info',
				   'operator' => 'isnot',
				   'value'    => '1',
				),
   			),
			'field_map'  => $this->customer_info_fields()
		);

		$feed_field_billing_information = array(
			'name'       => 'billing',
			'type'       => 'field_map',
			'label'      => esc_html__( 'Billing Information', 'gravityformsqualpay' ),
			// 'dependency' => array(
			// 	'field'      => 'use_previous_feed_customer_info',
			// 	'comparison' => 'isnot',
			// 	'values'     => ( '1' )
			// ),
			'rules'      => array(
				array(
				   'field'  => 'use_previous_feed_customer_info',
				   'operator' => 'isnot',
				   'value'    => '1',
				),
   			),
			'field_map'  => $this->address_fields()
		);

		$feed_field_shipping_information = array(
			'name'       => 'shipping',
			'type'       => 'field_map',
			'label'      => esc_html__( 'Shipping Information', 'gravityformsqualpay' ),
			// 'dependency' => array(
			// 	'field'      => 'use_previous_feed_customer_info',
			// 	'comparison' => 'isnot',
			// 	'values'     => ( '1' )
			// ),
			'rules'      => array(
				array(
				   'field'  => 'use_previous_feed_customer_info',
				   'operator' => 'isnot',
				   'value'    => '1',
				),
   			),
			'field_map'  => $this->address_fields()
		);


		$feed_field_payment_profile = array(
			'name'       => 'payment_profile',
			'type'       => 'select',
			'label'      => esc_html__( 'Payment Profile', 'gravityformsqualpay' ),
			'choices'    => $this->get_profile_choices(),
			'rules'      => array(
				array(
				   'field'  => 'payment_type',
				   'value'    => 'one_time',
				),
				 array(
				   'field'  => 'plan_type',
				   'value'    => 'one_off',
				),
		   ),
				'logic'  => 'any'
		);

		$feed_field_email_receipt = array(
			'name'       => 'email_receipt_checkbox',
			'type'       => 'checkbox',
			'label'      => __( 'Email receipt', 'gravityformsqualpay' ),
			'choices'    => array(
				array(
					'name'          => 'email_receipt',
					'label'         => '',
					'default_value' => '0',
				)
			),
			'dependency' => array( 'field' => 'payment_type', 'values' => array( 'one_time' ) )
		);

		$feed_field_purchase_id = array(
			'name'       => 'purchase_id',
			'type'       => 'field_select',
			'label'      => esc_html__( 'Purchase ID', 'gravityformsqualpay' ),
			'tooltip'    => '<h6>' . esc_html__( 'Purchase ID', 'gravityformsqualpay' ) . '</h6>' . esc_html__( 'Also referred to as the invoice number', 'gravityformsqualpay' ),
			'dependency' => array( 'field' => 'payment_type', 'values' => array( 'one_time' ) )
		);

		$feed_field_report_data = array(
			'name'           => 'report_data',
			'type'           => 'dynamic_field_map',
			'label'          => __( 'Report Data', 'gravityformsqualpay' ),
			'tooltip'        => __( 'Enter your custom field name, then select the form field with the value for that field', 'gravityformsqualpay' ),
			'disable_custom' => false,
			'dependency'     => array( 'field' => 'payment_type', 'values' => array( 'one_time' ) )
		);


		$feed_field_conditional_logic = array(
			'name'    => 'conditionalLogic',
			'label'   => esc_html__( 'Conditional Logic', 'gravityformsqualpay' ),
			'type'    => 'feed_condition',
			'tooltip' => '<h6>' . esc_html__( 'Conditional Logic', 'gravityformsqualpay' ) . '</h6>' . esc_html__( 'When conditions are enabled, form submissions will only be sent to the payment gateway when the conditions are met. When disabled, all form submissions will be sent to the payment gateway.', 'gravityformsqualpay' )
		);

		$feed_field_ach_on_off = array(
			'name'       => 'achOnOff_checkbox',
			'type'       => 'checkbox',
			'label'      => __( 'Enable ACH Payments', 'gravityformsqualpay' ),
			'tooltip'    => '<h6>' . esc_html__( 'ACH', 'gravityformsqualpay' ) . '</h6>' . esc_html__( 'Qualpay ACH Payments requires a separate merchant account application.  Contact Qualpay at <a href="mailto:sales@qualpay.com">sales@qualpay.com</a> if you need to apply for ACH Payments.', 'gravityformsqualpay' ),
			'choices'    => array(
				array(
					'name'          => 'achOnOff',
					'label'         => '',
					'default_value' => '1',
				)
			),
			'dependency' => array( 'field' => 'transaction_type', 'values' => array( 'sale' ) )
		);

		$feed_field_custom_css = array(
			'name'       => 'custom_css',
			'type'       => 'textarea',
			'label'      => __( 'Custom CSS for Embedded Fields', 'gravityformsqualpay' ),
			'class'   => 'medium',
			'choices'    => array(
				array(
					'name'          => 'custom_css',
					'label'         => ''
				)
			)
		);
		

		$sections = array(
			'section_feed_name'                 => array(
				'fields' => array(
					$feed_field_name,
					$feed_mode
				)
			),
			'section_payment_type'              => array(
				'fields' => array(
					$feed_field_payment_type
				)
			),
			'section_one_time_payment_settings' => array(
				'title'      => esc_html__( 'One-Time Payment Settings', 'gravityformsqualpay' ),
				'dependency' => array(
					'field'  => 'payment_type',
					'values' => array( 'one_time' )
				),
				'fields'     => array(
					$feed_field_transaction_fields,
					$feed_field_transaction_type,
					$feed_field_ach_on_off
				)
			),
			'section_subscription_settings'     => array(
				'title'      => esc_html__( 'Subscription Payment Settings', 'gravityformsqualpay' ),
				'dependency' => array(
					'field'  => 'payment_type',
					'values' => array( 'subscription' )
				),
				'fields'     => array(
					$feed_field_plan_type,
					$feed_field_plan_field,
					$feed_field_transaction_fields,
					$feed_field_plan_desc,
					$feed_field_plan_frequency,
					$feed_field_plan_interval,
					$feed_field_plan_duration,
					$feed_field_setup_fee,
					$feed_field_cancel_setup_fail,
					$feed_field_start_date
				)
			),
			'section_customer_info'             => array(
				'title'      => __( 'Customer Information', 'gravityformsqualpay' ),
				'dependency' => 'payment_type',
				'fields'     => array(
					$feed_field_use_previous_feed_customer_info,
					$feed_field_customer_information,
					$feed_field_billing_information,
					$feed_field_shipping_information
				)
			),
			'section_options'                   => array(
				'title'      => __( 'Options', 'gravityformsqualpay' ),
				'dependency' => array( 'field' => 'payment_type', 'values' => array( 'one_time' ) ),
				'fields'     => array(
					$feed_field_payment_profile,
					$feed_field_email_receipt,
					$feed_field_purchase_id,
					$feed_field_report_data,
					$feed_field_custom_css
				)
			),
			'section_conditional_logic'         => array(
				'title'      => __( 'Conditional Logic', 'gravityformsqualpay' ),
				'dependency' => 'payment_type',
				'fields'     => array(
					$feed_field_conditional_logic
				)
			)
		);

		return $sections;

	}

	/**
	 * Get Qualpay plans formatted as choices
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay  
	 *
	 *
	 * @return mixed
	 */
	public function get_plan_choices() {

		$choices = array();

		$form = $this->get_current_form();

		$environment = $this->get_qualpay_mode( $form );

		$this->get_qualpay_api( $environment );
		//exit;
		$choices[] = array( 'label' => 'Select a Plan', 'value' => '' );
		$plans = $this->_gfp_qualpay_api->get_plans( array() );

		if ( $plans[ 'success' ] && ! empty( $plans[ 'response' ][ 'data' ] ) ) {

			foreach ( $plans[ 'response' ][ 'data' ] as $plan ) {

				$choices[] = array( 'label' => $plan[ 'plan_name' ], 'value' => "plan_{$plan['plan_code']}" );

			}

		}


		return $choices;
	}

	public function get_date_choices() {

		$choices = array();
		
		$choices[] = array( 'label' => 'Select a Date', 'value' => '' );
		$choices[] = array( 'label' => 'date', 'value' => 'date' );

		return $choices;
	}

	/**
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay  
	 *
	 * @return array
	 */
	public function customer_info_fields() {

		$fields = array(
			array(
				'name'     => 'email',
				'label'    => esc_html__( 'Email', 'gravityformsqualpay' ),
				'required' => ( 1 < count( $this->get_active_feeds( rgget( 'id' ) ) ) ) ? false : true
			),
			array(
				'name'     => 'first_name',
				'label'    => esc_html__( 'First Name', 'gravityformsqualpay' ),
				'required' => ( 1 < count( $this->get_active_feeds( rgget( 'id' ) ) ) ) ? false : true
			),
			array(
				'name'     => 'last_name',
				'label'    => esc_html__( 'Last Name', 'gravityformsqualpay' ),
				'required' => ( 1 < count( $this->get_active_feeds( rgget( 'id' ) ) ) ) ? false : true
			),
			array(
				'name'     => 'firm_name',
				'label'    => esc_html__( 'Business Name', 'gravityformsqualpay' ),
				'required' => false
			),
			array(
				'name'     => 'phone',
				'label'    => esc_html__( 'Phone', 'gravityformsqualpay' ),
				'required' => false
			)
		);

		return $fields;
	}

	/**
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay  
	 *
	 * @return array
	 */
	public function address_fields() {

		$fields = array(
			array(
				'name'     => 'addr1',
				'label'    => esc_html__( 'Address Line 1', 'gravityformsqualpay' ),
				'required' => false
			),
			array(
				'name'     => 'addr2',
				'label'    => esc_html__( 'Address Line 2', 'gravityformsqualpay' ),
				'required' => false
			),
			array(
				'name'     => 'city',
				'label'    => esc_html__( 'City', 'gravityformsqualpay' ),
				'required' => false
			),
			array(
				'name'     => 'state',
				'label'    => esc_html__( 'State', 'gravityformsqualpay' ),
				'required' => false
			),
			array(
				'name'     => 'zip',
				'label'    => esc_html__( 'Zip', 'gravityformsqualpay' ),
				'required' => false
			),
			array(
				'name'     => 'country',
				'label'    => esc_html__( 'Country', 'gravityformsqualpay' ),
				'required' => false
			),
		);

		return $fields;
	}

	/**
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay  
	 *
	 * @return array
	 */
	public function get_profile_choices() {

		$choices = array(
			array(
				'label' => esc_html__( 'Select payment profile', 'gravityformsqualpay' ),
				'value' => ''
			)
		);


		$payment_profiles = $this->get_payment_profiles();

		foreach ( $payment_profiles as $profile ) {

			$choices[] = array(
				'label' => empty( $profile[ 'name' ] ) ? $profile[ 'id' ] : $profile[ 'name' ],
				'value' => $profile[ 'id' ]
			);
		}


		return $choices;
	}

	/**
	 * Implement field types and add exclude_ids
	 *
	 * @see    GFAddOn::get_form_fields_as_choices
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay  
	 *
	 * @param array $form - The form object
	 * @param array $args - Additional settings to check for (field and input types to include, callback for applicable
	 *                    input type)
	 *
	 * @return array The array of formatted form fields
	 */
	public function get_form_fields_as_choices( $form, $args = array() ) {

		$fields = array();

		if ( ! is_array( $form[ 'fields' ] ) ) {

			return $fields;

		}

		$args = wp_parse_args(
			$args, array(
				'exclude_ids' => array(),
				'field_types' => array(),
				'input_types' => array(),
				'callback'    => false
			)
		);

		foreach ( $form[ 'fields' ] as $field ) {

			if ( ! empty( $args[ 'exclude_ids' ] ) && in_array( $field->id, $args[ 'exclude_ids' ] ) ) {

				continue;

			}

			if ( ! empty( $args[ 'field_types' ] ) && ! in_array( $field->type, $args[ 'field_types' ] ) ) {

				continue;

			}

			$input_type               = GFFormsModel::get_input_type( $field );
			$is_applicable_input_type = empty( $args[ 'input_types' ] ) || in_array( $input_type, $args[ 'input_types' ] );

			if ( is_callable( $args[ 'callback' ] ) ) {
				$is_applicable_input_type = call_user_func( $args[ 'callback' ], $is_applicable_input_type, $field, $form );
			}

			if ( ! $is_applicable_input_type ) {
				continue;
			}

			if ( ! empty( $args[ 'property' ] ) && ( ! isset( $field->{$args[ 'property' ]} ) || $field->{$args[ 'property' ]} != $args[ 'property_value' ] ) ) {
				continue;
			}

			/*$inputs = $field->get_entry_inputs();

			if ( is_array( $inputs ) ) {

				// if this is an address field, add full name to the list
				if ( $input_type == 'address' ) {
					$fields[] = array(
						'value' => $field->id,
						'label' => GFCommon::get_label( $field ) . ' (' . esc_html__( 'Full', 'gravityforms' ) . ')'
					);
				}
				// if this is a name field, add full name to the list
				if ( $input_type == 'name' ) {
					$fields[] = array(
						'value' => $field->id,
						'label' => GFCommon::get_label( $field ) . ' (' . esc_html__( 'Full', 'gravityforms' ) . ')'
					);
				}
				// if this is a checkbox field, add to the list
				if ( $input_type == 'checkbox' ) {
					$fields[] = array(
						'value' => $field->id,
						'label' => GFCommon::get_label( $field ) . ' (' . esc_html__( 'Selected', 'gravityforms' ) . ')'
					);
				}

				foreach ( $inputs as $input ) {
					$fields[] = array(
						'value' => $input['id'],
						'label' => GFCommon::get_label( $field, $input['id'] )
					);
				}
			} elseif ( $input_type == 'list' && $field->enableColumns ) {
				$fields[] = array(
					'value' => $field->id,
					'label' => GFCommon::get_label( $field ) . ' (' . esc_html__( 'Full', 'gravityforms' ) . ')'
				);
				$col_index = 0;
				foreach ( $field->choices as $column ) {
					$fields[] = array(
						'value' => $field->id . '.' . $col_index,
						'label' => GFCommon::get_label( $field ) . ' (' . rgar( $column, 'text' ) . ')',
					);
					$col_index ++;
				}
			} else*/
			if ( ! $field->displayOnly ) {
				$fields[] = array( 'value' => $field->id, 'label' => GFCommon::get_label( $field ) );
			} else {
				$fields[] = array(
					'value' => $field->id,
					'label' => GFCommon::get_label( $field )
				);
			}

		}

		return $fields;
	}

	/***
	 * Allow multiple dependency fields
	 *
	 * Logic: 'all' or 'any'
	 *
	 * @see    GFFeedAddOn::setting_dependency_met()
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay  
	 *
	 * @param array|string $dependency - Field or input name of the "parent" field.
	 *
	 * @return bool - true if the "parent" field has been filled out and false if it has not.
	 *
	 */
	public function setting_dependency_met( $dependency ) {

		if ( ! $dependency ) {

			return true;

		}

		if ( is_callable( $dependency ) ) {

			return call_user_func( $dependency );

		}

		if ( empty( $dependency[ 'fields' ] ) ) {

			return $this->dependency_met( $dependency );

		} else {

			$logic = rgar( $dependency, 'logic' );

			$logic = ( empty( $logic ) || ! is_string( $logic ) ) ? 'any' : $logic;

			$dependencies_met = 0;

			foreach ( $dependency[ 'fields' ] as $dependency_field ) {

				$dependency_met = $this->dependency_met( $dependency_field );

				if ( ( 'any' == $logic ) && $dependency_met ) {

					return true;

				} else if ( 'all' == $logic ) {

					if ( $dependency_met ) {

						$dependencies_met ++;

					} else {

						return false;

					}

				}

			}

			if ( ( 'all' == $logic ) && ( count( $dependency[ 'fields' ] ) == $dependencies_met ) ) {

				return true;

			}

			return false;

		}
	}

	/**
	 * Allow comparison options in a dependency rule
	 *
	 * Logic: 'all' or 'any'
	 * Comparison: see GFFormsModel::matches_operation() for values
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay  
	 *
	 * @param $dependency
	 *
	 * @return bool
	 */
	private function dependency_met( $dependency ) {

		if ( is_array( $dependency ) ) {

			//supports: 'dependency' => array("field" => 'myfield', 'values' => array("val1", 'val2'))
			$dependency_field = $dependency[ 'field' ];

			$dependency_value = $dependency[ 'values' ];

		} else {

			//supports: 'dependency' => 'myfield'
			$dependency_field = $dependency;

			$dependency_value = '_notempty_';

		}

		if ( ! is_array( $dependency_value ) ) {

			$dependency_value = array( $dependency_value );

		}

		$current_value = $this->get_setting( $dependency_field );

		$comparison = isset( $dependency[ 'comparison' ] ) ? $dependency[ 'comparison' ] : '';

		$logic = is_array( rgar( $dependency, 'logic' ) ) ? $dependency[ 'logic' ][ 0 ] : rgar( $dependency, 'logic' );

		$dependency_matches = 0;

		foreach ( $dependency_value as $val ) {

			if ( empty( $comparison ) ) {

				if ( $current_value == $val ) {

					return true;

				}

				if ( $val == '_notempty_' && ! rgblank( $current_value ) ) {

					return true;

				}

			} else {

				$matches = GFFormsModel::matches_operation( $current_value, $val, $comparison );

				if ( ( empty( $logic ) || 'any' == $logic ) && $matches ) {

					return true;

				} else if ( 'all' == $logic ) {

					if ( $matches ) {

						$dependency_matches ++;

					} else {

						return false;

					}

				}

			}

		}

		if ( ! empty( $comparison ) && count( $dependency_value ) == $dependency_matches ) {

			return true;

		}

		return false;
	}

	/**************************************************
	 * FORM DISPLAY                                   *
	 *                                                *
	 **************************************************/

	/**
	 * Replace Gravity Forms credit card field with Qualpay embedded field and set default payment method
	 *
	 * @see    GF_Field_CreditCard::get_field_input
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay  
	 *
	 * @param string              $markup
	 * @param GF_Field_CreditCard $field
	 * @param string              $value
	 * @param int                 $lead_id
	 * @param int                 $form_id
	 *
	 * @return bool|string
	 */
	public function gform_field_input( $markup, $field, $value, $lead_id, $form_id ) {

		if ( ( 'creditcard' !== $field->type ) || ( $field->is_form_editor() || $field->is_entry_detail() || empty( $form_id ) || ! $this->has_feed( $form_id ) ) ) {

			return false;
		}


		$id = intval( $field->id );

		$html_input_id = "input_{$form_id}_{$id}";

		$class_suffix = '';

		$card_icons = '';

		$cards = GFCommon::get_card_types();

		$card_style = $field->creditCardStyle ? $field->creditCardStyle : 'style1';

		foreach ( $cards as $card ) {

			$style = '';

			$print_card = ( $field->is_card_supported( $card[ 'slug' ] ) ) ? true : false;

			if ( $print_card ) {

				$card_icons .= "<div class='gform_card_icon gform_card_icon_{$card['slug']}' {$style}>{$card['name']}</div>";

			}

		}

		$payment_methods = apply_filters( 'gform_payment_methods', array(), $field, $form_id );

		$payment_options = '';

		if ( is_array( $payment_methods ) ) {

			foreach ( $payment_methods as $payment_method ) {

				$posted_payment_method = rgpost( 'gform_payment_method' );

				if ( ! empty( $posted_payment_method ) ) {

					$checked = ( $payment_method[ 'key' ] == $posted_payment_method ) ? "checked='checked'" : '';

				} else {

					$checked = ( $payment_method[ 'default' ] ) ? "checked='checked'" : '';

				}

				$payment_options .= "<div class='gform_payment_option gform_payment_{$payment_method['key']}'><input type='radio' style='width:25px;' name='gform_payment_method' value='{$payment_method['key']}' id='gform_payment_method_{$payment_method['key']}' onclick='gformToggleCreditCard();' onkeypress='gformToggleCreditCard();' {$checked}/> {$payment_method['label']}</div>";

			}

		}

		$checked = rgpost( 'gform_payment_method' ) == 'creditcard' || rgempty( 'gform_payment_method' ) ? "checked='checked'" : '';

		$card_radio_button = empty( $payment_options ) ? '' : "<input type='radio' style='width:25px;' name='gform_payment_method' id='gform_payment_method_creditcard' value='creditcard' onclick='gformToggleCreditCard();' onkeypress='gformToggleCreditCard();' {$checked}/>";

		$card_icons = "{$payment_options}<div class='gform_card_icon_container gform_card_icon_{$card_style}'>{$card_radio_button}{$card_icons}</div>";

		$card_field = "<span class='ginput_full{$class_suffix}' id='{$html_input_id}_1_container' >
                                    {$card_icons}
                                    </span>";
		
		$feeds = $this->get_feeds( $form_id );
		$feed_meta = $feeds[0]['meta'];
		$tran_type = $feed_meta['transaction_type'];
		$achToogle = $feed_meta['achOnOff'];
		$custom_css = $feed_meta['custom_css'];
		$embedded_field = '<div id="qp-embedded-container" style="width:100%" align="center"></div>';
		$embedded_field .= '
		<input type="hidden" id="capture_id" name="capture_id" value="' . $tran_type . '" />
		<input type="hidden" id="achOnOff" name="achOnOff" value="' . $achToogle . '" />';
		if(isset($custom_css) && $custom_css != '') {
			echo wp_kses( "<style>".$custom_css."</style>", array('style' => array()) );
		}

		return "<div class='ginput_complex{$class_suffix} ginput_container ginput_container_creditcard' id='{$html_input_id}'>" . $card_field . $embedded_field . ' </div>';

	}

	public function gform_register_init_scripts( $form, $field_values, $is_ajax ) {

		if ( is_admin() ) {

			return false;
		}

		if ( $this->enqueue_creditcard_token_script( $form ) ) {

			$creditcard_field_id = $this->get_credit_card_field( $form )->id;

			unset( GFFormDisplay::$init_scripts[ $form[ 'id' ] ][ "creditcard_{$creditcard_field_id}_" . GFFormDisplay::ON_PAGE_RENDER ] );

		}

	}

	/**
	 * Get saved billing cards for the logged-in user
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay  
	 *
	 * @param array               $payment_methods
	 * @param GF_Field_CreditCard $field
	 * @param                     $form_id
	 *
	 * @return array
	 */
	public function gform_payment_methods( $payment_methods, $field, $form_id ) {

		if ( is_user_logged_in() && $this->has_feed( $form_id ) ) {

			$payment_methods = array();

			$user_id = get_current_user_id();
			
			$mode = $this->get_qualpay_mode( GFAPI::get_form( $form_id ) );
			$merchant_id = $this->get_plugin_setting( "merchant_id_{$mode}" );
	
			$cards = GFP_Qualpay_Customer_API::get_billing_cards( $user_id );

			$default_card = GFP_Qualpay_Customer_API::get_default_billing_card( $user_id );
			$customer_id = GFP_Qualpay_Customer_API::get_customer_id( $user_id ,$merchant_id ,$mode );

			$this->get_qualpay_api( $mode );
			$get_qualpay_card_ids = $this->_gfp_qualpay_api->get_customer_billing_cards($customer_id, $merchant_id);
			
			$card_ids = array();
			foreach ($get_qualpay_card_ids['response']['data']['billing_cards'] as $billing_card) {
				$card_ids[] = $billing_card['card_id'];
			}
			
			foreach ( $cards as $card ) {
				$card_id = $card['id'];
				if (strpos($card['mode'], $mode) !== false) {
					if (strpos($card['merchant_id'], $merchant_id) !== false) {
						if( in_array( $card_id, $card_ids ) ) {
							$payment_methods[] = array_merge( $card,
								array(
									'key'     => $card[ 'id' ],
									'label'   => strtoupper( $card[ 'type' ] ) . ' (' . __( 'ending in ', 'gravityformsqualpay' ) . $card[ 'last4' ] . ')',
									'default' => $card[ 'id' ] == $default_card
								)
							);
						}
					}
				}
			}

		}


		return $payment_methods;
	}

	/**
	 * Add Qualpay embedded field and token response fields
	 *
	 * Although it's not named intuitively, it's the perfect place to add our embedded field
	 *
	 * @see    GFPaymentAddOn::add_creditcard_token_input()
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay  
	 *
	 * @param string   $content
	 * @param GF_Field $field
	 * @param string   $value
	 * @param string   $entry_id
	 * @param string   $form_id
	 *
	 * @return string
	 */
	public function add_creditcard_token_input( $content, $field, $value, $entry_id, $form_id ) {

		if ( 'creditcard' !== $field->type || ! $this->has_feed( $form_id ) || is_admin() ) {

			return $content;

		}

		$form = GFAPI::get_form( $form_id );

		if ( ! $this->creditcard_token_info( $form ) ) {

			return $content;

		}

		$slug = str_replace( 'gravityforms', '', $this->_slug );

		$content .= "<input type='hidden' name='{$slug}_response' id='gf_{$slug}_response' value='" . rgpost( "{$slug}_response" ) . "' />";

		$content .= "<input type='hidden' name='{$slug}_error' id='gf_{$slug}_error' value='" . rgpost( "{$slug}_error" ) . "' />";

		$content .= "<input type='hidden' name='input_{$field->id}.1' id='input_{$form_id}_{$field->id}_1' value='" . rgpost( "input_{$field->id}_1" ) . "' />";

		$content .= "<input type='hidden' name='input_{$field->id}.4' id='input_{$form_id}_{$field->id}_4' value='" . rgpost( "input_{$field->id}_4" ) . "' />";

		$content .= "<input type='hidden' name='input_{$field->id}.5' id='input_{$form_id}_{$field->id}_5' value='" . rgpost( "input_{$field->id}_5" ) . "' />";

		return $content;

	}

	/**
	 * @see GFPaymentAddOn::creditcard_token_info
	 *
	 * @param mixed $form
	 *
	 * @return array
	 */
	public function creditcard_token_info( $form ) {

		if ( ! is_admin() ) {

			return $this->get_active_feeds( rgar( $form, 'id' ) );

		}

		return false;

	}

	/**
	 * Nothing to do here, we are using our own script since Qualpay doesn't allow control of token creation timing
	 *
	 * @see    GFPaymentAddOn::register_creditcard_token_script
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay  
	 *
	 * @param array $form
	 * @param array $field_values
	 * @param bool  $is_ajax
	 *
	 * @return void
	 */
	public function register_creditcard_token_script( $form, $field_values, $is_ajax ) {
	}

	/**
	 * Add JS variables needed for Qualpay embedded field
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay  
	 *
	 * @param $form
	 * @param $is_ajax
	 */
	public function frontend_script_enqueue_callback( $form, $is_ajax ) {

		$is_postback = $is_valid = false;

		$submission_info = isset( GFFormDisplay::$submission[ $form[ 'id' ] ] ) ? GFFormDisplay::$submission[ $form[ 'id' ] ] : false;

		if ( $submission_info ) {

			$is_postback = true;

			$is_valid = rgar( $submission_info, 'is_valid' ) || rgar( $submission_info, 'is_confirmation' );

		}

		if ( $is_postback && $is_valid ) {

			return;
		}

		
		$mode = $this->get_qualpay_mode( $form );

		$this->get_qualpay_api( $mode );

		$transient_key = $this->_gfp_qualpay_api->get_transient_key();
		
		//QA and sandbox changes
		if($mode == 'test') {
			$iniFilename = GFP_QUALPAY_PATH."qp.txt";
			$mode_env = 'test';    // default

			if( file_exists($iniFilename) ) {
				
				$props = parse_ini_file ($iniFilename);
				if( !empty($props['host']) ) {
					$mode_env = $props['host'];
				}
			}
		} else {
			$mode_env = 'live';
		}
		if ( $transient_key[ 'success' ] && ! empty( $transient_key[ 'response' ][ 'data' ] ) ) {


			$strings = array(
				'merchant_id'         => $this->get_plugin_setting( "merchant_id_{$mode}" ),
				'mode'                => $mode_env,
				'transientKey'        => $transient_key[ 'response' ][ 'data' ][ 'transient_key' ],
				'form_element_id'     => "gform_{$form['id']}",
				'formId'              => rgar( $form, 'id' ),
				'hasPages'            => GFCommon::has_pages( $form ),
				'pageCount'           => GFFormDisplay::get_max_page_number( $form ),
				'responseField'       => '#gf_' . str_replace( 'gravityforms', '', $this->_slug ) . '_response',
				'errorField'          => '#gf_' . str_replace( 'gravityforms', '', $this->_slug ) . '_error',
				'creditcard_field_id' => $this->get_credit_card_field( $form )->id,
				'credit_card_rules'   => $this->get_credit_card_field( $form )->get_credit_card_rules(),
				'is_postback'         => $is_postback,
				'transient_key_nonce' => wp_create_nonce( 'gaddon_qualpay_transient_key' ),
				'ajaxurl'             => admin_url( 'admin-ajax.php', isset ( $_SERVER[ "HTTPS" ] ) ? 'https://' : 'http://' )

			);

			wp_localize_script( 'gfp_qualpay_frontend', 'gfp_qualpay_frontend_strings', $strings );

		}

	}

	/**
	 * Get new transient key if form has validation error, which means visitor has to input their credit card again
	 *
	 * At this point, key has already been used to verify a card, which means it cannot be used again. However, there
	 * isn't a way to control when the Qualpay token action is triggered
	 *
	 * @todo even better would be to somehow re-use the card ID if the error wasn't with the card
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay  
	 */
	public function ajax_qualpay_transient_key() {

		check_ajax_referer( 'gaddon_qualpay_transient_key', 'gaddon_qualpay_transient_key' );


		$form_id = rgpost( 'form_id' );


		$mode = $this->get_qualpay_mode( GFAPI::get_form( $form_id ) );

		$this->get_qualpay_api( $mode );

		$transient_key = $this->_gfp_qualpay_api->get_transient_key();

		if ( $transient_key[ 'success' ] && ! empty( $transient_key[ 'response' ][ 'data' ] ) ) {

			wp_send_json_success( $transient_key[ 'response' ][ 'data' ][ 'transient_key' ] );

		}

		wp_send_json_error();
	}

	/**
	 * Get Qualpay mode for form
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay  
	 *
	 * @param $form
	 *
	 * @return array|string
	 */
	public function get_qualpay_mode( $form ) {
		// $form_settings = $this->get_form_settings( $form );
		$form_id = $form['id'];
		$feeds = $this->get_feeds( $form_id );
		if( is_admin() ) {
			$feed = $feeds['meta'];
		} else {
			$feed = $feeds[0]['meta'];
		}
		return $this->get_setting( 'mode', '', $feed );
	}

	/**************************************************
	 * FORM VALIDATION                                *
	 *                                                *
	 **************************************************/

	/**
	 * Check for Qualpay token or saved card in form submission
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay  
	 *
	 * @param $validation_result
	 * @param $value
	 * @param $form
	 * @param $field
	 *
	 * @return mixed
	 */
	public function gform_field_validation( $validation_result, $value, $form, $field ) {

		if ( 'creditcard' == $field->type && $this->has_feed( $form[ 'id' ] ) ) {

			$this->log_debug( 'Validating credit card field...' );


			$payment_method = rgpost( 'gform_payment_method' );

			$token = rgpost( 'qualpay_response' );


			if ( empty( $payment_method ) || 'creditcard' == $payment_method ) {

				$validation_result[ 'is_valid' ] = empty( $token ) ? false : true;

				if ( ! $validation_result[ 'is_valid' ] ) {

					$validation_result[ 'message' ] = rgpost( 'qualpay_error' );

					$this->log_error( "Error: {$validation_result[ 'message' ]}" );

				}

			} elseif ( ! empty( $payment_method ) ) {

				$validation_result[ 'is_valid' ] = true;

				$this->log_debug( 'Using a saved Qualpay card.' );

			}


			if ( $validation_result[ 'is_valid' ] ) {

				unset( $validation_result[ 'message' ] );

			}


		}


		return $validation_result;
	}

	/**
	 * @see    parent
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay  
	 *
	 * @param array $validation_result
	 *
	 * @return array
	 */
	public function validation( $validation_result ) {

		if ( ! $validation_result[ 'is_valid' ] ) {

			return $validation_result;

		}

		$form = $validation_result[ 'form' ];

		$entry = GFFormsModel::create_lead( $form );


		if ( $entry[ 'id' ] ) {

			$feeds_to_process = $this->get_feeds_by_entry( $entry[ 'id' ] );

		} elseif ( $form ) {

			$feeds = $this->get_feeds( $form[ 'id' ] );

			$feeds = $this->pre_process_feeds( $feeds, $entry, $form );

			foreach ( $feeds as $feed ) {

				if ( $feed[ 'is_active' ] && $this->is_feed_condition_met( $feed, $form, $entry ) ) {

					$feeds_to_process[] = $feed;

				}

			}

		}

		if ( empty( $feeds_to_process ) ) {

			return $validation_result;

		}

		foreach ( $feeds_to_process as $feed ) {

			$this->authorization = array();

			$this->get_submission_data( $feed, $form, $entry );

			if ( ( floatval( rgar( $this->current_submission_data, 'payment_amount' ) ) <= 0 ) && ( 'existing' !== $this->current_submission_data[ 'feed' ][ "meta" ][ "plan_type" ] ) ) {

				$this->log_debug( __METHOD__ . '(): Payment amount is zero or less. Not sending to payment gateway.' );

				continue;
			}


			$this->is_payment_gateway = true;

			$this->current_feed = $feed;


			$performed_authorization = false;


			switch ( $feed[ 'meta' ][ 'payment_type' ] ) {

				case 'one_time':

					$transaction_type = $this->get_setting( 'transaction_type', '', $feed[ 'meta' ] );

					if ( 'authorization' == $transaction_type ) {

						$this->authorization = $this->authorize( $feed, $this->current_submission_data, $form, $entry );

						$performed_authorization = true;

					} elseif ( 'sale' == $transaction_type ) {

						$this->authorization = $this->capture( $this->authorization, $feed, $this->current_submission_data, $form, $entry );

						$this->authorization[ 'captured_payment' ] = $this->authorization;

						$performed_authorization = true;

					}

					break;

				case 'subscription':

					$subscription = $this->subscribe( $feed, $this->current_submission_data, $form, $entry );

					$this->authorization[ 'is_authorized' ] = rgar( $subscription, 'is_success' );

					$this->authorization[ 'error_message' ] = rgar( $subscription, 'error_message' );

					$this->authorization[ 'subscription' ] = $subscription;

					$performed_authorization = true;

					break;
			}


			if ( $performed_authorization ) {

				$this->log_debug( __METHOD__ . "(): Authorization result for form #{$form['id']} feed #{$feed['id']} submission => " . print_r( $this->authorization, 1 ) );
			}

			if ( $performed_authorization && ! rgar( $this->authorization, 'is_authorized' ) ) {

				$validation_result = $this->get_validation_result( $validation_result, $this->authorization );

				GFFormDisplay::set_current_page( $validation_result[ 'form' ][ 'id' ], $validation_result[ 'credit_card_page' ] );


				$mode = $this->get_qualpay_mode( $form );

				$this->get_qualpay_api( $mode );

				$merchant_id = $this->get_plugin_setting( "merchant_id_{$mode}" );

				$this->log_debug( 'Validation failed. Reversing ' . count( $this->_transactions ) . ' transactions.' );

				foreach ( $this->_transactions as $transaction ) {

					switch ( $transaction[ 'action' ] ) {

						case 'authorize':

							$this->_gfp_qualpay_api->void_authorized_transaction( $merchant_id, $transaction[ 'transaction_id' ] );

							break;

						case 'capture':

							$this->_gfp_qualpay_api->refund( $merchant_id, $transaction[ 'transaction_id' ], $transaction[ 'amount' ] );

							break;

						case 'subscribe':

							$this->_gfp_qualpay_api->cancel_subscription( $transaction[ 'subscription' ][ 'subscription_id' ], $transaction[ 'customer_id' ] );

							break;

					}

					if ( $transaction[ 'submission_data' ][ 'add_new_customer' ] ) {

						$this->_gfp_qualpay_api->delete_customer( $transaction[ 'submission_data' ][ 'customer_id' ] );

					}

				}


				return $validation_result;

			}

			$this->_transactions[ $feed[ 'id' ] ] = array_merge( $this->authorization, array( 'submission_data' => $this->current_submission_data ) );

		}


		return $validation_result;
	}

	/**
	 * Get submitted form values for sending to Qualpay
	 *
	 * @see    GFPaymentAddOn::get_submission_data
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay  
	 *
	 * @param array $feed
	 * @param array $form
	 * @param array $entry
	 *
	 */
	public function get_submission_data( $feed, $form, $entry ) {

		if ( ! empty( $this->current_submission_data ) ) {

			$this->_previous_submission_data = $this->current_submission_data;

		}

		$this->current_submission_data = array(
			'feed'         => $feed,
			'form'         => $form,
			'entry'        => $entry,
			'form_title'   => $form[ 'title' ],
			'qualpay_mode' => $this->get_qualpay_mode( $form )
		);


		$this->get_customer();

		$this->get_billing_card();


		$profile_id = $this->get_setting( 'payment_profile', '', $feed[ 'meta' ] );
		

		switch ( $feed[ 'meta' ][ 'payment_type' ] ) {

			case 'one_time':

				$this->get_order_data( $feed, $form, $entry );

				$transaction_type = $this->get_setting( 'transaction_type', '', $feed[ 'meta' ] );

				$customer_info_first_name =  $this->get_setting( 'customer_info_first_name', '', $feed[ 'meta' ] );
				$customer_info_last_name =  $this->get_setting( 'customer_info_last_name', '', $feed[ 'meta' ] );
				$shipping_zip = $this->get_setting( 'shipping_zip', '', $feed[ 'meta' ] ); 
				$billing_zip = $this->get_setting( 'billing_zip', '', $feed[ 'meta' ] ); 
				$business_name =  $this->get_setting( 'customer_info_firm_name', '', $feed[ 'meta' ] );
				
				if($this->current_submission_data[ 'card' ][ 'type' ] == 'ACH') {
					if(isset($this->current_submission_data[ 'card' ][ 'type_id' ] ) && ($this->current_submission_data[ 'card' ][ 'type_id' ]  == 'K'  || $this->current_submission_data[ 'card' ][ 'type_id' ]  == 'V') && isset($entry[$business_name]) && $entry[$business_name] != '') {
						$cardholder_name = $entry[$business_name];
					} else {
						$cardholder_name = $entry[$customer_info_first_name]." ".$entry[$customer_info_last_name];
					}
				} else {
					$cardholder_name = $entry[$customer_info_first_name]." ".$entry[$customer_info_last_name];
				}

				if($billing_zip) {
					$avs_zip = $entry[$billing_zip];
				} else if($shipping_zip) {
					$avs_zip = $entry[$shipping_zip];
				} else {
					$avs_zip = '11111';
				}
				$length = 25;

				$purchase_id = $this->get_mapped_field_value( 'purchase_id', $form, $entry, $feed[ 'meta' ] );
				
				
				if(empty($purchase_id )) {
					$purchase_id = $form['title'];
				}

				if(strlen($purchase_id)<= $length) {
					$purchase_id = $purchase_id;
				} else{
					$purchase_id = substr($purchase_id,0,$length);
				}
				
				$this->current_submission_data[ $transaction_type ] = array(
					'amt_tran'         => $this->current_submission_data[ 'payment_amount' ],
					'avs_zip'			=> $avs_zip,
					'card_id'          => $this->current_submission_data[ 'card' ][ 'id' ],
					'customer_id'      => $this->current_submission_data[ 'customer_id' ],
					'line_items'       => json_encode( $this->current_submission_data[ 'line_items' ] ),
					'merchant_ref_num' => '',
					'profile_id'       => $profile_id,
					'purchase_id'      => $purchase_id,
					'report_data'      => $this->get_dynamic_field_map_values( 'report_data', $feed, $entry, $form ),
					'cardholder_name'	=> $cardholder_name
				);

				$email_receipt = $this->get_setting( 'email_receipt' ,'' , $feed[ 'meta' ] );
				
				if ( $email_receipt == '1' ) {

					$this->current_submission_data[ $transaction_type ][ 'email_receipt' ] = true;

					$this->current_submission_data[ $transaction_type ][ 'customer_email' ] = $this->current_submission_data[ 'customer_email' ];
				}

				
				break;

			case 'subscription':

				$plan_type = $this->get_setting( 'plan_type', '', $feed[ 'meta' ] );


				$start_date = $this->get_mapped_field_value( 'start_date', $form, $entry, $feed[ 'meta' ] );

				$start_date = $this->get_qualpay_formatted_date( $start_date, $this->get_setting( 'start_date', '', $feed[ 'meta' ] ), $form );


				if ( 'existing' == $plan_type ) {

					$plan_code = $this->get_setting( 'plan_code', '', $feed[ 'meta' ] );

					if ( false === strpos( $plan_code, 'plan_' ) ) {

						$entry_value = RGFormsModel::get_lead_field_value( $entry, GFAPI::get_field( $form, $plan_code ) );

						list( $plan_code, $price ) = explode( '|', $entry_value );

					} else {

						$plan_code = str_replace( 'plan_', '', $plan_code );
					}

					$this->current_submission_data[ 'subscription' ] = array(
						'customer_id' => $this->_current_customer_id,
						'date_start'  => $start_date,
						'plan_code'   => $plan_code
					);
				} elseif ( 'one_off' == $plan_type ) {

					$this->get_order_data( $feed, $form, $entry );

					$plan_frequency = $this->get_setting( 'plan_frequency', '', $feed[ 'meta' ] );

					$plan_desc = $this->get_setting( 'plan_desc', '', $feed[ 'meta' ] );

					$plan_desc = ( 'gf_custom' == $plan_desc ) ? $this->get_setting( 'plan_desc_custom', '', $feed[ 'meta' ] ) : $this->get_mapped_field_value( 'plan_desc', $form, $entry, $feed[ 'meta' ] );
					
					$plan_duration = $this->get_setting( 'plan_duration', '', $feed[ 'meta' ] );
					if(empty($plan_duration)) {
						$plan_duration = '-1';
					}
					$shipping_zip = $this->get_setting( 'shipping_zip', '', $feed[ 'meta' ] ); 
					$billing_zip = $this->get_setting( 'billing_zip', '', $feed[ 'meta' ] ); 
					
					$cancel_setup_fail = (boolval($this->get_setting( 'cancel_setup_fail', "0",  $feed['meta']) )? 'true' : 'false');

					if($billing_zip) {
						$avs_zip = $entry[$billing_zip];
					} else if($shipping_zip) {
						$avs_zip = $entry[$shipping_zip];
					} else {
						$avs_zip = '11111';
					}
					$this->current_submission_data[ 'subscription' ] = array(
						'customer_id'    		=> $this->_current_customer_id,
						'date_start'     		=> $start_date,
						'plan_desc'      		=> $plan_desc,
						'plan_frequency' 		=> $plan_frequency,
						'plan_duration'  		=> $plan_duration,
						'cancel_on_setup_fail' 	=> $cancel_setup_fail,
						'amt_tran'       		=> $this->current_submission_data[ 'payment_amount' ],
						'avs_zip'				=> $avs_zip,
						'profile_id'     		=> $profile_id
					);

					if ( 3 == $plan_frequency ) {

						$this->current_submission_data[ 'subscription' ][ 'interval' ] = $this->get_setting( 'plan_interval', '', $feed[ 'meta' ] );
					}

					if ( ! empty( $this->current_submission_data[ 'setup_fee' ] ) ) {

						$this->current_submission_data[ 'subscription' ][ 'amt_setup' ] = $this->current_submission_data[ 'setup_fee' ];
					}

				}

				break;

		}

		/**
		 * @see GFPaymentAddOn::get_submission_data
		 */
		$this->current_submission_data = gf_apply_filters( array(
			'gform_submission_data_pre_process_payment',
			$form[ 'id' ]
		), $this->current_submission_data, $feed, $form, $entry );

	}

	/**
	 * Get transaction amount and line items for Qualpay
	 *
	 * @see    parent
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay  
	 *
	 * @param array $feed
	 * @param array $form
	 * @param array $entry
	 *
	 * @return array
	 */
	public function get_order_data( $feed, $form, $entry ) {

		$products = GFCommon::get_product_fields( $form, $entry );

		$payment_fields = $this->get_setting( 'transaction_fields', '', $feed[ 'meta' ] );

		$setup_fee_field = rgar( $feed[ 'meta' ], 'setup_fee_enabled' ) ? $feed[ 'meta' ][ 'setup_fee_product' ] : false;

		$amount = 0;

		$line_items = array();

		$fee_amount = 0;

		foreach ( $products[ 'products' ] as $field_id => $product ) {

			if ( in_array( $field_id, $payment_fields ) || in_array( 'form_total', $payment_fields ) ) {

				$quantity = $product[ 'quantity' ] ? $product[ 'quantity' ] : 1;

				$product_price = GFCommon::to_number( $product[ 'price' ], $entry[ 'currency' ] );

				$options = array();

				if ( is_array( rgar( $product, 'options' ) ) ) {

					foreach ( $product[ 'options' ] as $option ) {

						$options[] = $option[ 'option_name' ];

						$product_price += $option[ 'price' ];

					}

				}

				$amount += $product_price * $quantity;

				$description = '';

				if ( ! empty( $options ) ) {

					$description = esc_html__( 'options: ', 'gravityforms' ) . ' ' . implode( ', ', $options );

				}

				if ( $product_price >= 0 ) {

					$line_items[] = array(
						'product_code'     => substr( "{$form['id']}_{$field_id}_" . str_replace( ' ', '_', $product[ 'name' ] ), 0, 12 ),
						'description'      => substr( "{$product[ 'name' ]} {$description}", 0, 26 ),
						'quantity'         => $quantity,
						'unit_cost'        => GFCommon::to_number( $product_price, $entry[ 'currency' ] ),
						'unit_of_measure'  => 'each',
						'debit_credit_ind' => 'D'
					);

				}

				continue;

			}

		}

		if ( ! empty( $setup_fee_field ) && in_array( $setup_fee_field, array_keys( $products['products'] ) ) ) {

		    $product = $products['products'][ $setup_fee_field ];

			$quantity = $product[ 'quantity' ] ? $product[ 'quantity' ] : 1;

			$product_price = GFCommon::to_number( $product[ 'price' ], $entry[ 'currency' ] );

			$fee_amount = $product_price * $quantity;

		}


		if ( ( ! empty( $products[ 'shipping' ][ 'id' ] ) ) && ( in_array( $products[ 'shipping' ][ 'id' ], $payment_fields ) || in_array( 'form_total', $payment_fields ) ) ) {

			$line_items[] = array(
				'product_code'     => substr( $products[ 'shipping' ][ 'id' ] . $products[ 'shipping' ][ 'name' ], 0, 12 ),
				'description'      => substr( $products[ 'shipping' ][ 'name' ], 0, 26 ),
				'quantity'         => 1,
				'unit_cost'        => GFCommon::to_number( $products[ 'shipping' ][ 'price' ], $entry[ 'currency' ] ),
				'unit_of_measure'  => 'each',
				'debit_credit_ind' => 'D'
			);

			$amount += $products[ 'shipping' ][ 'price' ];

		}


		$this->current_submission_data[ 'payment_amount' ] = $amount;

		$this->current_submission_data[ 'setup_fee' ] = $fee_amount;

		$this->current_submission_data[ 'line_items' ] = $line_items;


	}

	/**
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay  
	 */
	private function get_customer() {

		if ( is_user_logged_in() ) {
			$mode =  $this->current_submission_data[ 'qualpay_mode' ];
			$merchant_id = $this->get_plugin_setting( "merchant_id_{$mode}" );

			$customer_id = GFP_Qualpay_Customer_API::get_customer_id( get_current_user_id() , $merchant_id ,$mode );
			
		}

		if ( empty( $customer_id ) ) {


			$feed = $this->current_submission_data[ 'feed' ];


			if ( $this->get_setting( 'use_previous_feed_customer_info', '', $feed[ 'meta' ] ) && ! empty( $this->_current_customer_id ) ) {

				$this->current_submission_data[ 'customer_id' ] = $this->_current_customer_id;

				$this->get_address_from_previous_feed( 'shipping' );

				return;

			}

			$form = $this->current_submission_data[ 'form' ];

			$entry = $this->current_submission_data[ 'entry' ];


			foreach ( $this->get_field_map_fields( $feed, 'customer_info' ) as $name => $value ) {

				$field_value = $this->get_mapped_field_value( "customer_info_{$name}", $form, $entry, $feed[ 'meta' ] );

				if ( empty( $field_value ) ) {

					continue;
				}

				$this->current_submission_data[ "customer_{$name}" ] = $field_value;

			}

			unset( $name, $value, $field_value );


			$shipping = array();

			foreach ( $this->get_field_map_fields( $feed, 'shipping' ) as $name => $value ) {

				$shipping[ "shipping_{$name}" ] = $this->get_mapped_field_value( "shipping_{$name}", $form, $entry, $feed[ 'meta' ] );

			}

			unset( $name, $value );

			if ( ! empty( $shipping ) ) {

				$shipping[ 'shipping_first_name' ] = $this->current_submission_data[ 'customer_first_name' ];

				$shipping[ 'shipping_last_name' ] = $this->current_submission_data[ 'customer_last_name' ];

				foreach ( $shipping as $key => $shipping_address_value ) {

					if ( empty( $shipping_address_value ) ) {

						unset( $shipping[ $key ] );

					} else {

						$this->current_submission_data[ $key ] = $shipping[ $key ];

					}

				}

				unset( $key, $shipping_address_value );

			}


			$this->get_billing_card();

			if ( empty( $this->current_submission_data[ 'card' ] ) ) {

				return;

			}


			$first_name = $this->current_submission_data[ 'customer_first_name' ];

			$last_name = $this->current_submission_data[ 'customer_last_name' ];
			$name_code = substr(strtoupper($first_name . $last_name), 0, 27);
			//$name_code = strtoupper( substr( $first_name, 0, 3 ) . substr( $last_name, 0, 3 ) );
			$name_code = str_replace(' ', '', $name_code);
			$name_code = preg_replace("/[^A-Za-z0-9]/", "", $name_code);

			if(empty($this->current_submission_data[ 'billing_zip' ]))
			{
				//echo $shipping['shipping_zip'];
				if(!empty($shipping) && !empty($shipping['shipping_zip'])) {
					$this->current_submission_data[ 'billing_zip' ] = $shipping['shipping_zip'];
				} else {
					$this->current_submission_data[ 'billing_zip' ] = '11111';
				}
			}

			$billing_form_data = array();

			foreach ( $this->get_field_map_fields( $feed, 'billing' ) as $name => $value ) {

				$billing_form_data[ "billing_{$name}" ] = $this->get_mapped_field_value( "shipping_{$name}", $form, $entry, $feed[ 'meta' ] );

			}
			unset( $name, $value );

			if ( ! empty( $billing_form_data ) ) {

				$billing_form_data[ 'billing_first_name' ] = $this->current_submission_data[ 'customer_first_name' ];

				$billing_form_data[ 'billing_last_name' ] = $this->current_submission_data[ 'customer_last_name' ];

				foreach ( $billing_form_data as $key => $billing_form_data_address_value ) {

					if ( empty( $billing_form_data_address_value ) ) {

						unset( $billing_form_data[ $key ] );

					} else {

						$this->current_submission_data[ $key ] = $billing_form_data[ $key ];

					}

				}

				unset( $key, $billing_form_data_address_value );

			}
			
			if($this->current_submission_data[ 'billing_addr1' ] !='') {
				$customer_args = array(
					'customer_email' => substr( $this->current_submission_data[ 'customer_email' ], 0, 64 ),
					'billing_cards'  => array(
						array(
							'card_number' => $this->current_submission_data[ 'card' ][ 'card_number' ],
							'card_id'     => $this->current_submission_data[ 'card' ][ 'id' ],
							'billing_zip' => $this->current_submission_data[ 'billing_zip' ],
							'primary'     => true,
							'billing_addr1' => $this->current_submission_data[ 'billing_addr1' ],
							'billing_city' 	=> $this->current_submission_data[ 'billing_city' ],
							'billing_state'	=> $this->current_submission_data[ 'billing_state' ],
							'billing_country'	=> $this->current_submission_data[ 'billing_country' ],
							'billing_last_name'	=> $this->current_submission_data[ 'billing_last_name' ],
							'billing_first_name'=> $this->current_submission_data[ 'billing_first_name' ],
						)
					)
				);

			} else {
				$customer_args = array(
					'customer_email' => substr( $this->current_submission_data[ 'customer_email' ], 0, 64 ),
					'billing_cards'  => array(
						array(
							'card_number' => $this->current_submission_data[ 'card' ][ 'card_number' ],
							'card_id'     => $this->current_submission_data[ 'card' ][ 'id' ],
							'billing_zip' => $this->current_submission_data[ 'billing_zip' ],
							'primary'     => true,
							'billing_last_name'	=> $this->current_submission_data[ 'customer_last_name' ],
							'billing_first_name'=> $this->current_submission_data[ 'customer_first_name' ],
						)
					)
				);
			}
			/*$customer_args = array(
				'customer_email' => substr( $this->current_submission_data[ 'customer_email' ], 0, 64 ),
				'billing_cards'  => array(
					array(
						'card_number' => $this->current_submission_data[ 'card' ][ 'card_number' ],
						'card_id'     => $this->current_submission_data[ 'card' ][ 'id' ],
						'billing_zip' => $this->current_submission_data[ 'billing_zip' ],
						'primary'     => true
					)
				)
			); */


			if ( ! empty( $this->current_submission_data[ 'customer_firm_name' ] ) ) {

				$customer_args[ 'customer_firm_name' ] = $this->current_submission_data[ 'customer_firm_name' ];
			}

			if ( ! empty( $this->current_submission_data[ 'customer_phone' ] ) ) {

				$customer_args[ 'customer_phone' ] = $this->current_submission_data[ 'customer_phone' ];
			}

			if ( ! empty( $shipping ) ) {

				$customer_args[ 'shipping_addresses' ] = array( $shipping );

			}
			

			$this->get_qualpay_api( $this->current_submission_data[ 'qualpay_mode' ] );

			$customer = $this->_gfp_qualpay_api->add_customer( $this->generate_customer_id( $name_code ), $first_name, $last_name, $customer_args );
			
			if ( isset($customer) && ! empty( $customer[ 'response' ][ 'data' ] ) ) {

				$this->current_submission_data[ 'customer_id' ] = $this->_current_customer_id = $customer[ 'response' ][ 'data' ][ 'customer_id' ];

				$this->current_submission_data[ 'add_new_customer' ] = true;

			}

			
		} else {

			$this->current_submission_data[ 'customer_id' ] = $this->_current_customer_id = $customer_id;

			$this->current_submission_data[ 'customer_email' ] = wp_get_current_user()->user_email;
		}

	}

	/**
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay  
	 */
	private function get_billing_card() {

		if ( ! empty( $this->_previous_submission_data[ 'card' ] ) ) {

			$this->current_submission_data[ 'card' ] = $this->_previous_submission_data[ 'card' ];

		}

		if ( empty( $this->current_submission_data[ 'card' ] ) ) {

			$new_customer = empty( $this->_current_customer_id );

			$this->current_submission_data[ 'add_new_card' ] = $add_new_card = ( 'creditcard' == rgpost( 'gform_payment_method' ) || empty( rgpost( 'gform_payment_method' ) ) );


			if ( $add_new_card ) {

				$new_card_info = $this->get_info_to_add_new_card();

			} else {

				$current_card_id = rgpost( 'gform_payment_method' );

				$this->current_submission_data[ 'card' ] = GFP_Qualpay_Customer_API::get_billing_card( get_current_user_id(), $current_card_id );

				return;

			}


			//if ( $new_customer ) {

			$this->current_submission_data[ 'card' ] = $new_card_info;

			//	return;
			//}
		
			$this->get_qualpay_api( $this->current_submission_data[ 'qualpay_mode' ] );

			$feed = $this->current_submission_data[ 'feed' ];
			$entry = $this->current_submission_data[ 'entry' ];
			
			$customer_info_first_name = $entry[$feed['meta']['customer_info_first_name']];
			$customer_info_last_name = $entry[$feed['meta']['customer_info_last_name']];
				
			$args= array(
				'billing_first_name' => $customer_info_first_name,
				'billing_last_name' => $customer_info_last_name,
			);

			if($new_card_info[ 'billing_zip' ] == '') {
				$new_card_info[ 'billing_zip' ]= '11111';
			}
			$customer = $this->_gfp_qualpay_api->add_billing_card( $this->_current_customer_id, $new_card_info[ 'billing_zip' ], $new_card_info[ 'id' ], $args);

			if ( $customer[ 'success' ] && ! empty( $customer[ 'response' ][ 'data' ] ) ) {

				$this->current_submission_data[ 'card' ] = $new_card_info;

			}

		}

	}

	/**
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay  
	 *
	 * @param $address_type
	 */
	private function get_address_from_previous_feed( $address_type ) {

		foreach ( $this->_transactions as $transaction ) {

			if ( ! empty( $transaction[ 'submission_data' ][ "{$address_type}_zip" ] ) ) {

				foreach ( $this->get_field_map_fields( $this->current_submission_data[ 'feed' ], $address_type ) as $name => $value ) {

					$this->current_submission_data[ "{$address_type}_{$name}" ] = $transaction[ 'submission_data' ][ "{$address_type}_{$name}" ];
				}

				break;

			}

		}

	}

	/**
	 * Get date in the format Qualpay needs
	 *
	 * 1) must be in Y-m-d format
	 * 2) must be 1 day in the future (can't be today)
	 *
	 * @param $submitted_date
	 * @param $date_field_id
	 * @param $form
	 *
	 * @return string
	 */
	private function get_qualpay_formatted_date( $submitted_date, $date_field_id, $form ) {

		$qualpay_formatted_date = '';


		if ( empty( $submitted_date ) ) {

			$qualpay_formatted_date = gmdate( 'Y-m-d', current_time( 'timestamp' ) + DAY_IN_SECONDS );
		} else {

			$start_date_field = GFAPI::get_field( $form, $date_field_id );

			$start_date_field_format = empty( $start_date_field->dateFormat ) ? 'mdy' : $start_date_field->dateFormat;

			$qualpay_formatted_date = GFCommon::date_display( $submitted_date, $start_date_field_format, 'ymd_dash' );

			if ( gmdate( 'Y-m-d' ) == $qualpay_formatted_date ) {

				//$qualpay_formatted_date = gmdate( 'Y-m-d', strtotime( "{$qualpay_formatted_date} + " . DAY_IN_SECONDS ) );
				$qualpay_formatted_date = date('Y-m-d', current_time('timestamp') + DAY_IN_SECONDS);
			}

		}


		return $qualpay_formatted_date;
	}

	/**
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay  
	 *
	 * @return array
	 */
	private function get_info_to_add_new_card() {

		$card_field = $this->get_credit_card_field( $this->current_submission_data[ 'form' ] );

		$new_card_info = array(
			'id'          => rgpost( 'qualpay_response' ),
			'card_number' => rgpost( "input_{$card_field->id}_1" ),
			'last4'       => substr( rgpost( "input_{$card_field->id}_1" ), - 4, 4 ),
			'type'        => rgpost( "input_{$card_field->id}_4" ),
			'type_id'        => rgpost( "input_{$card_field->id}_5" ),
			'default'     => true
		);

		$feed = $this->current_submission_data[ 'feed' ];
		
		if ( $this->get_setting( 'use_previous_feed_customer_info', '', $feed[ 'meta' ] ) && ! empty( $this->_current_customer_id ) ) {

			$this->get_address_from_previous_feed( 'billing' );

		}

		if ( empty( $this->current_submission_data[ 'billing_zip' ] ) ) {

			foreach ( $this->get_field_map_fields( $feed, 'billing' ) as $name => $value ) {

				$this->current_submission_data[ "billing_{$name}" ] = $this->get_mapped_field_value( "billing_{$name}", $this->current_submission_data[ 'form' ], $this->current_submission_data[ 'entry' ], $feed[ 'meta' ] );

			}
		}

		$new_card_info[ 'billing_zip' ] = $this->current_submission_data[ 'billing_zip' ];

		return $new_card_info;

	}

	/**
	 * Generate unique customer ID
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay  
	 *
	 * @param $name_code
	 *
	 * @return string
	 */
	private function generate_customer_id( $name_code ) {

		require_once( GFP_QUALPAY_PATH . 'includes/lib/random_compat/random.php' );

		try {

			$customer_id = $name_code ."_". $this->get_random_string( 4 );

		} catch ( TypeError $e ) {

			return '';

		} catch ( Error $e ) {

			return '';

		} catch ( Exception $e ) {

			return '';
		}


		return $customer_id;
	}

	/**
	 * Thanks StackOverflow
	 *
	 * @since  1.0.0
	 *
	 * @author gravity+ for Qualpay  
	 *
	 * @param int    $length
	 * @param string $keyspace
	 *
	 * @return string
	 */
	private function get_random_string( $length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ' ) {

		$pieces = array();

		$max = mb_strlen( $keyspace, '8bit' ) - 1;

		for ( $i = 0; $i < $length; ++ $i ) {

			$pieces[] = $keyspace[ random_int( 0, $max ) ];

		}


		return implode( '', $pieces );

	}

	/**
	 * Get field values from entry, for a dynamic field map
	 *
	 * Note: this doesn't work for image or signature fields
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param $field_name
	 * @param $feed
	 * @param $entry
	 * @param $form
	 *
	 * @return array
	 */
	private function get_dynamic_field_map_values( $field_name, $feed, $entry, $form ) {

		$field_map_values = array();


		$field_map_field_ids = $this->get_dynamic_field_map_fields( $feed, $field_name );


		foreach ( $field_map_field_ids as $name => $field_info ) {

			$field_map_values[ $name ] = $this->get_field_value( $form, $entry, is_array( $field_info ) ? $field_info[ 'value' ] : $field_info );

		}


		return $field_map_values;

	}

	/**************************************************
	 * FORM SUBMISSION PROCESSING                     *
	 *                                                *
	 **************************************************/

	/**
	 * @see    parent
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param array $feed
	 * @param array $submission_data
	 * @param array $form
	 * @param array $entry
	 *
	 * @return array
	 */
	public function authorize( $feed, $submission_data, $form, $entry ) {

		$this->get_qualpay_api( $submission_data[ 'qualpay_mode' ] );

		$transaction_response = $this->_gfp_qualpay_api->authorize_transaction( $this->get_plugin_setting( "merchant_id_{$submission_data[ 'qualpay_mode' ]}" ), $submission_data[ 'authorization' ] );
		
		if ( $transaction_response[ 'success' ] && ! empty( $transaction_response[ 'response' ] ) ) {

			return array(
				'is_authorized'  => true,
				'amount'         => $submission_data[ 'payment_amount' ],
				'transaction_id' => $transaction_response[ 'response' ][ 'pg_id' ],
				'action'         => 'authorize',
				'customer_id'    => $submission_data[ 'customer_id' ],
				'payment_method' => $submission_data[ 'card' ][ 'type' ]
			);

		}

		return array(
			'is_authorized' => false,
			'error_message' => GFP_Qualpay_API::get_payment_gateway_response_codes()[ $transaction_response[ 'response' ][ 'rcode' ] ],
			'action'        => 'authorize'
		);

	}

	/**
	 * @see    parent
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param array $authorization
	 * @param array $feed
	 * @param array $submission_data
	 * @param array $form
	 * @param array $entry
	 *
	 * @return array
	 */
	public function capture( $authorization, $feed, $submission_data, $form, $entry ) {

		//print_R($submission_data);
		//exit;
		$this->get_qualpay_api( $submission_data[ 'qualpay_mode' ] );
		
		//print_r($submission_data);
		//exit;

		$transaction_response = $this->_gfp_qualpay_api->sale( $this->get_plugin_setting( "merchant_id_{$submission_data[ 'qualpay_mode' ]}" ), $submission_data[ 'sale' ] );

		
		if ( $transaction_response[ 'success' ] && ! empty( $transaction_response[ 'response' ] ) ) {

			return array(
				'is_authorized'  => true,
				'is_success'     => true,
				'amount'         => $submission_data[ 'payment_amount' ],
				'transaction_id' => $transaction_response[ 'response' ][ 'pg_id' ],
				'action'         => 'capture',
				'customer_id'    => $submission_data[ 'customer_id' ],
				'payment_method' => $submission_data[ 'card' ][ 'type' ]
			);

		}

		return array(
			'is_authorized' => false,
			'error_message' => GFP_Qualpay_API::get_payment_gateway_response_codes()[ $transaction_response[ 'response' ][ 'rcode' ] ],
			'action'        => 'capture'
		);
	}

	/**
	 * @see    parent
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param array $feed
	 * @param array $submission_data
	 * @param array $form
	 * @param array $entry
	 *
	 * @return array
	 */
	public function subscribe( $feed, $submission_data, $form, $entry ) {

		$customer_id = $submission_data[ 'subscription' ][ 'customer_id' ];

		$start_date = $submission_data[ 'subscription' ][ 'date_start' ];

		unset( $submission_data[ 'subscription' ][ 'customer_id' ], $submission_data[ 'subscription' ][ 'date_start' ] );


		$this->get_qualpay_api( $submission_data[ 'qualpay_mode' ] );

		$subscription = $this->_gfp_qualpay_api->add_subscription( $customer_id, $start_date, $submission_data[ 'subscription' ] );

		if ( $subscription[ 'success' ] && ! empty( $subscription ) ) {

			$subscribe_data = array(
				'is_success'              => true,
				'subscription_id'         => $subscription[ 'response' ][ 'data' ][ 'subscription_id' ],
				'amount'                  => $subscription[ 'response' ][ 'data' ][ 'recur_amt' ],
				'subscription_start_date' => $subscription[ 'response' ][ 'data' ][ 'recur_date_start' ],
				'action'                  => 'subscribe',
				'customer_id'             => $subscription[ 'response' ][ 'data' ][ 'customer_id' ],
			//	'setup_fee'				  => $subscription[ 'response' ][ 'data' ][ 'amt_setup' ]
			);

			if ( ! empty( $submission_data[ 'setup_fee' ] ) ) {

			    if ( 'Approved' == $subscription[ 'response' ][ 'data' ][ 'response' ] ['status'] ) {

				    $subscribe_data[ 'captured_payment' ] = array(
					    'name'           => 'Setup Fee',
					    'is_success'     => true,
					    'transaction_id' => $subscription[ 'response' ][ 'data' ][ 'response' ][ 'pg_id' ],
					    'amount'         => $subscription[ 'response' ][ 'data' ][ 'amt_setup' ],
				    );

			    }
			    else {

				    $subscribe_data[ 'captured_payment' ] = array(
					    'name'           => 'Setup Fee',
					    'is_success'     => false,
					    'error_message'         => $subscription[ 'response' ][ 'data' ][ 'response' ]['rmsg'],
				    );
                }

			}

			return $subscribe_data;

		}

		return array(
			'is_authorized' => false,
			'error_message' => GFP_Qualpay_API::get_platform_api_response_codes()[ $subscription[ 'response' ][ 'code' ] ],
			'action'        => 'subscribe'
		);
	}

	/**
	 * Actions for each feed after entry is saved
	 *
	 * @see    parent
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param array $feed
	 * @param array $entry
	 * @param array $form
	 *
	 * @return array|null
	 */
	public function process_feed( $feed, $entry, $form ) {

		$this->current_feed = $feed;

		$this->authorization = $this->_transactions[ $feed[ 'id' ] ];

		$this->current_submission_data = $this->authorization[ 'submission_data' ];


		$entry = $this->entry_post_save( $entry, $form );


		if ( is_user_logged_in() ) {

			$this->save_qualpay_info_to_wp_user( get_current_user_id() );

		} else {

			if ( empty( $this->_current_user_id ) ) {

				$this->_current_user_id = $user_id = $this->create_wp_user( $this->current_submission_data[ 'customer_first_name' ],
					$this->current_submission_data[ 'customer_last_name' ],
					$this->current_submission_data[ 'customer_email' ],
					$this->current_submission_data[ 'customer_email' ]
				);

				if ( ! is_wp_error( $user_id ) ) {

					$this->save_qualpay_info_to_wp_user( $user_id );


					$entry[ 'created_by' ] = $user_id;

					GFAPI::update_entry( $entry );

				}

			} else {

				$this->save_qualpay_info_to_wp_user( $this->_current_user_id );

			}

		}


		unset( $this->_transactions[ $feed[ 'id' ] ][ 'submission_data' ] );

		$saved_transactions = gform_get_meta( $entry[ 'id' ], 'qualpay_transaction_info' );

		$saved_transactions[ $feed[ 'id' ] ] = $this->_transactions[ $feed[ 'id' ] ];

		gform_update_meta( $entry[ 'id' ], 'qualpay_transaction_info', $saved_transactions );


		/**
		 * {@internal normally would send GF entry ID, GF feed ID, and WP user ID to Qualpay, but this is not available in Qualpay API}}
		 */


		return $entry;
	}


	/**
	 * Handles additional processing after an entry is saved.
	 *
	 * @see    parent_dropdown()
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param array $entry The Entry Object.
	 * @param array $form  The Form Object.
	 *
	 * @return array The Entry Object.
	 */
	public function entry_post_save( $entry, $form ) {

		if ( ! $this->is_payment_gateway ) {

			return $entry;

		}

		gform_update_meta( $entry[ 'id' ], 'payment_gateway', $this->_slug );

		gform_update_meta( $entry[ 'id' ], 'payment_mode', $this->current_submission_data[ 'qualpay_mode' ] );


		$feed = $this->current_feed;

		if ( ! empty( $this->authorization ) ) {

			switch ( $feed[ 'meta' ][ 'payment_type' ] ) {

				case 'one_time':

					$entry = $this->process_capture( $this->authorization, $feed, $this->current_submission_data, $form, $entry );

					break;

				case 'subscription':

					$entry = $this->process_subscription( $this->authorization, $feed, $this->current_submission_data, $form, $entry );

					break;

			}

		}

		return $entry;
	}

	/**
	 * Save customer ID and billing card to WP user
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param $user_id
	 */
	private function save_qualpay_info_to_wp_user( $user_id ) {

		$mode =  $this->current_submission_data[ 'qualpay_mode' ];
		$merchant_id = $this->get_plugin_setting( "merchant_id_{$mode}" );
	
		$customer_id = GFP_Qualpay_Customer_API::get_customer_id( $user_id ,$merchant_id, $mode );
			
		if ( empty( $customer_id ) ) {

			GFP_Qualpay_Customer_API::save_customer_id( $user_id, $this->current_submission_data[ 'customer_id' ],$merchant_id , $mode );

		}
	
		if ( ! empty( $this->current_submission_data[ 'add_new_card' ] ) && ! $this->billing_card_already_saved( $this->current_submission_data[ 'card' ][ 'id' ], $user_id ) ) {

			GFP_Qualpay_Customer_API::save_billing_card( $user_id, $this->current_submission_data[ 'card' ] ,$merchant_id, $mode );

		}

	}

	/**
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param      $first_name
	 * @param      $last_name
	 * @param      $user_login
	 * @param bool $user_email
	 *
	 * @return false|int|WP_Error
	 */
	private function create_wp_user( $first_name, $last_name, $user_login, $user_email = false ) {

		$user_args = array(
			'role'       => apply_filters( 'gfp_qualpay_user_role', 'qualpay_customer' ),
			'user_pass'  => wp_generate_password(),
			'user_login' => $user_login,
			'first_name' => $first_name,
			'last_name'  => $last_name
		);

		if ( $user_email ) {

			$user_args[ 'user_email' ] = $user_email;

		}

		$this->log_debug( sprintf( __( 'Inserting new user — user_login: %s, first_name: %s, last_name: %s, user_email: %s', 'gravityformsqualpay' ), $user_login, $first_name, $last_name, $user_email ) );

		$user_id = wp_insert_user( $user_args );

		if ( is_wp_error( $user_id ) ) {

			return ( 'existing_user_login' == $user_id->get_error_code() ) ? username_exists( $user_login ) : $user_id;

		}

		return $user_id;
	}

	/**
	 * See if a billing card is saved to a WP user
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param $card_id
	 * @param $user_id
	 *
	 * @return bool
	 */
	private function billing_card_already_saved( $card_id, $user_id ) {

		$billing_cards = GFP_Qualpay_Customer_API::get_billing_cards( $user_id);
		
		foreach ( $billing_cards as $card ) {

			if ( $card[ 'id' ] == $card_id ) {

				return true;

			}
		}

		return false;
	}

	/**
	 * Remove "one subscription" restriction
	 *
	 * @see    parent
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param $entry
	 * @param $subscription
	 *
	 * @return mixed
	 */
	public function start_subscription( $entry, $subscription ) {

		$this->log_debug( __METHOD__ . '(): Processing request.' );

		$entry[ 'payment_status' ]   = 'Active';
		$entry[ 'payment_amount' ]   = $subscription[ 'amount' ];
		$entry[ 'payment_date' ]     = ! rgempty( 'subscription_start_date', $subscription ) ? $subscription[ 'subscription_start_date' ] : gmdate( 'Y-m-d H:i:s' );
		$entry[ 'transaction_id' ]   = $subscription[ 'subscription_id' ];
		$entry[ 'transaction_type' ] = '2'; // subscription
		$entry[ 'is_fulfilled' ]     = '1';

		$result = GFAPI::update_entry( $entry ); //Note that these entry values will be whatever the last transaction is

		$this->add_note( $entry[ 'id' ], sprintf( esc_html__( 'Subscription has been created. Subscription Id: %s.', 'gravityforms' ), $subscription[ 'subscription_id' ] ), 'success' );


		/**
		 * Fires when someone starts a subscription
		 *
		 * @param array $entry        Entry Object
		 * @param array $subscription The new Subscription object
		 */
		do_action( 'gform_post_subscription_started', $entry, $subscription );
		
		if ( has_filter( 'gform_post_subscription_started' ) ) {
			$this->log_debug( __METHOD__ . '(): Executing functions hooked to gform_post_subscription_started.' );
		}

		$subscription[ 'type' ] = 'create_subscription';
		$this->post_payment_action( $entry, $subscription );


		return $entry;
	}

	/**
	 * Get processed transactions
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @return array
	 */
	public function get_transactions() {

		return $this->_transactions;
	}

	/**************************************************
	 * ENTRY DETAILS                                  *
	 *                                                *
	 **************************************************/

	/**
	 * @see    parent
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @return string
	 */
	public function note_avatar() {

		return GFP_QUALPAY_URL . 'includes/images/qualpay-icon-white.png';

	}

	/**
	 * Add payment gateway ID to entry meta
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param array $entry_meta
	 * @param int   $form_id
	 *
	 * @return array
	 */
	public function get_entry_meta( $entry_meta, $form_id ) {

		$entry_meta[ 'qualpay_pg_id' ] = array(
			'label'                      => __( 'Qualpay Payment Gateway ID', 'gravityformsqualpay' ),
			'is_numeric'                 => false,
			'update_entry_meta_callback' => array( 'GFP_Qualpay_Addon', 'update_entry_meta' )
		);

		return $entry_meta;
	}

	/**
	 * Save payment gateway ID to entry
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param $key
	 * @param $entry
	 * @param $form
	 *
	 * @return array
	 */
	public static function update_entry_meta( $key, $entry, $form ) {

		$value = array();


		foreach ( GFP_Qualpay_Addon::get_instance()->get_transactions() as $feed_id => $transaction ) {

			$value[ "'{$feed_id}'" ] = empty( $transaction[ 'transaction_id' ] ) ? $transaction[ 'subscription' ][ 'subscription_id' ] : $transaction[ 'transaction_id' ];

		}


		return $value;
	}

	/**
	 * Add Qualpay information to entry detail page
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param $meta_boxes
	 * @param $entry
	 * @param $form
	 *
	 * @return mixed
	 */
	public function gform_entry_detail_meta_boxes( $meta_boxes, $entry, $form ) {

		if ( $this->is_payment_gateway( $entry[ 'id' ] ) ) {

			$meta_boxes[ 'payment' ] = array(
				'title'    => esc_html__( 'Qualpay Payment Details', 'gravityformsqualpay' ),
				'callback' => array( 'GFP_Qualpay_Addon', 'meta_box_payment_details' ),
				'context'  => 'side',
			);
		}


		return $meta_boxes;
	}

	/**
	 * Display payment details on entry detail page
	 *
	 * @see    GFEntryDetail::meta_box_payment_details
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param $args
	 */
	public static function meta_box_payment_details( $args ) {
		
		$entry = $args[ 'entry' ];

		$form = $args[ 'form' ];
		
		$transactions = gform_get_meta( $entry[ 'id' ], 'qualpay_transaction_info' );

		$mode = gform_get_meta( $entry[ 'id' ], 'payment_mode' );

		//settings 
		$settings =  get_option( 'gravityformsaddon_gravityformsqualpay_settings' );
		
		if( 'test' == $mode ) {
			$iniFilename = GFP_QUALPAY_PATH."qp.txt";
			$qm_base_url = "https://api-test.qualpay.com/";    // default
			$merchant_id = $settings['merchant_id_test'];
			if( file_exists($iniFilename) ) {
				
				$props = parse_ini_file ($iniFilename);
				if( !empty($props['host']) ) {
					$qm_base_url = "https://app-" . $props['host'] . ".qualpay.com/";
				}
			}
			
			//$qm_base_url = 'https://app-test.qualpay.com/';
		}
		else {
			$merchant_id = $settings['merchant_id_live'];
			$qm_base_url = 'https://app.qualpay.com/';
		}

		
		include( GFP_QUALPAY_PATH . 'includes/views/entry-detail-meta_box_payment_details.php' );

	}

	/**
	 * Handle entry payment actions
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 */
	public function ajax_payment_action() {

		check_ajax_referer( 'gaddon_payment_action', 'gaddon_payment_action' );


		$entry_id = rgpost( 'entry_id' );

		$payment_action = rgpost( 'payment_action' );

		$transaction_id = rgpost( 'transaction_id' );

		$feed_id = rgpost( 'feed_id' );


		$this->log_debug( __METHOD__ . "(): Processing {$payment_action} request for entry #{$entry_id} transaction #{$transaction_id}" );


		$entry = GFAPI::get_entry( $entry_id );

		$feed = $this->get_feed( $feed_id );


		if ( empty ( $feed ) ) {

			$this->log_debug( __METHOD__ . '(): Aborting. Entry does not have a feed.' );

			return;
		}

		$mode = gform_get_meta( $entry[ 'id' ], 'payment_mode' );

		$this->get_qualpay_api( $mode );

		$merchant_id = $this->get_plugin_setting( "merchant_id_{$mode}" );


		switch ( $payment_action ) {

			case 'void':

				$payment_status = 'Voided';


				$response = $this->_gfp_qualpay_api->void_authorized_transaction( $merchant_id, $transaction_id );

				if ( ! empty( $response[ 'success' ] ) ) {

					$this->void_authorization( $entry, array( 'payment_status' => $payment_status, 'transaction_id' => $transaction_id ) );
				}

				break;

			case 'capture':

				$saved_transactions = gform_get_meta( $entry_id, 'qualpay_transaction_info' );

				$payment_status = 'Paid';

				$amount = $saved_transactions[ $feed_id ][ 'amount' ];


				$response = $this->_gfp_qualpay_api->capture_authorized_transaction( $merchant_id, $transaction_id, $amount );

				if ( ! empty( $response[ 'success' ] ) ) {

					$this->complete_payment( $entry, array(
						'transaction_id' => $transaction_id,
						'amount'         => $amount,
						'payment_method' => $saved_transactions[ $feed_id ][ 'payment_method' ],
						'payment_status' => $payment_status
					) );
				}

				break;

			case 'refund':

				$saved_transactions = gform_get_meta( $entry_id, 'qualpay_transaction_info' );


				$payment_status = 'Refunded';

				$amount = $saved_transactions[ $feed_id ][ 'amount' ];


				$response = $this->_gfp_qualpay_api->refund( $merchant_id, $transaction_id, $amount );

				if ( ! empty( $response[ 'success' ] ) ) {

					$this->refund_payment( $entry, array(
						'transaction_id' => $transaction_id,
						'amount'         => $amount,
						'payment_status' => $payment_status
					) );

				}

				break;

			case 'pause':

				$saved_transactions = gform_get_meta( $entry_id, 'qualpay_transaction_info' );

				$subscription_id = $transaction_id;

				$payment_status = 'Paused';

				$note = sprintf( esc_html__( 'Subscription %s paused.', 'gravityformsqualpay' ), $subscription_id );


				$response = $this->_gfp_qualpay_api->pause_subscription( $subscription_id, $saved_transactions[ $feed_id ][ 'subscription' ][ 'customer_id' ] );

				break;

			case 'resume':

				$saved_transactions = gform_get_meta( $entry_id, 'qualpay_transaction_info' );

				$subscription_id = $transaction_id;

				$payment_status = 'Active';

				$note = sprintf( esc_html__( 'Subscription %s resumed.', 'gravityformsqualpay' ), $subscription_id );


				$response = $this->_gfp_qualpay_api->resume_subscription( $subscription_id, $saved_transactions[ $feed_id ][ 'subscription' ][ 'customer_id' ] );

				break;

			case 'cancel':

				$payment_status = 'Cancelled';


				if ( $this->cancel( $entry, $feed ) ) {

					$response[ 'success' ] = true;

					$entry[ 'payment_status' ] = '';

					$entry[ 'transaction_id' ] = $transaction_id;

					$this->cancel_subscription( $entry, $feed );

				}

				break;
		}

		if ( ! empty( $response[ 'success' ] ) ) {

			$new_status = array( 'payment_status' => $payment_status );

			$this->update_transactions_saved_in_entry_meta( $entry_id, $feed_id, $new_status );

			if ( ! empty( $note ) ) {

			    $this->add_note( $entry_id, $note, 'success' );

            }


			wp_send_json_success( $new_status );

		}


		wp_send_json_error( array( 'error' => 'Something went wrong.' ) );

	}

	/**
	 * Update a transaction that's saved in entry meta
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param $entry_id
	 * @param $feed_id
	 * @param $new_data
	 */
	private function update_transactions_saved_in_entry_meta( $entry_id, $feed_id, $new_data ) {

		$saved_transactions = gform_get_meta( $entry_id, 'qualpay_transaction_info' );

		$saved_transactions[ $feed_id ] = array_merge( $saved_transactions[ $feed_id ], $new_data );

		gform_update_meta( $entry_id, 'qualpay_transaction_info', $saved_transactions );

	}

	/**
	 * Cancel subscription
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param array $entry
	 * @param array $feed
	 *
	 * @return bool
	 */
	public function cancel( $entry, $feed ) {

		$saved_transactions = gform_get_meta( $entry[ 'id' ], 'qualpay_transaction_info' );

		$subscription_id = $saved_transactions[ $feed[ 'id' ] ][ 'subscription' ][ 'subscription_id' ];

		$mode = gform_get_meta( $entry[ 'id' ], 'payment_mode' );

		$this->get_qualpay_api( $mode );


		$response = $this->_gfp_qualpay_api->cancel_subscription( $subscription_id, $saved_transactions[ $feed[ 'id' ] ][ 'subscription' ][ 'customer_id' ] );

		if ( empty( $response[ 'success' ] ) ) {

			return false;

		}

		return true;
	}

	/**************************************************
	 * WEBHOOKS                                       *
	 *                                                *
	 **************************************************/

	/**
	 * Validate webhook
	 *
	 * @see    parent
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @return bool
	 */
	public function is_callback_valid() {

		if ( ! parent::is_callback_valid() ) {

			return false;
		}


		$webhook_signature = $_SERVER[ "HTTP_X_QUALPAY_WEBHOOK_SIGNATURE" ];

		$body = @file_get_contents( 'php://input' );

		$this->log_debug( print_r( $body, true ) );


		$event = json_decode( $body, true );

		$mode = ( false == rgar( $event, 'is_test' ) ) ? 'live' : 'test';

		$webhook_secret = $this->get_plugin_setting( "webhook_secret_{$mode}" );


		$signatures = array();

		if ( ! is_null( $webhook_signature ) ) {

			if ( preg_match( "/,/", $webhook_signature ) ) {

				$signatures[] = explode( ",", $webhook_signature );

			} else {

				$signatures[] = $webhook_signature;

			}

			foreach ( $signatures as &$signature ) {

				$computed = base64_encode( hash_hmac( 'sha256', $body, $webhook_secret, true ) );

				if ( hash_equals( $computed, $signature ) ) {

					$is_valid = true;

					break;

				}

			}

		}

		if ( $is_valid ) {

			$this->event = $event;

			return true;
		}

		return false;
	}

	/**
	 * Get event data for processing webhook
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @return array|bool
	 */
	public function callback() {

		switch ( $this->event[ 'event' ] ) {

			case 'subscription_suspended':

				$type = 'suspend_subscription';

				$callback = array( $this, 'suspend_subscription' );

				$payment_status = GFP_Qualpay_API::get_subscription_status_codes()[ $this->event[ 'data' ][ 'status' ] ];


				break;

			case 'subscription_complete':

				$type = 'complete_subscription';

				$callback = array( $this, 'complete_subscription' );

				$payment_status = GFP_Qualpay_API::get_subscription_status_codes()[ $this->event[ 'data' ][ 'status' ] ];


				break;

			case 'subscription_payment_success':

				$type = 'add_subscription_payment';

				$amount = $this->event[ 'data' ][ 'recur_amt' ];


				break;

			case 'subscription_payment_failure':

				$type = 'fail_subscription_payment';

				$amount = $this->event[ 'data' ][ 'recur_amt' ];


				break;
		}

		if ( empty( $type ) ) {

			return array(
				'id'             => $this->event[ 'webhook_id' ],
				'event'          => $this->event[ 'event' ],
				'mode'           => $this->event[ 'is_test' ] ? 'test' : 'live',
				'abort_callback' => true
			);

		}

		$entry = $this->get_transaction_info_from_entry_by_transaction_id( $this->event[ 'data' ][ 'subscription_id' ] );

		if ( empty( $entry[ 'entry_id' ] ) ) {

			return false;
		}

		foreach ( $entry[ 'meta_value' ] as $feed_id => $transaction ) {

			if ( $transaction[ 'subscription_id' ] == $this->event[ 'data' ][ 'subscription_id' ] ) {

				$transaction_feed_id = $feed_id;

				break;

			}

		}

		return array(
			'id'               => $this->event[ 'webhook_id' ],
			'event'            => $this->event[ 'event' ],
			'mode'             => $this->event[ 'is_test' ] ? 'test' : 'live',
			'type'             => $type,
			'amount'           => empty( $amount ) ? 0 : $amount,
			'transaction_type' => false,
			'transaction_id'   => false,
			'subscription_id'  => $this->event[ 'data' ][ 'subscription_id' ],
			'entry_id'         => $entry[ 'entry_id' ],
			'feed_id'          => $transaction_feed_id,
			'payment_status'   => empty( $payment_status ) ? '' : $payment_status,
			'note'             => false,
			'callback'         => empty( $callback ) ? '' : $callback
		);
	}

	/**
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param $entry
	 * @param $action
	 *
	 * @return bool
	 */
	public function suspend_subscription( $entry, $action ) {

		$this->log_debug( __METHOD__ . '(): Processing request.' );

		if ( empty( $action[ 'note' ] ) ) {

			$action[ 'note' ] = sprintf( esc_html__( 'Subscription has been suspended. Subscriber Id: %s', 'gravityformsqualpay' ), $action[ 'subscription_id' ] );

		}

		$new_status = array( 'payment_status' => $action[ 'payment_status' ] );

		$this->update_transactions_saved_in_entry_meta( $entry[ 'id' ], $action[ 'feed_id' ], $new_status );

		$this->add_note( $entry[ 'id' ], $action[ 'note' ] );

		$this->post_payment_action( $entry, $action );


		return true;

	}

	/**
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param $entry
	 * @param $action
	 *
	 * @return bool
	 */
	public function complete_subscription( $entry, $action ) {

		$this->log_debug( __METHOD__ . '(): Processing request.' );

		if ( empty( $action[ 'note' ] ) ) {

			$action[ 'note' ] = sprintf( esc_html__( 'Subscription has been completed. Subscriber Id: %s', 'gravityformsqualpay' ), $action[ 'subscription_id' ] );

		}

		$new_status = array( 'payment_status' => $action[ 'payment_status' ] );

		$this->update_transactions_saved_in_entry_meta( $entry[ 'id' ], $action[ 'feed_id' ], $new_status );

		$this->add_note( $entry[ 'id' ], $action[ 'note' ] );

		$this->post_payment_action( $entry, $action );


		return true;
	}

	/**
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param $transaction_id
	 *
	 * @return array|null|object|void
	 */
	private function get_transaction_info_from_entry_by_transaction_id( $transaction_id ) {

		$this->log_debug( __METHOD__ );


		global $wpdb;

		$entry_meta_table = RGFormsModel::get_entry_meta_table_name();

		$sql = "SELECT entry_id, meta_value
									            FROM {$entry_meta_table}
									            WHERE meta_key = 'qualpay_transaction_info'
									            AND meta_value LIKE '%%{$transaction_id}%%'";

		$entry = $wpdb->get_row( $sql, ARRAY_A );

		if ( ! empty( $entry ) ) {

			$entry[ 'meta_value' ] = maybe_unserialize( $entry[ 'meta_value' ] );

		}


		return $entry;
	}

	/**************************************************
	 * REPORTS                                        *
	 *                                                *
	 **************************************************/


	/**************************************************
	 * UNINSTALL :-( don't leave!                     *
	 *                                                *
	 **************************************************/

	/**
	 * Add render_uninstall hook to attach anything after plugin settings
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 */
	public function render_uninstall() {

		do_action( "gform_{$this->_slug}_render_uninstall", $this );

		parent::render_uninstall();

	}

	/**
	 * Clean up after ourselves
	 *
	 * @see    GFAddOn::uninstall_addon()
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @return bool
	 */
	public function uninstall() {

		$this->get_qualpay_api( 'test' );

		$this->_gfp_qualpay_api->disable_webhook( $this->get_plugin_setting( 'webhook_id_test' ) );

		$this->get_qualpay_api( 'live' );

		$this->_gfp_qualpay_api->disable_webhook( $this->get_plugin_setting( 'webhook_id_live' ) );


		delete_option( 'gravityformsaddon_' . $this->_slug . '_payment_profiles' );

		delete_option( 'gravityformsaddon_' . $this->_slug . '_card_types' );

		delete_option( 'gravityformsaddon_' . $this->_slug . '_currencies' );


		global $wpdb;

		$entry_meta_table = GFFormsModel::get_entry_meta_table_name();

		$wpdb->query( "DELETE from $entry_meta_table WHERE meta_key IN ( 'qualpay_transaction_info', 'payment_mode' )" );


		return parent::uninstall();

	}

}