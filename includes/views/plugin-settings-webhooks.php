<?php
/**
 * Qualpay plugin settings Webhooks section description
 *
 * Display webhooks status
 *
 * @since  1.0.0
 *
 * @author Naomi C. Bush for gravity+ for Qualpay <support@gravityplus.pro>
 */
?>
<div id="qualpay-webhooks-description">

    <p><?php _e( 'Test webhook: ', 'gravityformsqualpay' ) ?><span class="<?php echo $test_webhook_status_class ?>"><?php echo $test_webhook_status ?></span></p>
    <p><?php _e( 'Live webhook: ', 'gravityformsqualpay' ) ?><span class="<?php echo $live_webhook_status_class ?>"><?php echo $live_webhook_status ?></span></p>

</div>