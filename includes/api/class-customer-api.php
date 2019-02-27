<?php
/* @package   GFP_Qualpay\GFP_Qualpay_Customer_API
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
 * GFP_Qualpay_Customer_API Class
 *
 * Add, retrieve, and update Qualpay customer information saved to a WP user
 *
 * @since 1.0.0
 *
 * @author    Naomi C. Bush for gravity+ for Qualpay <support@gravityplus.pro>
 *
 */
class GFP_Qualpay_Customer_API {

	/**************************************************
	 * CUSTOMER                                       *
	 *                                                *
	 **************************************************/

	/**
	 * Get Qualpay customer ID for user
	 *
	 * @since 1.0.0
	 *
	 * @param int  $user_id
	 *
	 * @return string Returns customer ID
	 */
	public static function get_customer_id( $user_id ,$merchant_id ,$mode ) {
			
		$get_customer_id = get_user_meta($user_id, '_gravityformsaddon_qualpay_customer_id');
       // print_r($get_customer_id);                    
			if($mode != 'test') {
				for($i=0;$i<count($get_customer_id);$i++) {
					if (strpos($get_customer_id[$i], 'production') !== false) {
						$mid = $merchant_id;
						if (strpos($get_customer_id[$i], $mid) !== false) {
							$mydata = unserialize($get_customer_id[$i]);
							$customer_id = $mydata[1];
						}
					}
				}
			} else {
				for($i=0;$i<count($get_customer_id);$i++) {
					//$iniFilename = QUALPAY_PATH."qp.txt";
					$env_name = "test";
					
					if (strpos($get_customer_id[$i], $env_name) !== false) {
						$mid = $merchant_id;
						if (strpos($get_customer_id[$i], $mid) !== false) {
							$mydata = unserialize($get_customer_id[$i]);
							$customer_id = $mydata[1];
						}
					
					}
				}
			}
		return $customer_id;	
		//return get_user_meta( $user_id, '_gravityformsaddon_qualpay_customer_id', true );

	}

	/**
	 * Save Qualpay customer ID to WordPress user
	 *
	 * @since 1.0.0
	 *
	 * @param int  $user_id
	 *
	 * @return string Returns customer ID
	 */
	public static function save_customer_id( $user_id, $customer_id ,$merchant_id ,$mode) {
		
	
		$mode_name = $mode;
		$mode = strlen($mode_name);
		
		$cid_name = $customer_id;
		$cid = strlen($cid_name);

		$mid = $merchant_id;
		$mid_length =strlen($mid);
		$data_customer = 'a:3:{i:0;s:'.$mode.':"'.$mode_name.'";i:1;s:'.$cid.':"'.$cid_name.'";i:2;s:'.$mid_length.':"'.$mid.'";}';
		
		return add_user_meta( $user_id, '_gravityformsaddon_qualpay_customer_id', $data_customer );

	}

	/**************************************************
	 * BILLING CARDS                                  *
	 *                                                *
	 **************************************************/

	/**
	 * Save billing card to WordPress user
	 *
	 * @since 1.0.0
	 *
	 * @author Naomi C. Bush for gravity+ for Qualpay <support@gravityplus.pro>
	 *
	 * @param $user_id
	 * @param $card_details
	 */
	public static function save_billing_card( $user_id, $card_details ,$merchant_id ,$mode ) {

		
		add_user_meta( $user_id, '_gravityformsaddon_qualpay_billing_card', array(
		'id'        => $card_details[ 'id' ],
		'last4'     => $card_details[ 'last4' ],
		'type'      => $card_details[ 'type' ],
		'mode'		=> $mode,
		'merchant_id'=> $merchant_id
		) );


		if ( $card_details['default'] ) {

			update_user_meta( $user_id, '_gravityformsaddon_qualpay_billing_card_default', $card_details[ 'id' ] );

		}

	}

	/**
	 * Get WordPress user's saved billing cards
	 *
	 * @since 1.0.0
	 *
	 * @author Naomi C. Bush for gravity+ for Qualpay <support@gravityplus.pro>
	 *
	 * @param $user_id
	 *
	 * @return mixed
	 */
	public static function get_billing_cards( $user_id ) {

		return get_user_meta( $user_id, '_gravityformsaddon_qualpay_billing_card', false );

	}

	/**
	 * Get WordPress user's default billing card
	 *
	 * @since 1.0.0
	 *
	 * @author Naomi C. Bush for gravity+ for Qualpay <support@gravityplus.pro>
	 *
	 * @param $user_id
	 *
	 * @return string
	 */
	public static function get_default_billing_card( $user_id ) {

		return get_user_meta( $user_id, '_gravityformsaddon_qualpay_billing_card_default', true );

	}

	/**
	 * Get saved billing card information
	 *
	 * @since 1.0.0
	 *
	 * @author Naomi C. Bush for gravity+ for Qualpay <support@gravityplus.pro>
	 *
	 * @param $user_id
	 * @param $card_id
	 *
	 * @return array
	 */
	public static function get_billing_card( $user_id, $card_id ) {

		$cards = get_user_meta( $user_id, '_gravityformsaddon_qualpay_billing_card', false );

		foreach ( $cards as $card ) {

			if ( $card['id'] == $card_id ) {

				return $card;

			}

		}

		return array();

	}


	/**************************************************
	 * SUBSCRIPTION                                   *
	 *                                                *
	 **************************************************/


}