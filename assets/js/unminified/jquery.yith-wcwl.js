jQuery( document ).ready( function( $ ){

    var cart_redirect_after_add = typeof( wc_add_to_cart_params ) !== 'undefined' ? wc_add_to_cart_params.cart_redirect_after_add : '',
        this_page = window.location.toString(),
        checkboxes = $( '.wishlist_table tbody input[type="checkbox"]:not(:disabled)');

    $(document).on( 'click', '.add_to_wishlist', function( ev ) {
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

    $(document).on( 'added_to_cart', 'body', print_add_to_cart_notice );

    $(document).on( 'cart_page_refreshed', 'body', init_handling_after_ajax );

    $(document).on( 'click', '.show-title-form', show_title_form );

    $(document).on( 'click', '.wishlist-title-with-form h2', show_title_form );

    $(document).on( 'click', '.hide-title-form', hide_title_form );

    $(document).on( 'change', '.change-wishlist', function( ev ){
        var t = $(this);

        move_item_to_another_wishlist( t );

        return false;
    } );

    $(document).on( 'change', '.yith-wcwl-popup-content .wishlist-select', function( ev ){
        var t = $(this);

        if( t.val() == 'new' ){
            t.parents( '.yith-wcwl-first-row' ).next( '.yith-wcwl-second-row' ).css( 'display', 'table-row' );
        }
        else{
            t.parents( '.yith-wcwl-first-row' ).next( '.yith-wcwl-second-row' ).hide();
        }
    } );

    $(document).on( 'change', '#bulk_add_to_cart', function(){
        var t = $(this);

        if( t.is( ':checked' ) ){
            checkboxes.attr( 'checked','checked').change();
        }
        else{
            checkboxes.removeAttr( 'checked').change();
        }
    } );

    $(document).on( 'click', '#custom_add_to_cart', function(ev){
        var t = $(this),
            table = t.parents( '.cart.wishlist_table' );

        if( ! yith_wcwl_l10n.ajax_add_to_cart_enabled ){
            return;
        }

        ev.preventDefault();

        if( typeof $.fn.block != 'undefined' ) {
            table.fadeTo('400', '0.6').block({message: null,
                overlayCSS                           : {
                    background    : 'transparent url(' + yith_wcwl_l10n.ajax_loader_url + ') no-repeat center',
                    backgroundSize: '16px 16px',
                    opacity       : 0.6
                }
            });
        }

        $( '#yith-wcwl-form' ).load( yith_wcwl_l10n.ajax_url + t.attr( 'href' ) + ' #yith-wcwl-form', {action: yith_wcwl_l10n.actions.bulk_add_to_cart_action}, function(){

            if( typeof $.fn.unblock != 'undefined' ) {
                table.stop(true).css('opacity', '1').unblock();
            }

            if( typeof $.prettyPhoto != 'undefined' ) {
                $('a[data-rel="prettyPhoto[ask_an_estimate]"]').prettyPhoto({
                    hook              : 'data-rel',
                    social_tools      : false,
                    theme             : 'pp_woocommerce',
                    horizontal_padding: 20,
                    opacity           : 0.8,
                    deeplinking       : false
                });
            }

            checkboxes.off('change');
            checkboxes = $( '.wishlist_table tbody input[type="checkbox"]');

            if( typeof $.fn.selectBox != 'undefined' ) {
                $('select.selectBox').selectBox();
            }

            handle_wishlist_checkbox();
        } );
    } );

    add_wishlist_popup();

    handle_wishlist_checkbox();

    /**
     * Adds selectbox where needed
     */
    if( typeof $.fn.selectBox != 'undefined' ) {
        $('select.selectBox').selectBox();
    }

    /**
     * Init js handling on wishlist table items after ajax update
     *
     * @return void
     * @since 2.0.7
     */
    function init_handling_after_ajax(){
        if( typeof $.prettyPhoto != 'undefined' ) {
            $('a[data-rel="prettyPhoto[ask_an_estimate]"]').prettyPhoto({
                hook              : 'data-rel',
                social_tools      : false,
                theme             : 'pp_woocommerce',
                horizontal_padding: 20,
                opacity           : 0.8,
                deeplinking       : false
            });
        }

        checkboxes.off('change');
        checkboxes = $( '.wishlist_table tbody input[type="checkbox"]');

        if( typeof $.fn.selectBox != 'undefined' ) {
            $('select.selectBox').selectBox();
        }

        handle_wishlist_checkbox();
    }

    /**
     *
     */
    function print_add_to_cart_notice(){
        var messages = $( '.woocommerce-message');

        if( messages.length == 0 ){
            $( '#yith-wcwl-form').prepend( yith_wcwl_l10n.labels.added_to_cart_message );
        }
        else{
            messages.fadeOut( 300, function(){
                $(this).replaceWith( yith_wcwl_l10n.labels.added_to_cart_message ).fadeIn();
            } );
        }
    }

    /**
     * Add a product in the wishlist.
     *
     * @param object el
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
                    if( typeof $.prettyPhoto != 'undefined' ) {
                        $.prettyPhoto.close();
                    }

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
     * @param object el
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
            wishlist_id = table.data( 'id' ),
            wishlist_token = table.data( 'token' ),
            data = {
                action: yith_wcwl_l10n.actions.remove_from_wishlist_action,
                remove_from_wishlist: data_row_id,
                pagination: pagination,
                per_page: per_page,
                current_page: current_page,
                wishlist_id: wishlist_id,
                wishlist_token: wishlist_token
            };

        $( '#yith-wcwl-message' ).html( '&nbsp;' );

        if( typeof $.fn.block != 'undefined' ) {
            table.fadeTo('400', '0.6').block({message: null,
                overlayCSS                           : {
                    background    : 'transparent url(' + yith_wcwl_l10n.ajax_loader_url + ') no-repeat center',
                    backgroundSize: '16px 16px',
                    opacity       : 0.6
                }
            });
        }

        $( '#yith-wcwl-form' ).load( yith_wcwl_l10n.ajax_url + ' #yith-wcwl-form', data, function(){

            if( typeof $.fn.unblock != 'undefined' ) {
                table.stop(true).css('opacity', '1').unblock();
            }

            init_handling_after_ajax();

            $('body').trigger('removed_from_wishlist');
        } );
    }

    /**
     * Remove a product from the wishlist.
     *
     * @param object el
     * @return void
     * @since 1.0.0
     */
    function reload_wishlist_and_adding_elem( el, form ) {

        var product_id = el.data( 'product-id' ),
            table = $(document).find( '.cart.wishlist_table' ),
            pagination = table.data( 'pagination' ),
            per_page = table.data( 'per-page' ),
            wishlist_id = table.data( 'id' ),
            wishlist_token = table.data( 'token' ),
            data = {
                action: yith_wcwl_l10n.actions.reload_wishlist_and_adding_elem_action,
                pagination: pagination,
                per_page: per_page,
                wishlist_id: wishlist_id,
                wishlist_token: wishlist_token,
                add_to_wishlist: product_id,
                product_type: el.data( 'product-type' )
            };

        if( ! is_cookie_enabled() ){
            alert( yith_wcwl_l10n.labels.cookie_disabled );
            return
        }

        $.ajax({
            type: 'POST',
            url: yith_wcwl_l10n.ajax_url,
            data: data,
            dataType    : 'html',
            beforeSend: function(){
                if( typeof $.fn.block != 'undefined' ) {
                    table.fadeTo('400', '0.6').block({message: null,
                        overlayCSS                           : {
                            background    : 'transparent url(' + yith_wcwl_l10n.ajax_loader_url + ') no-repeat center',
                            backgroundSize: '16px 16px',
                            opacity       : 0.6
                        }
                    });
                }
            },
            success: function(res) {
                var obj      = $(res),
                    new_form = obj.find('#yith-wcwl-form'); // get new form

                form.replaceWith( new_form );
                init_handling_after_ajax();
            }
        });
    }

    $('.yith-wfbt-add-wishlist').on('click', function(e){
        e.preventDefault();
        var t    = $(this),
            form = $( '#yith-wcwl-form' );

        $('html, body').animate({
            scrollTop: ( form.offset().top)
        },500);

        // ajax call
        reload_wishlist_and_adding_elem( t, form );
    });

    /**
     * Move item to another wishlist
     *
     * @param object el
     * @return void
     * @since 2.0.7
     */
    function move_item_to_another_wishlist( el ){
        var table = el.parents( '.cart.wishlist_table'),
            wishlist_token = table.data( 'token'),
            wishlist_id = table.data( 'id' ),
            item = el.parents( 'tr'),
            item_id = item.data( 'row-id'),
            to_token = el.val(),
            pagination = table.data( 'pagination' ),
            per_page = table.data( 'per-page' ),
            current_page = table.data( 'page' ),
            data = {
                action: yith_wcwl_l10n.actions.move_to_another_wishlist_action,
                wishlist_token: wishlist_token,
                wishlist_id: wishlist_id,
                destination_wishlist_token: to_token,
                item_id: item_id,
                pagination: pagination,
                per_page: per_page,
                current_page: current_page
            };

        if( to_token == '' ){
            return;
        }

        if( typeof $.fn.block != 'undefined' ) {
            table.fadeTo('400', '0.6').block({message: null,
                overlayCSS                           : {
                    background    : 'transparent url(' + yith_wcwl_l10n.ajax_loader_url + ') no-repeat center',
                    backgroundSize: '16px 16px',
                    opacity       : 0.6
                }
            });
        }

        $( '#yith-wcwl-form' ).load( yith_wcwl_l10n.ajax_url + ' #yith-wcwl-form', data, function(){

            if( typeof $.fn.unblock != 'undefined' ) {
                table.stop(true).css('opacity', '1').unblock();
            }

            init_handling_after_ajax();

            $('body').trigger('moved_to_another_wishlist');
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

    /**
     * Add wishlist popup message
     *
     * @return void
     * @since 2.0.0
     */
    function add_wishlist_popup() {
        if( $('.yith-wcwl-add-to-wishlist').length != 0 && $( '#yith-wcwl-popup-message' ).length == 0 ) {
            var message_div = $( '<div>' )
                    .attr( 'id', 'yith-wcwl-message' ),
                popup_div = $( '<div>' )
                    .attr( 'id', 'yith-wcwl-popup-message' )
                    .html( message_div )
                    .hide();

            $( 'body' ).prepend( popup_div );
        }
    }

    /**
     * Handle "Add to cart" checkboxes events
     *
     * @return void
     * @since 2.0.5
     */
    function handle_wishlist_checkbox() {
        checkboxes.on( 'change', function(){
            var ids = '',
                table = $(this).parents( '.cart.wishlist_table'),
                wishlist_id = table.data( 'id'),
                wishlist_token = table.data( 'token'),
                url = document.URL;

            checkboxes.filter(':checked').each( function(){
                var t = $(this);
                ids += ( ids.length != 0 ) ? ',' : '';
                ids += t.parents('tr').data( 'row-id' );
            } );

            url = add_query_arg( url, 'wishlist_products_to_add_to_cart', ids );
            url = add_query_arg( url, 'wishlist_token', wishlist_token );
            url = add_query_arg( url, 'wishlist_id', wishlist_id );

            $('#custom_add_to_cart').attr( 'href', url );
        } );
    }

    /**
     * Add a query arg to an url
     *
     * @param purl  original url
     * @param key   query argr key
     * @param value query arg value
     * @return string
     * @since 2.0.7
     */
    function add_query_arg(purl, key,value){
        var s = purl;
        var pair = key+"="+value;

        var r = new RegExp("(&|\\?)"+key+"=[^\&]*");

        s = s.replace(r,"$1"+pair);

        if(s.indexOf(key + '=')>-1){


        }
        else{
            if(s.indexOf('?')>-1){
                s+='&'+pair;
            }else{
                s+='?'+pair;
            }
        }

        return s;
    }
});