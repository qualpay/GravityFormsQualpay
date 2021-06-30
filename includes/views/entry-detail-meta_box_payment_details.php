<?php
/**
 * Entry Details page: Payment Details meta box
 *
 * Show multiple transactions for one entry
 *
 * @since  1.0.0
 *
 * @author Jankee Patel from Qualpay 
 */
if ( ! empty( $transactions ) ) {

	?>

    <div id="submitcomment" class="submitbox">
        <div id="minor-publishing">

			<?php $first_transaction = current( $transactions );

			if ( ! empty( $first_transaction[ 'customer_id' ] ) || ! empty( $first_transaction[ 'subscription' ][ 'customer_id' ] ) ) {

				$customer_id = ( empty( $first_transaction[ 'customer_id' ] ) ? $first_transaction[ 'subscription' ][ 'customer_id' ] : $first_transaction[ 'customer_id' ] );
				?>

                <div id="gf_qualpay_customer_id" class="gf_payment_detail">
					<?php esc_html_e( 'Customer', 'gravityforms' ) ?>:
                    <span id="gform_customer_id">
                        <a href="<?php echo $qm_base_url ?>merchant/customers/detail/<?php echo $customer_id ?>"
                           target="_blank" rel="noopener noreferrer"
                           title="Link to item in Qualpay Manager"><?php echo $customer_id ?></a>
                    </span>
                </div>

                <hr/>

				<?php

			}

			foreach ( $transactions as $feed_id => $transaction ) {

				$action = empty( $transaction[ 'subscription' ] ) ? $transaction[ 'action' ] : $transaction[ 'subscription' ][ 'action' ];

				switch ( $action ) {

					case 'authorize':
					case 'capture':

						$default_payment_status = ( 'authorize' == $transaction[ 'action' ] ) ? 'Authorized' : 'Paid';

						$payment_status = apply_filters( 'gform_payment_status', empty( $transaction[ 'payment_status' ] ) ? $default_payment_status : $transaction[ 'payment_status' ], $form, $entry, $transaction );

						$date_label = __( 'Date', 'gravityformsqualpay' );

						$payment_date = apply_filters( 'gform_payment_date', GFCommon::format_date( $entry[ 'payment_date' ], false, 'Y/m/d', false ), $form, $entry, $transaction );

						$transaction_id_label = 'PG ID';

						$transaction_id = apply_filters( 'gform_payment_transaction_id', $transaction[ 'transaction_id' ], $form, $entry, $transaction );

						//$qm_transaction_link = '#';
						
						//https://app-test.qualpay.com/merchant/971000010003/transaction/bypgid/e32a2e4dac7a11e8a80f0adff05dfb52
						$qm_transaction_link = "{$qm_base_url}merchant/{$merchant_id}/transaction/bypgid/{$transaction_id}";

						$payment_amount_label = 'Amount';

						$payment_amount = apply_filters( 'gform_payment_amount', $transaction[ 'amount' ], $form, $entry, $transaction );

						/*if ( 'Authorized' == $payment_status ) {

							$payment_actions = array(
								array( 'label' => 'Void', 'action' => 'void' ),
								array( 'label' => 'Capture', 'action' => 'capture' )
							);

						} elseif ( 'Paid' == $payment_status ) {

							$payment_actions = array(
								array( 'label' => 'Refund', 'action' => 'refund' )
							);

						} */


						break;

					case 'subscribe':

						$payment_status = apply_filters( 'gform_payment_status', empty( $transaction[ 'payment_status' ] ) ? 'Active' : $transaction[ 'payment_status' ], $form, $entry, $transaction );

						$date_label = __( 'Subscription Start Date', 'gravityformsqualpay' );

						$payment_date = $transaction[ 'subscription' ][ 'subscription_start_date' ];

						$transaction_id_label = 'Subscription ID';

						$transaction_id = apply_filters( 'gform_payment_transaction_id', $transaction[ 'subscription' ][ 'subscription_id' ], $form, $entry, $transaction );

						$qm_transaction_link = "{$qm_base_url}merchant/subscription/{$transaction_id}";

						$payment_amount_label = 'Recurring Amount';

						$payment_amount = apply_filters( 'gform_payment_amount', $transaction[ 'subscription' ][ 'amount' ], $form, $entry, $transaction );

						/*if ( 'Active' == $payment_status ) {

							$payment_actions = array(
								array( 'label' => 'Pause', 'action' => 'pause' ),
								array( 'label' => 'Cancel', 'action' => 'cancel' )
							);

						} elseif ( 'Paused' == $payment_status ) {

							$payment_actions = array(
								array( 'label' => 'Resume', 'action' => 'resume' ),
								array( 'label' => 'Cancel', 'action' => 'cancel' )
							);

						} */

						/**
						 * @todo do we want to show setup fee here
						 */

						break;
				}


				if ( ! empty( $payment_status ) ) {
					?>
                    <div id="transaction_<?php echo $transaction_id ?>">
                        <div id="gf_payment_status_<?php echo $transaction_id ?>" class="gf_payment_detail">
							<?php esc_html_e( 'Status', 'gravityforms' ) ?>:
                            <span id="gform_payment_status_<?php echo $transaction_id ?>"><?php echo $payment_status; ?></span>
                        </div>

						<?php

						if ( ! empty( $payment_date ) ) {
							?>
                            <div id="gf_payment_date_<?php echo $transaction_id ?>" class="gf_payment_detail">
								<?php echo $date_label ?>
                                :
                                <span id="gform_payment_date_<?php echo $transaction_id ?>"><?php echo $payment_date; ?></span>
                            </div>
							<?php
						}

						if ( ! empty( $transaction_id ) ) {
							?>
                            <div id="gf_payment_transaction_id_<?php echo $transaction_id ?>" class="gf_payment_detail">
								<?php echo $transaction_id_label ?>
                                :
                                <span id='gform_payment_transaction_id_<?php echo $transaction_id ?>'><a
                                            href="<?php echo $qm_transaction_link ?>" target="_blank"
                                            rel="noopener noreferrer"
                                            title="Link to item in Qualpay Manager"><?php echo $transaction_id; ?></a></span>
                            </div>
							<?php
						}

						if ( ! rgblank( $payment_amount ) ) {
							?>
                            <div id="gf_payment_amount_<?php echo $transaction_id ?>" class="gf_payment_detail">
								<?php echo $payment_amount_label ?>
                                :
                                <span id="gform_payment_amount_<?php echo $transaction_id ?>"><?php echo $payment_amount; ?></span>
                            </div>
							<?php
						} ?>
                        <br/><br/>
						<?php if ( ! empty( $payment_actions ) ) { ?>
                            <div id="gf_payment_actions_<?php echo $transaction_id ?>">
								<?php foreach ( $payment_actions as $action_link ) {
									?>

                                    <input id=""
                                           type="button"
                                           name="<?php echo $action_link[ 'action' ] ?>"
                                           value="<?php echo $action_link[ 'label' ] ?>"
                                           data-entry="<?php echo absint( $entry[ 'id' ] ); ?>"
                                           data-action="<?php echo $action_link[ 'action' ] ?>"
                                           data-transaction="<?php echo $transaction_id ?>"
                                           data-feed="<?php echo $feed_id ?>"
                                           class="button"
                                           onclick="do_entry_payment_action(this);"
                                           onkeypress="do_entry_payment_action(this);"/>

								<?php }
								?>
                                <img src="<?php echo GFCommon::get_base_url() ?>/images/spinner.gif"
                                     id="payment_action_spinner"
                                     style="display: none;"/>
                                <br/><br/>
                            </div>
						<?php } ?>

                    </div>
                    <hr/>
					<?php
				}


			}

			/**
			 * Fires after the Form Payment Details (The type of payment, the cost, the ID, etc)
			 *
			 * @param int   $form  ['id'] The current Form ID
			 * @param array $entry The current Lead object
			 */
			do_action( 'gform_payment_details', $form[ 'id' ], $entry );
			?>
        </div>
    </div>

	<?php

}