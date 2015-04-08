jQuery( document ).ready( function( $ ){
    /* === COLORS TAB === */
    $('input#yith_wcwl_frontend_css').on('change',function () {
        if ($(this).is(':checked')) {
            $('#yith_wcwl_styles_colors').hide();
            $('#yith_wcwl_rounded_corners').parents('tr').hide();
            $('#yith_wcwl_add_to_wishlist_icon').parents('tr').hide();
            $('#yith_wcwl_add_to_cart_icon').parents('tr').hide();
        } else {
            $('#yith_wcwl_styles_colors').show();
            if ($('#yith_wcwl_use_button').is(':checked')) {
                $('#yith_wcwl_rounded_corners').parents('tr').show();
                $('#yith_wcwl_add_to_wishlist_icon').parents('tr').show();
                $('#yith_wcwl_add_to_cart_icon').parents('tr').show();
            }
        }
    }).change();

    $('input#yith_wcwl_use_button').on('change',function () {
        if ($(this).is(':checked') && !$('#yith_wcwl_frontend_css').is(':checked')) {
            $('#yith_wcwl_rounded_corners').parents('tr').show();
            $('#yith_wcwl_add_to_wishlist_icon').parents('tr').show();
            $('#yith_wcwl_add_to_cart_icon').parents('tr').show();
        } else {
            $('#yith_wcwl_rounded_corners').parents('tr').hide();
            $('#yith_wcwl_add_to_wishlist_icon').parents('tr').hide();
            $('#yith_wcwl_add_to_cart_icon').parents('tr').hide();
        }
    }).change();

    $('#yith_wcwl_multi_wishlist_enable').on('change', function () {
        if ($(this).is(':checked')) {
            $('#yith_wcwl_wishlist_create_title').parents('tr').show();
            $('#yith_wcwl_wishlist_manage_title').parents('tr').show();
        }
        else{
            $('#yith_wcwl_wishlist_create_title').parents('tr').hide();
            $('#yith_wcwl_wishlist_manage_title').parents('tr').hide();
        }
    }).change();

    /* === SETTINGS TAB === */
    $('input#yith_wcwl_disable_wishlist_for_unauthenticated_users').on('change',function () {
        if ($(this).is(':checked')) {
            $('#yith_wcwl_show_login_notice').parents('tr').hide();
            $('#yith_wcwl_login_anchor_text').parents('tr').hide();
        }
        else{
            $('#yith_wcwl_show_login_notice').parents('tr').show();
            $('#yith_wcwl_login_anchor_text').parents('tr').show();
        }
    }).change();

    $('input#yith_wcwl_show_estimate_button').on('change',function () {
        if ($(this).is(':checked')) {
            var additional_info = $('#yith_wcwl_show_additional_info_textarea');

            additional_info.parents('tr').show();
            additional_info.on( 'change', function(){
                if ($(this).is(':checked')) {
                    $('#yith_wcwl_additional_info_textarea_label').parents('tr').show()
                }
                else{
                    $('#yith_wcwl_additional_info_textarea_label').parents('tr').hide()
                }
            }).change();
        }
        else{
            $('#yith_wcwl_show_additional_info_textarea').parents('tr').hide();
            $('#yith_wcwl_additional_info_textarea_label').parents('tr').hide()
        }
    }).change();
} );