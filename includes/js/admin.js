/**
 * Qualpay admin JS
 *
 * Payment actions on the entry detail page
 *
 * @since  1.0.0
 *
 * @author Jankee Patel from Qualpay 
 */



function do_entry_payment_action( button_clicked ) {

    if( ! confirm( gfp_qualpay_admin_strings.payment_action_warning ) ) {

        return;

    }

    var button = jQuery( button_clicked );

    var entry_id = button.data('entry');

    var action = button.data('action');

    var transaction_id = button.data('transaction');

    var feed_id = button.data('feed');


    jQuery("#payment_action_spinner").show();

    jQuery('#gf_payment_actions_' + transaction_id).find('.button').prop('disabled', true);

    jQuery.post(ajaxurl, {
            action: 'gaddon_payment_action',
            entry_id: entry_id,
            payment_action: action,
            transaction_id: transaction_id,
        feed_id: feed_id,
            gaddon_payment_action: gfp_qualpay_admin_strings.payment_action_nonce
        },
        function (response) {

            jQuery('#payment_action_spinner').hide();

            if (true === response.success) {

                jQuery('#gform_payment_status_' + transaction_id ).html( response.data.payment_status );

                jQuery('#gf_payment_actions_' + transaction_id).find('.button').remove();

            }
            else {

                jQuery('#gf_payment_actions_' + transaction_id).find('.button').prop('disabled', false);

                alert(response.data.error);

            }

        }
    );
}