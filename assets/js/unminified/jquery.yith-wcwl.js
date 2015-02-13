jQuery( document ).ready( function( $ ){

    var cart_redirect_after_add = typeof( wc_add_to_cart_params ) !== 'undefined' ? wc_add_to_cart_params.cart_redirect_after_add : '',
        this_page = window.location.toString();

    $(document).on( 'click', '.add_to_wishlist', function( ev ){
        var t = $( this);

        ev.preventDefault();

        call_ajax_add_to_wishlist( t );

        return false;
    } );

    $(document).on( 'click', '.remove_from_wishlist', function( ev ){
        var t = $( this );

        ev.preventDefault();

        remove_item_from_wishlist( t );

        return false;
    } );

    $(document).on( 'adding_to_cart', 'body', function( ev, button, data ){
        if( button.closest( '.wishlist_table' ).length != 0 ){
            data.remove_from_wishlist_after_add_to_cart = button.closest( 'tr' ).data( 'row-id' );
            data.wishlist_id = button.closest( 'table' ).data( 'id' );
            wc_add_to_cart_params.cart_redirect_after_add = yith_wcwl_l10n.redirect_to_cart;
        }
    } );

    $(document).on( 'added_to_cart', 'body', function( ev ){
        wc_add_to_cart_params.cart_redirect_after_add = cart_redirect_after_add;

        var wishlist = $( '.wishlist_table');

        wishlist.find( '.added' ).removeClass( 'added' );
        wishlist.find( '.added_to_cart' ).remove();
    } );

    $(document).on( 'click', '.show-title-form', show_title_form );

    $(document).on( 'click', '.wishlist-title-with-form h2', show_title_form );

    $(document).on( 'click', '.hide-title-form', hide_title_form );

    $(document).on( 'change', '.yith-wcwl-popup-content .wishlist-select', function( ev ){
        var t = $(this);

        if( t.val() == 'new' ){
            t.parents( '.yith-wcwl-first-row' ).next( '.yith-wcwl-second-row' ).css( 'display', 'table-row' );
        }
        else{
            t.parents( '.yith-wcwl-first-row' ).next( '.yith-wcwl-second-row' ).hide();
        }
    } );

    /**
     * Adds selectbox where needed
     */
    $( 'select.selectBox' ).selectBox();

    /**
     * Add a product in the wishlist.
     *
     * @param string url
     * @param string prod_type
     * @return void
     * @since 1.0.0
     */
    function call_ajax_add_to_wishlist( el ) {
        var product_id = el.data( 'product-id' ),
            el_wrap = $( '.add-to-wishlist-' + product_id ),
            data = {
                add_to_wishlist: product_id,
                product_type: el.data( 'product-type' ),
                action: yith_wcwl_l10n.actions.add_to_wishlist_action
            };

        if( yith_wcwl_l10n.multi_wishlist && yith_wcwl_l10n.is_user_logged_in ){
            var wishlist_popup_container = el.parents( '.yith-wcwl-popup-footer' ).prev( '.yith-wcwl-popup-content' ),
                wishlist_popup_select = wishlist_popup_container.find( '.wishlist-select' ),
                wishlist_popup_name = wishlist_popup_container.find( '.wishlist-name' ),
                wishlist_popup_visibility = wishlist_popup_container.find( '.wishlist-visibility' );

            data.wishlist_id = wishlist_popup_select.val();
            data.wishlist_name = wishlist_popup_name.val();
            data.wishlist_visibility = wishlist_popup_visibility.val();
        }

        if( ! is_cookie_enabled() ){
            alert( yith_wcwl_l10n.labels.cookie_disabled );
            return;
        }

        $.ajax({
            type: 'POST',
            url: yith_wcwl_l10n.ajax_url,
            data: data,
            dataType: 'json',
            beforeSend: function(){
                el.siblings( '.ajax-loading' ).css( 'visibility', 'visible' );
            },
            complete: function(){
                el.siblings( '.ajax-loading' ).css( 'visibility', 'hidden' );
            },
            success: function( response ) {
                var msg = $( '#yith-wcwl-popup-message' ),
                    response_result = response.result,
                    response_message = response.message;

                if( yith_wcwl_l10n.multi_wishlist && yith_wcwl_l10n.is_user_logged_in ) {
                    var wishlist_select = $( 'select.wishlist-select' );
                    $.prettyPhoto.close();

                    wishlist_select.each( function( index ){
                        var t = $(this),
                            wishlist_options = t.find( 'option' );

                        wishlist_options = wishlist_options.slice( 1, wishlist_options.length - 1 );
                        wishlist_options.remove();

                        if( typeof( response.user_wishlists ) != 'undefined' ){
                            var i = 0;
                            for( i in response.user_wishlists ) {
                                if ( response.user_wishlists[i].is_default != "1" ) {
                                    $('<option>')
                                        .val(response.user_wishlists[i].ID)
                                        .html(response.user_wishlists[i].wishlist_name)
                                        .insertBefore(t.find('option:last-child'))
                                }
                            }
                        }
                    } );
                }

                $( '#yith-wcwl-message' ).html( response_message );
                msg.css( 'margin-left', '-' + $( msg ).width() + 'px' ).fadeIn();
                window.setTimeout( function() {
                    msg.fadeOut();
                }, 2000 );

                if( response_result == "true" ) {
                    if( ! yith_wcwl_l10n.multi_wishlist || ! yith_wcwl_l10n.is_user_logged_in || ( yith_wcwl_l10n.multi_wishlist && yith_wcwl_l10n.is_user_logged_in && yith_wcwl_l10n.hide_add_button ) ) {
                        el_wrap.find('.yith-wcwl-add-button').hide().removeClass('show').addClass('hide');
                    }

                    el_wrap.find( '.yith-wcwl-wishlistexistsbrowse').hide().removeClass('show').addClass('hide').find('a').attr('href', response.wishlist_url);
                    el_wrap.find( '.yith-wcwl-wishlistaddedbrowse' ).show().removeClass('hide').addClass('show').find('a').attr('href', response.wishlist_url);
                } else if( response_result == "exists" ) {
                    if( ! yith_wcwl_l10n.multi_wishlist || ! yith_wcwl_l10n.is_user_logged_in || ( yith_wcwl_l10n.multi_wishlist && yith_wcwl_l10n.is_user_logged_in && yith_wcwl_l10n.hide_add_button ) ) {
                        el_wrap.find('.yith-wcwl-add-button').hide().removeClass('show').addClass('hide');
                    }

                    el_wrap.find( '.yith-wcwl-wishlistexistsbrowse' ).show().removeClass('hide').addClass('show').find('a').attr('href', response.wishlist_url);
                    el_wrap.find( '.yith-wcwl-wishlistaddedbrowse' ).hide().removeClass('show').addClass('hide').find('a').attr('href', response.wishlist_url);
                } else {
                    el_wrap.find( '.yith-wcwl-add-button' ).show().removeClass('hide').addClass('show');
                    el_wrap.find( '.yith-wcwl-wishlistexistsbrowse' ).hide().removeClass('show').addClass('hide');
                    el_wrap.find( '.yith-wcwl-wishlistaddedbrowse' ).hide().removeClass('show').addClass('hide');
                }

                $('body').trigger('added_to_wishlist');
            }

        });
    }

    /**
     * Remove a product from the wishlist.
     *
     * @param string url
     * @param int rowid
     * @return void
     * @since 1.0.0
     */
    function remove_item_from_wishlist( el ) {
        var table = el.parents( '.cart.wishlist_table' ),
            pagination = table.data( 'pagination' ),
            per_page = table.data( 'per-page' ),
            current_page = table.data( 'page' ),
            row = el.parents( 'tr' ),
            pagination_row = table.find( '.pagination-row'),
            data_row_id = row.data( 'row-id'),
            data = {
                action: yith_wcwl_l10n.actions.remove_from_wishlist_action,
                remove_from_wishlist: data_row_id,
                pagination: pagination,
                per_page: per_page,
                current_page: current_page
            };

        $( '#yith-wcwl-message' ).html( '&nbsp;' );

        table.fadeTo( '400', '0.6' ).block({ message: null, overlayCSS: { background: 'transparent url(' + yith_wcwl_l10n.ajax_loader_url + ') no-repeat center', backgroundSize: '16px 16px', opacity: 0.6 } } );

        $( '#yith-wcwl-form' ).load( yith_wcwl_l10n.ajax_url, data, function(){
            table.stop( true ).css( 'opacity', '1' ).unblock();
        } );
    }

    /**
     * Show form to edit wishlist title
     *
     * @param ev event
     * @return void
     * @since 2.0.0
     */
    function show_title_form( ev ){
        var t = $(this);
        ev.preventDefault();

        t.parents( '.wishlist-title' ).next().show();
        t.parents( '.wishlist-title' ).hide();
    }

    /**
     * Hide form to edit wishlist title
     *
     * @param ev event
     * @return void
     * @since 2.0.0
     */
    function hide_title_form( ev ) {
        var t = $(this);
        ev.preventDefault();

        t.parents( '.hidden-title-form').hide();
        t.parents( '.hidden-title-form').prev().show ();
    }

    /**
     * Check if cookies are enabled
     *
     * @return bool
     * @since 2.0.0
     */
    function is_cookie_enabled() {
        if (navigator.cookieEnabled) return true;

        // set and read cookie
        document.cookie = "cookietest=1";
        var ret = document.cookie.indexOf("cookietest=") != -1;

        // delete cookie
        document.cookie = "cookietest=1; expires=Thu, 01-Jan-1970 00:00:01 GMT";

        return ret;
    }
});