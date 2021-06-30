<?php
/* @package   GFP_Qualpay\GFP_Qualpay_API
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
 * Class GFP_Qualpay_API
 *
 * Handles Qualpay API calls, using the WordPress HTTP API
 *
 * @since  1.0.0
 *
 * @author Jankee Patel from Qualpay 
 */
class GFP_Qualpay_API {

	/**
	 * API library version
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @var string
	 */
	private $version = '1.0.0';

	/**
	 * ID sent in all API requests
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @var string
	 */
	private $user_agent = '';

	/**
	 * Developer ID sent in all API requests
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @var string
	 */
	private $developer_id = '';

	/**
	 * Qualpay endpoint for sandbox API requests
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @var string
	 */
	//private $test_endpoint = 'https://api-test.qualpay.com';
	

	/**
	 * Qualpay endpoint for live API requests
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @var string
	 */
	private $live_endpoint = 'https://api.qualpay.com';

	/**
	 * PSR-3 compliant logger
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 */
	protected $logger = null;

	/**
	 * Qualpay API security key
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @var string
	 */
	private $api_key = '';

	/**
	 * Environment to use for API requests
	 *
	 * Either test or live
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @var string
	 */
	private $mode = 'test';


	/**************************************************
	 * INITIALIZATION                                 *
	 *                                                *
	 **************************************************/

	/**
	 * Setup initial settings
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param string $mode
	 * @param string $api_key
	 * @param string $user_agent
	 * @param object $logger
	 */
	public function __construct( $mode, $api_key, $user_agent, $developer_id, $logger ) {

	
		if ( ! empty( $mode ) ) {

			 $this->mode = $mode;

		}
		
		$this->api_key = $api_key;

		if ( ! empty( $logger ) ) {

			$this->logger = $logger;

		}

		if ( ! empty( $user_agent ) ) {

			$this->user_agent = $user_agent;

		}

		if ( ! empty( $developer_id ) ) {

			$this->developer_id = $developer_id;

		}
		
		//environment change from here for QA and sandbox..

		$iniFilename = GFP_QUALPAY_PATH."qp.txt";
		$testUrl = "https://app-test.qualpay.com";    // default

		if( file_exists($iniFilename) ) {
			
			$props = parse_ini_file ($iniFilename);
			if( !empty($props['host']) ) {
				$testUrl = "https://app-" . $props['host'] . ".qualpay.com";
			}
		}
		$this->test_endpoint = $testUrl;

	}


	/**************************************************
	 * VALIDATION                                     *
	 *                                                *
	 **************************************************/

	/**
	 * Validate merchant ID
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param string $merchant_id
	 *
	 * @return bool
	 */
	public function validate_merchant_id( $merchant_id ) {

		$this->logger->log->debug( "Validating merchant ID..." );

		$is_valid = false;


		$merchant_settings = $this->get_merchant_settings( $merchant_id );

		if ( $merchant_settings[ 'success' ] && ! empty( $merchant_settings[ 'response' ][ 'data' ] ) ) {

			$is_valid = true;

		}

		return $is_valid;
	}

	/**
	 * Validate API key
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param $api_key
	 *
	 * @return bool
	 */
	public function validate_api_key( $api_key ) {

		$this->logger->log->debug( "Validating API key..." );

		$is_valid = false;

		$this->set_api_key( $api_key );

		$webhooks = $this->browse_webhooks( array( 'count' => 1 ) );

		if ( $webhooks[ 'success' ] ) {

			$is_valid = true;

		}


		return $is_valid;
	}

	/**************************************************
	 * INTEGRATOR                                     *
	 *                                                *
	 **************************************************/

	/**
	 * Get merchant settings
	 *
	 * @link   https://www.qualpay.com/developer/api/integrator/merchant-settings
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param string $mid Merchant ID
	 *
	 * @return array
	 */
	public function get_merchant_settings( $mid ) {

		return $this->send_request( "{$this->{$this->mode . '_endpoint'}}/platform/vendor/settings/{$mid}", 'GET', array() );

	}

	public function get_merchant_settings_api_key( $mid , $mode ,$api_key) {
		$this->set_api_mode($mode);
		$this->set_api_key($api_key);
		return $this->get_merchant_settings($mid);
	}


	/**************************************************
	 * PAYMENT GATEWAY                                *
	 *                                                *
	 **************************************************/

	/**
	 * Authorize transaction
	 *
	 * @link   https://www.qualpay.com/developer/api/payment-gateway/authorize-transaction
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param string $merchant_id Required. Qualpay unique ID
	 * @param array  $args        {
	 *                            Optional.
	 *
	 * @type float  amt_convenience_fee Amount of convenience fee. For display purposes as the amount of the fee must
	 *       be included in amt_tran
	 * @type float  amt_fbo             Total amount of transaction transferred to the FBO account.
	 * @type float  amt_tax             Amount of sales tax included in the total transaction amount
	 * @type float  amt_tran            Total amount of transaction including sales tax (if applicable)
	 * @type float  amt_tran_fee        Amount of transaction surcharge fee. For display purposes as the amount of the
	 *       fee must be included in amt_tran
	 * @type string auth_code           6-character authorization code that was received during a voice or ARU
	 *       authorization
	 * @type string avs_address         Street address of the cardholder
	 * @type string card_id             Card ID received from a tokenization request
	 * @type string card_number         Cardholder's card number. If this field is present in the request, the field
	 *       card_swipe must NOT be present, the field exp_date must USUALLY be present, and the fields card_id and
	 *       customer_id should NOT be present
	 * @type string card_swipe          either track 1 or track 2 data magnetic stripe data
	 * @type string cardholder_name
	 * @type string cavv_3ds            Base 64 encoded CAVV returned from the merchant’s third-party 3-D Secure
	 *       Merchant Plug-in
	 * @type array customer {
	 *        Optional. Will create the customer data in the vault when used with tokenize, either card_number or
	 *        card_swipe, and customer_id
	 *
	 * @type string  billing_addr1           address street
	 * @type string  billing_addr2           address, line 2
	 * @type string  billing_city            city
	 * @type string  billing_country         country
	 * @type string  billing_country_code    ISO numeric country code
	 * @type string  billing_state           state (abbreviated)
	 * @type string  billing_zip             zip code
	 * @type string  billing_zip4            zip+4 code
	 * @type string  customer_email          email address
	 * @type string  customer_firm_name      business name, if applicable
	 * @type string  customer_first_name     Required.
	 * @type string  customer_last_name      Required.
	 * @type string  customer_phone          phone number
	 * @type array   shipping_addresses {
	 *           Optional. List of shipping addresses for the customer.
	 *
	 * @type string   shipping_first_name     Required. Can contain up to 32 characters
	 * @type string   shipping_last_name      Required. Can contain up to 32 characters
	 * @type string   shipping_firm_name      business name, if applicable
	 * @type string   shipping_addr1          Address line Item 1
	 * @type string   shipping_addr2          Address line Item 2
	 * @type string   shipping_city           city
	 * @type string   shipping_state          state
	 * @type string   shipping_country        country
	 * @type string   shipping_country_code   ISO numeric country code
	 * @type string   shipping_zip            zip
	 * @type string   shipping_zip4           zip+4code if applicable
	 * @type bool     primary                 if this should be the default address. Default 'false'
	 *
	 *        }
	 *
	 *     }
	 * @type string customer_code       Reference code supplied by the cardholder to the merchant
	 * @type string customer_email      comma-separated list of e-mail addresses to which a receipt should be sent
	 * @type string customer_id         Customer ID value established by the merchant. may be used in place of a card
	 *       number in requests requiring cardholder account data
	 * @type string cvv2                CVV2 or CID value from the signature panel on the back of the cardholder's card
	 * @type string dba_name            DBA name used in the authorization and clearing messages, when merchant has
	 *       been authorized to send dynamic DBA information
	 * @type string developer_id        indicate which company developed the integration to the Qualpay Payment Gateway
	 * @type bool   email_receipt       whether to send an email transaction receipt to the address(es) provided in the
	 *       customer_email field
	 * @type string exp_date            Expiration date of cardholder card number. Required when the field card_number
	 *       is present. If card_swipe is present in the request, this field must NOT be present. When card_id or
	 *       customer_id is present in the request this field may also be present; if it is not, then the expiration
	 *       date from the Card Vault will be used
	 * @type int    fbo_id              For Benefit Of (FBO) account identifier on the Qualpay system
	 * @type string line_items          JSON array of JSON objects. Each object represents a single line item detail
	 *       element related to the transaction. Each detail element has required subfields: quantity (7N), description
	 *       (26AN), unit_of_measure (12AN), product_code (12AN), debit_credit_ind (1 AN), unit_cost (12,2N). Optional
	 *       subfields: type_of_supply (2AN), commodity code (12AN)
	 * @type string loc_id              specific location for this request
	 * @type string mc_ucaf_data        Base64 encoded MasterCard UCAF Field Data returned from the merchant’s
	 *       third-party 3D Secure Merchant Plug-in
	 * @type string mc_ucaf_ind         MasterCard UCAF Collection Indicator returned from the merchant’s third-party
	 *       3D Secure Merchant Plug-in
	 * @type string merch_ref_num       Merchant provided reference value stored with the transaction data & included
	 *       w/ transaction data in reports & lifecycle events like chargebacks
	 * @type string moto_ecomm_ind      type of MOTO transaction
	 * @type bool   partial_auth        allow for approval of a partial amount. amt_tran field in the response will
	 *       contain the amount that was approved. A second sale on a different card is required to capture the
	 *       remaining amount. Applicable only for 'auth' and 'sale' requests
	 * @type string pg_id               PG ID of previously authorized transaction. This field is required when sending
	 *       a capture, refund, or void request.
	 * @type string profile_id          identifies which Payment Gateway profile should be used for the request
	 * @type string purchase_id         Purchase Identifier (also referred to as the invoice number generated by the
	 *       merchant
	 * @type string report_data         JSON array of field data that will be included with the transaction data
	 *       reported in Qualpay Manager
	 * @type string session_id          internal
	 * @type int    subscription_id     Identifies the recurring subscription that applies to this transaction
	 * @type bool   tokenize            instruct the payment gateway to store the cardholder data in the Card Vault and
	 *       provide a card_id in the repsonse. If the card_number or card_id in the request is already in the Card
	 *       Vault, associated data will be updated (e.g. avs_address, avs_zip, exp_date) if present
	 * @type int    tran_currency       ISO numeric currency code for the transaction
	 * @type int    user_id             internal
	 * @type string xid_3ds             Base64 encoded transaction ID returned from the merchant’s third-party 3D
	 *       Secure Merchant Plug-in
	 *
	 * }
	 *
	 * @return array
	 */
	public function authorize_transaction( $merchant_id, $args ) {

		$this->logger->log->debug( __METHOD__ );

		$args[ 'merchant_id' ] = $merchant_id;


		return $this->send_request( "{$this->{$this->mode . '_endpoint'}}/pg/auth", 'POST', $args );

	}


	/**
	 * Send the request to the Qualpay API
	 *
	 * @param array $args
	 * @return array|mixed|object|WP_Error
	 */
	public function get_customer_billing_cards( $customer_id, $merchant_id ) {

		return $this->send_request( "{$this->{$this->mode . '_endpoint'}}/platform/vault/customer/{$customer_id}/billing?merchant_id={$merchant_id}", 'GET', array() );

	}

	/**
	 * Capture authorized transaction
	 *
	 * @link   https://www.qualpay.com/developer/api/payment-gateway/capture-authorized-transaction
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param string $merchant_id Required. Qualpay unique ID
	 * @param string $pg_id       Required. Qualpay unique ID
	 * @param float  $amount      Required. Total amount of transaction to capture (if different than original
	 *                            authorization amount)
	 * @param array  $args        {
	 *                            Optional.
	 *
	 * @type string developer_id
	 * @type string loc_id
	 * @type string profile_id
	 * @type string report_data
	 * @type string session_id
	 * @type int user_id
	 * @type int vendor_id
	 *
	 * }
	 *
	 * @return array
	 */
	public function capture_authorized_transaction( $merchant_id, $pg_id, $amount, $args = array() ) {

		$this->logger->log->debug( __METHOD__ );

		$required_args = array( 'merchant_id' => $merchant_id, 'amt_tran' => $amount );


		return $this->send_request( "{$this->{$this->mode . '_endpoint'}}/pg/capture/{$pg_id}", 'POST', array_merge( $args, $required_args ) );

	}

	/**
	 * Auth + Capture
	 *
	 * @link   https://www.qualpay.com/developer/api/payment-gateway/sale-auth-%2B-capture
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param string $merchant_id Required. Qualpay unique ID
	 * @param array  $args        See GFP_Qualpay_API::authorize_transaction for description
	 *
	 * @return array
	 */
	public function sale( $merchant_id, $args ) {

		$this->logger->log->debug( __METHOD__ );

		$required_args = array( 'merchant_id' => $merchant_id );

		return $this->send_request( "{$this->{$this->mode . '_endpoint'}}/pg/sale", 'POST', array_merge( $args, $required_args ) );

	}

	/**
	 * Refund already captured transaction
	 *
	 * @link   https://www.qualpay.com/developer/api/payment-gateway/refund-previously-captured-transaction
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param string $merchant_id Required. Qualpay unique ID
	 * @param string $pg_id       Required. Payment gateway ID
	 * @param float  $amount      Required. Total amount to refund
	 * @param array  $args        See GFP_Qualpay_API::authorize_transaction for description
	 *
	 * @return array
	 */
	public function refund( $merchant_id, $pg_id, $amount, $args = array() ) {

		$this->logger->log->debug( __METHOD__ );

		$required_args = array( 'merchant_id' => $merchant_id, 'amt_tran' => $amount );


		return $this->send_request( "{$this->{$this->mode . '_endpoint'}}/pg/refund/{$pg_id}", 'POST', array_merge( $args, $required_args ) );

	}

	/**
	 * Void authorized transaction
	 *
	 * @link   https://www.qualpay.com/developer/api/payment-gateway/void-previously-authorized-transaction
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param string $merchant_id Required. Qualpay unique ID
	 * @param string $pg_id       Required. Qualpay unique ID
	 * @param array  $args        See GFP_Qualpay_API::authorize_transaction for description
	 *
	 * @return array
	 */
	public function void_authorized_transaction( $merchant_id, $pg_id, $args = array() ) {

		$this->logger->log->debug( __METHOD__ );

		$required_args = array( 'merchant_id' => $merchant_id );


		return $this->send_request( "{$this->{$this->mode . '_endpoint'}}/pg/void/{$pg_id}", 'POST', array_merge( $args, $required_args ) );

	}


	/**************************************************
	 * EMBEDDED FIELDS                                *
	 *                                                *
	 **************************************************/

	/**
	 * Get transient key
	 *
	 * @link   https://www.qualpay.com/developer/api/embedded-fields/get-transient-key
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @return array
	 */
	public function get_transient_key() {

		$this->logger->log->debug( __METHOD__ );
	
		return $this->send_request( "{$this->{$this->mode . '_endpoint'}}/platform/embedded", 'GET', array() );

	}


	/**************************************************
	 * RECURRING BILLING                              *
	 *                                                *
	 **************************************************/

	/**
	 * Get all recurring plans
	 *
	 * @link   https://www.qualpay.com/developer/api/recurring-billing/get-all-recurring-plans
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param array $args {
	 *                    Optional.
	 *
	 * @type int    count       Number of records to return. 1-100. Default '10'
	 * @type string order_on    field on which the results will be sorted on. Default 'plan_code'
	 * @type string order_by    Sort order. Default 'asc'
	 * @type int    page        page when there are more results than the count parameter. Default '0'
	 * @type string filter      custom filter criteria. See https://www.qualpay.com/developer/api/reference#filters
	 *
	 * }
	 *
	 * @return array
	 */
	public function get_plans( $args ) {

		$this->logger->log->debug( __METHOD__ );

		$plans = $this->send_request( "{$this->{$this->mode . '_endpoint'}}/platform/plan?filter=status,IS,E", 'GET', $args );

		if ( $plans[ 'success' ] && 1 < $plans['response'][ 'totalPages' ] ) {

			$number_of_pages = $plans['response'][ 'totalPages' ];

			for ( $i = 1; $i <= $number_of_pages; $i ++ ) {

				$args[ 'page' ] = $i;

				$more_plans = $this->send_request( "{$this->{$this->mode . '_endpoint'}}/platform/plan", 'GET', $args );

				if ( $more_plans[ 'success' ] && ! empty( $more_plans[ 'response' ][ 'data' ] ) ) {

					$plans[ 'response' ][ 'data' ] = array_merge( $plans[ 'response' ][ 'data' ], $more_plans[ 'response' ][ 'data' ] );

				}

				unset( $more_plans );

			}

		}


		return $plans;

	}

	/**
	 * Add a subscription
	 *
	 * Create a new subscription on the start date. If the plan has one time set up fee, a payment gateway request is
	 * also made to bill the customer the one time fee. Note that the subscription remains active even if the one-time
	 * setup fee gateway request fails
	 *
	 * A one off subscription - i.e., a subscription without a plan can be created by not sending the plan_code and
	 * sending fields applicable to one-off plans
	 *
	 * @link   https://www.qualpay.com/developer/api/recurring-billing/add-a-subscription
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param string $customer_id Required. Qualpay unique ID
	 * @param string $date_start  Required. Qualpay unique ID
	 * @param array  $args        {
	 *                            Optional.
	 *
	 * @type string plan_code       Required only if subscription is on a plan. Plan code of the Recurring Plan
	 * @type string plan_desc       Applicable only to one-off subscriptions. A short description of the one off plan.
	 * @type int    plan_frequency  Applicable only to one-off subscriptions. Required for one-off subscriptions. This
	 *       field identifies the frequency of billing. Use one of the following codes for frequency. 0 - Weekly 1 -
	 *       Bi-Weekly 3 - Monthly 4 - Quarterly 5 - BiAnnually 6 - Annually
	 * @type int    interval        Applicable only to one-off subscriptions. Applicable only for monthly frequency.
	 *       Number of months in a subscription cycle.
	 * @type int    plan_duration   Applicable only to one-off subscriptions. Required for one-off subscriptions.
	 *       Number of billing cycles in the recurring transaction, Use -1 if billing cycles are indefinite
	 * @type float  amt_setup       Applicable only to one-off subscriptions. One-Time Fee amount.
	 * @type float  amt_tran        Applicable only to one-off subscriptions. Amount that will be billed each cycle
	 *       period
	 * @type string profile_id      Applicable only to one-off subscriptions. Payment Gateway Profile id that will be
	 *       used when billing transactions
	 * @type string tran_currency   Applicable only to one-off subscriptions. Numeric Currency Code. If Profile_id is
	 *       provded, the currency is determined from profile. Default is 840 - USD
	 *
	 * }
	 *
	 * @return array
	 */
	public function add_subscription( $customer_id, $date_start, $args ) {

		$this->logger->log->debug( __METHOD__ );

		$required_args = array(
			'customer_id' => $customer_id,
			'date_start'  => $date_start
		);


		return $this->send_request( "{$this->{$this->mode . '_endpoint'}}/platform/subscription", 'POST', array_merge( $args, $required_args ) );

	}

	/**
	 * Cancel an existing subscription
	 *
	 * @link   https://www.qualpay.com/developer/api/recurring-billing/cancel-a-subscription
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param string $id          Required. Subscription ID
	 * @param string $customer_id Required. Qualpay unique ID
	 * @param array  $args        Optional. See GFP_Qualpay_API::add_subscription for description
	 *
	 * @return array
	 */
	public function cancel_subscription( $id, $customer_id, $args = array() ) {

		$this->logger->log->debug( __METHOD__ );

		$required_args = array( 'customer_id' => $customer_id );


		return $this->send_request( "{$this->{$this->mode . '_endpoint'}}/platform/subscription/{$id}/cancel", 'POST', array_merge( $args, $required_args ) );

	}

	/**
	 * Pause an active subscription
	 *
	 * @link   https://www.qualpay.com/developer/api/recurring-billing/pause-a-subscription
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param string $id          Required. Subscription ID
	 * @param string $customer_id Required. Qualpay unique ID
	 * @param array  $args        Optional. See GFP_Qualpay_API::add_subscription for description
	 *
	 * @return array
	 */
	public function pause_subscription( $id, $customer_id, $args = array() ) {

		$this->logger->log->debug( __METHOD__ );

		$required_args = array( 'customer_id' => $customer_id );


		return $this->send_request( "{$this->{$this->mode . '_endpoint'}}/platform/subscription/{$id}/pause", 'POST', array_merge( $args, $required_args ) );

	}

	/**
	 * Pause an active subscription
	 *
	 * @link   https://www.qualpay.com/developer/api/recurring-billing/pause-a-subscription
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param string $id          Required. Subscription ID
	 * @param string $customer_id Required. Qualpay unique ID
	 * @param array  $args        Optional. See GFP_Qualpay_API::add_subscription for description
	 *
	 * @return array
	 */
	public function resume_subscription( $id, $customer_id, $args = array() ) {

		$this->logger->log->debug( __METHOD__ );

		$required_args = array( 'customer_id' => $customer_id );


		return $this->send_request( "{$this->{$this->mode . '_endpoint'}}/platform/subscription/{$id}/resume", 'POST', array_merge( $args, $required_args ) );

	}


	/**************************************************
	 * CUSTOMER VAULT                                 *
	 *                                                *
	 **************************************************/

	/**
	 * Add a customer
	 *
	 * @link   https://www.qualpay.com/developer/api/customer-vault/add-a-customer
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param string $id         Required. Unique ID to identify a customer. Used to add subscriptions or make
	 *                           payments. Cannot be updated. Up to 32 characters, case sensitive, only letters and
	 *                           numbers allowed
	 * @param string $first_name Required. Up to 32 characters
	 * @param string $last_name  Required. Up to 32 charcaters
	 * @param array  $args       {
	 *                           Optional.
	 *
	 * @type string customer_email              Up to 64 characters
	 * @type string customer_firm_name
	 * @type string customer_phone              Up to 16 characters
	 * @type string comments
	 * @type array  shipping_addresses See GFP_Qualpay_API::authorize_transaction for description
	 * @type array  billing_cards {
	 *        Optional.
	 *
	 * @type string  card_number             Required.
	 * @type string  exp_date
	 * @type string  cvv2
	 * @type string  card_id
	 * @type string  billing_addr1
	 * @type string  billing_city
	 * @type string  billing_country
	 * @type string  billing_country_code
	 * @type string  billing_state
	 * @type string  billing_zip             Required.
	 * @type string  billing_zip4
	 * @type string  customer_email
	 * @type string  billing_firm_name
	 * @type string  billing_first_name
	 * @type string  billing_last_name
	 * @type bool    verify
	 * @type bool    primary
	 *
	 * }
	 *
	 * @return array
	 */
	public function add_customer( $id, $first_name, $last_name, $args ) {

		$this->logger->log->debug( __METHOD__ );

		$required_args = array(
			'customer_id'         => $id,
			'customer_first_name' => $first_name,
			'customer_last_name'  => $last_name
		);


		return $this->send_request( "{$this->{$this->mode . '_endpoint'}}/platform/vault/customer", 'POST', array_merge( $args, $required_args ) );

	}

	/**
	 * Delete customer
	 *
	 * @link   https://www.qualpay.com/developer/api/customer-vault/delete-a-customer
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param string $id Required. Customer ID
	 *
	 * @return array
	 */
	public function delete_customer( $id ) {

		$this->logger->log->debug( __METHOD__ );

		return $this->send_request( "{$this->{$this->mode . '_endpoint'}}/platform/vault/customer/{$id}", 'DELETE', array() );

	}

	/**
	 * Add a billing card
	 *
	 * @link   https://www.qualpay.com/developer/api/customer-vault/add-a-billing-card
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param string $customer_id Required. Qualpay unique ID
	 * @param string $billing_zip Required. Qualpay unique ID
	 * @param string $card_number Required. payment Card Number - masked
	 * @param array  $args        {
	 *                            Optional.
	 *
	 * @type string  exp_date
	 * @type string  cvv2
	 * @type string  card_id
	 * @type string  billing_addr1
	 * @type string  billing_city
	 * @type string  billing_country
	 * @type string  billing_country_code
	 * @type string  billing_state
	 * @type string  billing_zip4
	 * @type string  billing_firm_name
	 * @type string  billing_first_name
	 * @type string  billing_last_name
	 * @type bool    verify
	 * @type bool    primary
	 *
	 * }
	 *
	 * @return array
	 */
	public function add_billing_card( $customer_id, $billing_zip, $card_id ,$args ) {

		$this->logger->log->debug( __METHOD__ );

		$required_args = array(
			'billing_zip' => $billing_zip,
			'card_id' => $card_id
		);

		return $this->send_request( "{$this->{$this->mode . '_endpoint'}}/platform/vault/customer/{$customer_id}/billing", 'POST',  array_merge($required_args,$args) );

	}

	/**
	 * Delete billing card
	 *
	 * @link   https://www.qualpay.com/developer/api/customer-vault/delete-a-billing-card
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param string $id            Required. Billing card ID
	 * @param string $customer_id   Required. Customer ID
	 *
	 * @return array
	 */
	public function delete_billing_card( $id, $customer_id, $args = array() ) {

		$this->logger->log->debug( __METHOD__ );

		$required_args = array(
			'card_id' => $id
		);

		return $this->send_request( "{$this->{$this->mode . '_endpoint'}}/platform/vault/customer/{$customer_id}/billing/delete", 'PUT', array_merge( $args, $required_args ) );

	}


	/**************************************************
	 * WEBHOOKS                                       *
	 *                                                *
	 **************************************************/

	/**
	 * Add webhook
	 *
	 * @link   https://www.qualpay.com/developer/api/webhooks/add-webhook
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param string $label            Required. Label to identify the webhook
	 * @param string $notification_url Required. Endpoint to which requests will be posted
	 * @param string $node             Node at which the webhook will be created
	 * @param array  $email_address    Array of email addresses that will be notified when a webhook is suspended
	 * @param array  $events           Array of events that will trigger the POST request
	 *
	 * @return array
	 */
	public function add_webhook( $label, $notification_url, $node = '', $email_address = array(), $events = array() ) {

		$this->logger->log->debug( __METHOD__ );

		$body = array(
			'label'            => $label,
			'notification_url' => $notification_url
		);

		if ( ! empty( $node ) ) {

			$body[ 'webhook_node' ] = $node;

		}

		if ( ! empty( $email_address ) ) {

			$body[ 'email_address' ] = $email_address;

		}

		if ( ! empty( $events ) ) {

			$body[ 'events' ] = $events;

		}


		return $this->send_request( "{$this->{$this->mode . '_endpoint'}}/platform/webhook", 'POST', $body );

	}

	/**
	 * Disable webhook
	 *
	 * @link   https://www.qualpay.com/developer/api/webhooks/disable-a-webhook
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param string $webhook_id Required.
	 *
	 * @return array
	 */
	public function disable_webhook( $webhook_id ) {

		$this->logger->log->debug( __METHOD__ );

		return $this->send_request( "{$this->{$this->mode . '_endpoint'}}/platform/webhook/{$webhook_id}/disable", 'PUT', array() );

	}

	/**
	 * Get a webhook
	 *
	 * @link   https://www.qualpay.com/developer/api/webhooks/get-webhook
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param string $webhook_id Required.
	 *
	 * @return array
	 */
	public function get_webhook( $webhook_id ) {

		$this->logger->log->debug( __METHOD__ );

		return $this->send_request( "{$this->{$this->mode . '_endpoint'}}/platform/webhook/{$webhook_id}", 'GET', array() );

	}

	/**
	 * Get webhooks
	 *
	 * @link   https://www.qualpay.com/developer/api/webhooks/browse-webhooks
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param array $args {
	 *                    Optional.
	 *
	 * @type int    count       Number of records to return. 1-100. Default '10'
	 * @type string order_on    field on which the results will be sorted on. Default 'webhook_id'
	 * @type string order_by    Sort order. Default 'asc'
	 * @type int    page        page when there are more results than the count parameter. Default '0'
	 * @type string filter      custom filter criteria. See https://www.qualpay.com/developer/api/reference#filters
	 *
	 * }
	 *
	 * @return array
	 */
	public function browse_webhooks( $args ) {

		$this->logger->log->debug( __METHOD__ );

		return $this->send_request( "{$this->{$this->mode . '_endpoint'}}/platform/webhook", 'GET', $args );

	}


	/**************************************************
	 * HELPERS                                        *
	 *                                                *
	 **************************************************/

	/**
	 * Set API key to be used for API requests
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param $api_key
	 */
	public function set_api_key( $api_key ) {

		$this->api_key = $api_key;

	}

	/**
	 * Set API environment to be used for API requests
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param $mode
	 */
	public function set_api_mode( $mode ) {

		$this->mode = $mode;

	}

	/**
	 * Send request to Qualpay API
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @param string $url
	 * @param string $method
	 * @param array  $body
	 *
	 * @return array
	 */
	public function send_request( $url, $method, $body ) {

		$this->logger->log->debug( __METHOD__ );


		$response = array();

		$success = false;


		if ( ! empty( $this->api_key ) ) {

			$method = strtoupper( $method );

			$this->logger->log->debug( "API URL: {$url}" );

			$this->logger->log->debug( 'Body:' . print_r( $body, true ) );

			$this->logger->log->debug( "Method: {$method}" );

			if (strpos($url, 'pg/') !== false){
				$body_append = array_merge( empty( $body ) ? array() :  $body, array( 'developer_id' => $this->developer_id ) );
			} else {
				$body_append = array_merge( empty( $body ) ? array() :  $body);
			} 
			
			$arguments = array(
				'timeout'   => 30,
				'sslverify' => false,
				'headers'   => array(
					'Authorization' => "Basic " . base64_encode( "{$this->api_key}:" ),
					'User-Agent'    => "{$this->user_agent}/{$this->version}",
					'Content-Type'  => 'application/json'
				),
				//'body'      => array_merge( empty( $body ) ? array() :  $body, array( 'developer_id' => $this->developer_id ) )
				'body'      => $body_append
			);

			switch ( $method ) {

				case 'GET':

					$raw_response = wp_remote_get( $url, $arguments );

					break;

				case 'POST':

					if ( ! empty( $arguments['body'] ) ) {

						$arguments['body'] = json_encode( $arguments['body'] );

					}

					$raw_response = wp_remote_post( $url, $arguments );

					break;

				default:

					if ( ! empty( $arguments['body'] ) ) {

						$arguments['body'] = json_encode( $arguments['body'] );

					}

					$raw_response = wp_remote_request( $url, array_merge( array( 'method' => $method ), $arguments ) );

					break;
			}

			$response = json_decode( wp_remote_retrieve_body( $raw_response ), true );

			if ( is_wp_error( $raw_response ) || ( 200 !== wp_remote_retrieve_response_code( $raw_response ) ) ) {

				$this->logger->log->error( 'Error: ' . print_r( $response, true ) );

			} else {

				if ( empty( $response ) ) {

					$response = $raw_response[ 'response' ][ 'message' ];

				}

				$this->logger->log->debug( "Success. " . print_r( $response, true ) );

				$success = true;

			}

		}

		return array( 'success' => $success, 'response' => $response );

	}

	/**
	 * Comes from ISO4217 list from ISO website
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @return string
	 */
	public static function get_currency_codes() {

		$currency_codes_json = '{
			"104": {
			"alpha": "MMK"
			},
			"108": {
						"alpha": "BIF"
			},
			"116": {
						"alpha": "KHR"
			},
			"124": {
						"alpha": "CAD"
			},
			"132": {
						"alpha": "CVE"
			},
			"136": {
						"alpha": "KYD"
			},
			"144": {
						"alpha": "LKR"
			},
			"152": {
						"alpha": "CLP"
			},
			"156": {
						"alpha": "CNY"
			},
			"170": {
						"alpha": "COP"
			},
			"174": {
						"alpha": "KMF"
			},
			"188": {
						"alpha": "CRC"
			},
			"191": {
						"alpha": "HRK"
			},
			"192": {
						"alpha": "CUP"
			},
			"203": {
						"alpha": "CZK"
			},
			"208": {
						"alpha": "DKK"
			},
			"214": {
						"alpha": "DOP"
			},
			"222": {
						"alpha": "SVC"
			},
			"230": {
						"alpha": "ETB"
			},
			"232": {
						"alpha": "ERN"
			},
			"238": {
						"alpha": "FKP"
			},
			"242": {
						"alpha": "FJD"
			},
			"262": {
						"alpha": "DJF"
			},
			"270": {
						"alpha": "GMD"
			},
			"292": {
						"alpha": "GIP"
			},
			"320": {
						"alpha": "GTQ"
			},
			"324": {
						"alpha": "GNF"
			},
			"328": {
						"alpha": "GYD"
			},
			"332": {
						"alpha": "HTG"
			},
			"340": {
						"alpha": "HNL"
			},
			"344": {
						"alpha": "HKD"
			},
			"348": {
						"alpha": "HUF"
			},
			"352": {
						"alpha": "ISK"
			},
			"356": {
						"alpha": "INR"
			},
			"360": {
						"alpha": "IDR"
			},
			"364": {
						"alpha": "IRR"
			},
			"368": {
						"alpha": "IQD"
			},
			"376": {
						"alpha": "ILS"
			},
			"388": {
						"alpha": "JMD"
			},
			"392": {
						"alpha": "JPY"
			},
			"398": {
						"alpha": "KZT"
			},
			"400": {
						"alpha": "JOD"
			},
			"404": {
						"alpha": "KES"
			},
			"408": {
						"alpha": "KPW"
			},
			"410": {
						"alpha": "KRW"
			},
			"414": {
						"alpha": "KWD"
			},
			"417": {
						"alpha": "KGS"
			},
			"418": {
						"alpha": "LAK"
			},
			"422": {
						"alpha": "LBP"
			},
			"426": {
						"alpha": "LSL"
			},
			"430": {
						"alpha": "LRD"
			},
			"434": {
						"alpha": "LYD"
			},
			"446": {
						"alpha": "MOP"
			},
			"454": {
						"alpha": "MWK"
			},
			"458": {
						"alpha": "MYR"
			},
			"462": {
						"alpha": "MVR"
			},
			"480": {
						"alpha": "MUR"
			},
			"484": {
						"alpha": "MXN"
			},
			"496": {
						"alpha": "MNT"
			},
			"498": {
						"alpha": "MDL"
			},
			"504": {
						"alpha": "MAD"
			},
			"512": {
						"alpha": "OMR"
			},
			"516": {
						"alpha": "NAD"
			},
			"524": {
						"alpha": "NPR"
			},
			"532": {
						"alpha": "ANG"
			},
			"533": {
						"alpha": "AWG"
			},
			"548": {
						"alpha": "VUV"
			},
			"554": {
						"alpha": "NZD"
			},
			"558": {
						"alpha": "NIO"
			},
			"566": {
						"alpha": "NGN"
			},
			"578": {
						"alpha": "NOK"
			},
			"586": {
						"alpha": "PKR"
			},
			"590": {
						"alpha": "PAB"
			},
			"598": {
						"alpha": "PGK"
			},
			"600": {
						"alpha": "PYG"
			},
			"604": {
						"alpha": "PEN"
			},
			"608": {
						"alpha": "PHP"
			},
			"634": {
						"alpha": "QAR"
			},
			"643": {
						"alpha": "RUB"
			},
			"646": {
						"alpha": "RWF"
			},
			"654": {
						"alpha": "SHP"
			},
			"682": {
						"alpha": "SAR"
			},
			"690": {
						"alpha": "SCR"
			},
			"694": {
						"alpha": "SLL"
			},
			"702": {
						"alpha": "SGD"
			},
			"704": {
						"alpha": "VND"
			},
			"706": {
						"alpha": "SOS"
			},
			"710": {
						"alpha": "ZAR"
			},
			"728": {
						"alpha": "SSP"
			},
			"748": {
						"alpha": "SZL"
			},
			"752": {
						"alpha": "SEK"
			},
			"756": {
						"alpha": "CHF"
			},
			"760": {
						"alpha": "SYP"
			},
			"764": {
						"alpha": "THB"
			},
			"776": {
						"alpha": "TOP"
			},
			"780": {
						"alpha": "TTD"
			},
			"784": {
						"alpha": "AED"
			},
			"788": {
						"alpha": "TND"
			},
			"800": {
						"alpha": "UGX"
			},
			"807": {
						"alpha": "MKD"
			},
			"818": {
						"alpha": "EGP"
			},
			"826": {
						"alpha": "GBP"
			},
			"834": {
						"alpha": "TZS"
			},
			"840": {
						"alpha": "USD"
			},
			"858": {
						"alpha": "UYU"
			},
			"860": {
						"alpha": "UZS"
			},
			"882": {
						"alpha": "WST"
			},
			"886": {
						"alpha": "YER"
			},
			"901": {
						"alpha": "TWD"
			},
			"929": {
						"alpha": "MRU"
			},
			"930": {
						"alpha": "STN"
			},
			"931": {
						"alpha": "CUC"
			},
			"932": {
						"alpha": "ZWL"
			},
			"933": {
						"alpha": "BYN"
			},
			"934": {
						"alpha": "TMT"
			},
			"936": {
						"alpha": "GHS"
			},
			"937": {
						"alpha": "VEF"
			},
			"938": {
						"alpha": "SDG"
			},
			"940": {
						"alpha": "UYI"
			},
			"941": {
						"alpha": "RSD"
			},
			"943": {
						"alpha": "MZN"
			},
			"944": {
						"alpha": "AZN"
			},
			"946": {
						"alpha": "RON"
			},
			"947": {
						"alpha": "CHE"
			},
			"948": {
						"alpha": "CHW"
			},
			"949": {
						"alpha": "TRY"
			},
			"950": {
						"alpha": "XAF"
			},
			"951": {
						"alpha": "XCD"
			},
			"952": {
						"alpha": "XOF"
			},
			"953": {
						"alpha": "XPF"
			},
			"955": {
						"alpha": "XBA"
			},
			"956": {
						"alpha": "XBB"
			},
			"957": {
						"alpha": "XBC"
			},
			"958": {
						"alpha": "XBD"
			},
			"959": {
						"alpha": "XAU"
			},
			"960": {
						"alpha": "XDR"
			},
			"961": {
						"alpha": "XAG"
			},
			"962": {
						"alpha": "XPT"
			},
			"963": {
						"alpha": "XTS"
			},
			"964": {
						"alpha": "XPD"
			},
			"965": {
						"alpha": "XUA"
			},
			"967": {
						"alpha": "ZMW"
			},
			"968": {
						"alpha": "SRD"
			},
			"969": {
						"alpha": "MGA"
			},
			"970": {
						"alpha": "COU"
			},
			"971": {
						"alpha": "AFN"
			},
			"972": {
						"alpha": "TJS"
			},
			"973": {
						"alpha": "AOA"
			},
			"975": {
						"alpha": "BGN"
			},
			"976": {
						"alpha": "CDF"
			},
			"977": {
						"alpha": "BAM"
			},
			"978": {
						"alpha": "EUR"
			},
			"979": {
						"alpha": "MXV"
			},
			"980": {
						"alpha": "UAH"
			},
			"981": {
						"alpha": "GEL"
			},
			"984": {
						"alpha": "BOV"
			},
			"985": {
						"alpha": "PLN"
			},
			"986": {
						"alpha": "BRL"
			},
			"990": {
						"alpha": "CLF"
			},
			"994": {
						"alpha": "XSU"
			},
			"997": {
						"alpha": "USN"
			},
			"999": {
						"alpha": "XXX"
			},
			"008": {
						"alpha": "ALL"
			},
			"012": {
						"alpha": "DZD"
			},
			"032": {
						"alpha": "ARS"
			},
			"051": {
						"alpha": "AMD"
			},
			"036": {
						"alpha": "AUD"
			},
			"044": {
						"alpha": "BSD"
			},
			"048": {
						"alpha": "BHD"
			},
			"050": {
						"alpha": "BDT"
			},
			"052": {
						"alpha": "BBD"
			},
			"084": {
						"alpha": "BZD"
			},
			"060": {
						"alpha": "BMD"
			},
			"064": {
						"alpha": "BTN"
			},
			"068": {
						"alpha": "BOB"
			},
			"072": {
						"alpha": "BWP"
			},
			"096": {
						"alpha": "BND"
			},
			"090": {
						"alpha": "SBD"
			}
			}';

		return $currency_codes_json;
	}

	/**
	 * Qualpay card types and names
	 *
	 * @link   https://www.qualpay.com/developer/api/reference#card-types
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @return array
	 */
	public static function get_card_types() {

		return array(
			'AM' => 'American Express',
			'DS' => 'Discover',
			'PP' => 'PayPal',
			'MC' => 'MasterCard',
			'VS' => 'Visa',
			'AP' => 'ACH'
		);

	}

	/**
	 * Qualpay payment gateway response codes
	 *
	 * @link   https://www.qualpay.com/developer/api/reference#payment-gateway-response-codes
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @return array
	 */
	public static function get_payment_gateway_response_codes() {

		return array(
			//'00' => 'Success',
			'000' => 'Success',
			'100' => 'Invalid message',
			'101' => 'Invalid credentials — The merchant_id and security_key provided do not match an account with Qualpay',
			'102' => 'Invalid payment gateway ID — The pg_id value could not be linked to a valid transaction',
			'103' => 'Missing cardholder data',
			'104' => 'Invalid transaction amount - The request was either missing the amt_tran or the value provided was invalid',
			'105' => 'Missing auth_code',
			'106' => 'Invalid AVS',
			'107' => 'Invalid expiration date',
			'108' => 'Invalid card number',
			'109' => 'Field length validation failed',
			'110' => 'Dynamic DBA not allowed',
			'111' => 'Credits not allowed',
			'112' => 'Invalid customer data - customer_id already exists or required customer fields are not included',
			'401' => 'Void failed — transaction already captured or voided',
			'402' => 'Refund failed - transaction has already been refunded, original transaction has not been captured, total amount of all refunds exceeds the original transaction amount, or original transaction was not a sale',
			'403' => 'Capture failed - amount exceeds the authorized amount, the transaction has already been captured, or authorization has been voided.',
			'404' => 'Batch close failed',
			'405' => 'Tokenization failed',
			'998' => 'Timeout',
			'999' => 'Internal error',
		);
	}

	/**
	 * Qualpay payment gateway response codes
	 *
	 * @link   https://www.qualpay.com/developer/api/reference#platform-api-response-codes
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @return array
	 */
	public static function get_platform_api_response_codes() {

		return array(
			'0' => 'Success',
			'2' => 'Request failed validation',
			'6' => 'API Key does not have access to this resource',
			'7' => 'Service doesn\'t exist',
			'11' => 'credentials provided were not recognized by the API, or operation was not allowed for this merchant',
			'99' => 'Qualpay server problem',
		);
	}

	/**
	 * Qualpay subscription status codes
	 *
	 * @since  1.0.0
	 *
	 * @author Jankee Patel from Qualpay 
	 *
	 * @return array
	 */
	public static function get_subscription_status_codes(){

		return array( 'A' => 'Active',
		              'D' => 'Complete',
		              'P' => 'Paused',
		              'C' => 'Canceled',
		              'S' => 'Suspended' );
	}

}