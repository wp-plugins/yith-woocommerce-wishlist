/**
 * This file belongs to the YIT Plugin Framework.
 *
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE (GPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 */


(function ($) {

    /* === Licence API === */

    var licence_activation = function (button) {
        button.on('click', function (e, button) {
            e.preventDefault();

            var t = $(this),
                form_id = t.data('formid'),
                form = $('#' + form_id),
                data = form.serialize(),
                message = $(form).find('.message'),
                message_wrapper = $(form).find('.message-wrapper'),
                email = form.find('.user-email'),
                licence_key = form.find('.licence-key'),
                email_val = form.find('.user-email').val(),
                licence_key_val = form.find('.licence-key').val(),
                error = false,
                error_fields = new Array(),
                product_row = form.find('.product-row'),
                spinner = $('h3.to-active').find('.spinner');

            /* Init Input Fields */
            message.empty();
            message_wrapper.removeClass('visible')
            email.removeClass('require');
            licence_key.removeClass('require');
            product_row.removeClass('error');
            spinner.addClass('show');
            t.prop("disabled", true).addClass('clicked');

            if ('' == email_val) {
                error = true;
                error_fields[ error_fields.length ] = 'Email';
                email.addClass('require');
            }

            if ('' == licence_key_val) {
                error = true;
                error_fields[ error_fields.length ] = 'Licence Key';
                licence_key.addClass('require');
            }

            if (false == error) {
                jQuery.ajax({
                    type   : 'POST',
                    url    : ajaxurl,
                    data   : data,
                    success: function (response) {

                        spinner.removeClass('show');
                        t.prop("disabled", false).removeClass('clicked');

                        if (true == response.activated) {
                            $('.product-licence-activation').empty().replaceWith(response.template);
                            licence_api();
                        } else if (false != response) {
                            message.text(response.error);
                            message_wrapper.addClass('visible');
                            product_row.addClass('error');
                        } else {
                            message.text(licence_message.server);
                            message_wrapper.addClass('visible');
                            product_row.addClass('error');
                        }
                    }
                });
            } else {
                if (error_fields.length == 1) {
                    message.text(licence_message.error.replace('%field%', error_fields[0]));
                    message_wrapper.addClass('visible');
                    product_row.addClass('error');
                } else {
                    var message_text = licence_message.errors;
                    for (var i = 0; i < error_fields.length; i++) {
                        message_text = message_text.replace('%field_' + ( i + 1) + '%', error_fields[i]);
                        message_wrapper.addClass('visible');
                    }
                    message.text(message_text);
                    message_wrapper.addClass('visible');
                    product_row.addClass('error');
                }

                spinner.removeClass('show');
                t.prop("disabled", false).removeClass('clicked');
            }
        });
    }

    var licence_update = function (button) {
        button.on('click', function (e) {
            e.preventDefault();

            var t = $(this),
                form = $('#licence-check-update'),
                data = form.serialize();

            t.prop("disabled", true).addClass('clicked');
            form.find('div.spinner').addClass('show');

            jQuery.ajax({
                type   : 'POST',
                url    : ajaxurl,
                data   : data,
                success: function (response) {
                    $('.product-licence-activation').empty().replaceWith(response.template);
                    licence_api();
                }
            });
        });
    }

    var licence_api = function () {
        var button = $('.licence-activation'),
            check = $('.licence-check');

        licence_activation(button);
        licence_update(check);
    }

    licence_api();

    $('body').on('click', '.yit-changelog-button', function (e) {
        $('#TB_window').remove();

    });

})(jQuery);