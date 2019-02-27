/**
 * Qualpay frontend JS
 *
 * Load embedded field, tokenize card, and add card_id to form
 *
 * @since  1.0.0
 *
 * @author Naomi C. Bush for gravity+ for Qualpay <support@gravityplus.pro>
 */

jQuery(document).on('gform_post_render', gravityformsqualpay);

function gravityformsqualpay(event, form_id, current_page) {

    if (!window['gf_cc_rules']) {

        window['gf_cc_rules'] = gfp_qualpay_frontend_strings.credit_card_rules;

    }

    if (form_id !== parseInt(gfp_qualpay_frontend_strings.formId)) {

        return;

    }

    if (gfp_qualpay_frontend_strings.hasPages && current_page !== gfp_qualpay_frontend_strings.pageCount) {

        return;

    }

    if (gformQualpayIsPostback(form_id) && gformQualpayHasError(form_id)) {

        qpEmbeddedForm.unloadFrame();

        var post_data = {
            action: 'gaddon_qualpay_transient_key',
            gaddon_qualpay_transient_key: gfp_qualpay_frontend_strings.transient_key_nonce,
            form_id: form_id
        };

        jQuery.post(gfp_qualpay_frontend_strings.ajaxurl, post_data).done(function (response) {

            if (true === response.success) {

                gfp_qualpay_frontend_strings.transientKey = response.data;

                gravityformsqualpay_load_frame();

            }

        });

    }
    else {

        gravityformsqualpay_load_frame();

    }



}

/**
 * Load Qualpay embedded fields frame
 *
 * @since  1.0.0
 *
 * @author Naomi C. Bush for gravity+ for Qualpay <support@gravityplus.pro>
 */
function gravityformsqualpay_load_frame(){

    qpEmbeddedForm.loadFrame(gfp_qualpay_frontend_strings.merchant_id,
        {
            formId: gfp_qualpay_frontend_strings.form_element_id,
            mode: gfp_qualpay_frontend_strings.mode,
            transientKey: gfp_qualpay_frontend_strings.transientKey,
            tokenize: true,
            onSuccess: gravityformsqualpay_success,
            onError: gravityformsqualpay_error
        });
}

/**
 * Success :-)
 *
 * @since  1.0.0
 *
 * @author Naomi C. Bush for gravity+ for Qualpay <support@gravityplus.pro>
 *
 * @param data
 */
function gravityformsqualpay_success(data) {

    var card_id = data.card_id,
        card_number = data.card_number,
        form_id = gfp_qualpay_frontend_strings.formId,
        form = jQuery('#gform_' + form_id),
        creditcard_field_id = gfp_qualpay_frontend_strings.creditcard_field_id;

    form.find(gfp_qualpay_frontend_strings.responseField).val(card_id);

    form.find('#input_' + form_id + '_' + creditcard_field_id + '_1').val(card_number);

    form.find('#input_' + form_id + '_' + creditcard_field_id + '_4').val(gformFindCardType(card_number));


    form.submit();
}

/**
 * Error :-(
 *
 * @todo should we display error and not submit the form?
 *
 * @since  1.0.0
 *
 * @author Naomi C. Bush for gravity+ for Qualpay <support@gravityplus.pro>
 *
 * @param error
 */
function gravityformsqualpay_error(error) {

    var form = jQuery('#gform_' + gfp_qualpay_frontend_strings.formId);

    if (error.detail) {

        var error_detail = '';

        for (let key in error.detail) {

            error_detail += error.detail[key];

        }

        form.find(gfp_qualpay_frontend_strings.errorField).val(error_detail);

    }

    form.submit();

}

/**
 * Is this form a new page load or a postback
 *
 * @since  1.0.0
 *
 * @author Naomi C. Bush for gravity+ for Qualpay <support@gravityplus.pro>
 *
 * @param form_id
 * @returns {boolean}
 */
function gformQualpayIsPostback(form_id) {

    var is_postback = false;

    var ajax_contents = jQuery('#gform_ajax_frame_' + form_id).contents().find('*').html();

    if ('undefined' !== typeof(ajax_contents)) {

        is_postback = (0 < ajax_contents.indexOf('GF_AJAX_POSTBACK'));

    }


    return is_postback;
}

/**
 * Does this form have a validation error
 *
 * @since  1.0.0
 *
 * @author Naomi C. Bush for gravity+ for Qualpay <support@gravityplus.pro>
 *
 * @param form_id
 * @returns {boolean}
 */
function gformQualpayHasError(form_id) {

    return jQuery('#gform_confirmation_wrapper_' + form_id).hasClass('gform_validation_error') || jQuery('#gform_ajax_frame_' + form_id).contents().find('#gform_wrapper_' + form_id).hasClass('gform_validation_error');

}