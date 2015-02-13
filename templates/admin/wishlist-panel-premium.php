<style>
    .section{
        margin-left: -20px;
        margin-right: -20px;
        font-family: "Raleway";
    }
    .section h1{
        text-align: center;
        text-transform: uppercase;
        color: #2d789a;
        font-size: 35px;
        font-weight: 700;
        line-height: normal;
        display: inline-block;
        width: 100%;
        margin: 50px 0 0;
    }
    .section:nth-child(even){
        background-color: #fff;
    }
    .section:nth-child(odd){
        background-color: #f1f1f1;
    }
    .section .section-title img{
        display: inline-block;
        vertical-align: middle;
        width: auto;
        margin-right: 15px;
    }
    .section .section-title h2{
        display: inline-block;
        vertical-align: middle;
        padding: 0;
        font-size: 24px;
        font-weight: 700;
        color: #306388;
        text-transform: uppercase;
    }
    .section p{
        font-size: 13px;
        margin: 25px 0;
    }
    .section ul li{
        margin-bottom: 4px;
    }
    .landing-container{
        max-width: 750px;
        margin-left: auto;
        margin-right: auto;
        padding: 50px 0 30px;
    }
    .landing-container:after{
        display: block;
        clear: both;
        content: '';
    }
    .landing-container .col-1,
    .landing-container .col-2{
        float: left;
        box-sizing: border-box;
        padding: 0 15px;
    }
    .landing-container .col-1 img{
        width: 100%;
    }
    .landing-container .col-1{
        width: 55%;
    }
    .landing-container .col-2{
        width: 45%;
    }
    .wishlist-cta{
        background-color: #2d789a;
        color: #fff;
        border-radius: 6px;
        padding: 20px 30px;
    }
    .wishlist-cta:after{
        content: '';
        display: block;
        clear: both;
    }
    .wishlist-cta p{
        margin: 7px 0;
        font-size: 16px;
        font-weight: 500;
        display: inline-block;
    }
    .wishlist-cta a.button{
        border-radius: 6px;
        height: 60px;
        float: right;
        background: url(<?php echo YITH_WCWL_URL?>assets/images/landing/icon-6.png) #ff643f no-repeat 13px 13px;
        border-color: #ff643f;
        box-shadow: none;
        outline: none;
        color: #fff;
        position: relative;
        padding: 9px 50px 9px 70px;
    }
    .wishlist-cta a.button:hover,
    .wishlist-cta a.button:active,
    .wishlist-cta a.button:focus{
        color: #fff;
        background: url(<?php echo YITH_WCWL_URL?>assets/images/landing/icon-6.png) #971d00 no-repeat 13px 13px;
        border-color: #971d00;
        box-shadow: none;
        outline: none;
    }
    .wishlist-cta a.button:focus{
        top: 1px;
    }
    .wishlist-cta a.button span{
        line-height: 13px;
    }
    .wishlist-cta a.button .highlight{
        display: block;
        font-size: 20px;
        font-weight: 700;
        line-height: 20px;
    }
    .wishlist-cta .highlight{
        text-transform: uppercase;
        background: none;
        font-weight: 800;
        color: #fff;
    }

    @media (max-width: 480px){
        .wrap{
            margin-right: 0;
        }
        .section{
            margin: 0;
        }
        .landing-container .col-1,
        .landing-container .col-2{
            width: 100%;
            padding: 0 15px;
        }
        .section-odd .col-1 {
            float: left;
            margin-right: -100%;
        }
        .section-odd .col-2 {
            float: right;
            margin-top: 65%;
        }
    }

    @media (max-width: 320px){
        .wishlist-cta a.button{
            padding: 9px 20px 9px 70px;
        }

        .section .section-title img{
            display: none;
        }
    }
</style>
<div class="landing">
    <div class="section section-cta section-odd">
        <div class="landing-container">
            <div class="wishlist-cta">
                <p>
                    Upgrade to the <span class="highlight">premium version</span><br/>
                    of <span class="highlight">YITH wishlist</span> to benefit from all features!
                </p>
                <a href="http://yithemes.com/themes/plugins/yith-woocommerce-wishlist/" target="_blank" class="wishlist-cta-button button btn">
                    <span class="highlight">UPGRADE</span>
                    <span>to the premium version</span>
                </a>
            </div>
        </div>
    </div>
    <div class="section section-even clear" style="background: url(<?php echo YITH_WCWL_URL ?>assets/images/landing/background-1.png) no-repeat #fff; background-position: 85% 75%">
        <h1>Premium Features</h1>
        <div class="landing-container">
            <div class="col-1">
                <img src="<?php echo YITH_WCWL_URL ?>assets/images/landing/multiple-wishlist.png" alt="Multiple Wishlist" />
            </div>
            <div class="col-2">
                <div class="section-title">
                    <img src="<?php echo YITH_WCWL_URL ?>assets/images/landing/icon-1.png" alt="Multiple Wishlist"/>
                    <h2>Multiple Wishlist</h2>
                </div>
                <p>Does it ever happened to you to have too many wishes for a single wish list? The possibility to manage one's wishes is a fundamental feature in a modern e-commerce store and it also lets users' degree of satisfaction increase.</p>
                <p>The option "multiple wishlist" of <strong>YITH Wishlist</strong> makes this feature and many others on your online store available, and thanks to this plugin your customers will be able to create, manage and share their own wish lists.</p>
            </div>
        </div>
    </div>
    <div class="section section-odd clear" style="background: url(<?php echo YITH_WCWL_URL ?>assets/images/landing/background-2.png) no-repeat #f1f1f1; background-position: 15% 100%">
        <div class="landing-container">
            <div class="col-2">
                <div class="section-title">
                    <img src="<?php echo YITH_WCWL_URL ?>assets/images/landing/icon-2.png" alt="Wishlist Private" />
                    <h2>Wishlist Private</h2>
                </div>
                <p>By enabling the option wishlist, users will also have the possibility to manage the visibility of their wish lists according to one of the following options:</p>
                <ul>
                    <li><strong>public:</strong> all users can look for your wish list and see it;</li>
                    <li><strong>shared:</strong> only users possessing a direct link to the wish list page can display it;</li>
                    <li><strong>private:</strong> only the wish list creator can see it.</li>
                </ul>
            </div>
            <div class="col-1">
                <img src="<?php echo YITH_WCWL_URL ?>assets/images/landing/private-wishlist.png" alt="Private Wishlist" />
            </div>
        </div>
    </div>
    <div class="section section-even clear" style="background: url(<?php echo YITH_WCWL_URL ?>assets/images/landing/background-3.png) no-repeat #fff; background-position: 85% 100%">
        <div class="landing-container">
            <div class="col-1">
                <img src="<?php echo YITH_WCWL_URL ?>assets/images/landing/ask-an-estimate.png" alt="Ask an estimate" />
            </div>
            <div class="col-2">
                <div class="section-title">
                    <img src="<?php echo YITH_WCWL_URL ?>assets/images/landing/icon-3.png" alt="Ask an estimate" />
                    <h2>Estimate Cost</h2>
                </div>
                <p>Do you want add the possibility to ask for estimates of costs into your catalogue? Do you want to manage customised packets for faithful customers in your store?</p>
                <p>Thanks to the feature "estimate cost" of <strong>YITH Wishlist</strong>, every registered user will be able to ask for an estimate of their own products in the wish list, by simply clicking and sending an email with all necessary information directly to the address that you have previously set.</p>
            </div>
        </div>
    </div>
    <div class="section section-odd clear" style="background: url(<?php echo YITH_WCWL_URL ?>assets/images/landing/background-4.png) no-repeat #f1f1f1; background-position: 15% 100%">
        <div class="landing-container">
            <div class="col-2">
                <div class="section-title">
                    <img src="<?php echo YITH_WCWL_URL ?>assets/images/landing/icon-4.png" alt="Admin panel" />
                    <h2>Admin Panel</h2>
                </div>
                <p>Thanks to the useful Admin panel, accessible directly among the WooCommerce submenu pages, you will have total control on users' wishlists. In addition to that, evaluating the degree of appreciation for your products has never been so easy, now that you can see a useful report, available directly in the product page, which registers the occurrences of the product in customers' wish lists.</p>
            </div>
            <div class="col-1">
                <img src="<?php echo YITH_WCWL_URL ?>assets/images/landing/admin-panel.png" alt="Admin panel" />
            </div>
        </div>
    </div>
    <div class="section section-even clear" style="background: url(<?php echo YITH_WCWL_URL ?>assets/images/landing/background-5.png) no-repeat #fff; background-position: 85% 100%">
        <div class="landing-container">
            <div class="col-1">
                <img src="<?php echo YITH_WCWL_URL ?>assets/images/landing/search-wishlist.png" alt="Search wishlist" />
            </div>
            <div class="col-2">
                <div class="section-title">
                    <img src="<?php echo YITH_WCWL_URL?>assets/images/landing/icon-5.png" alt="Search wishlist" />
                    <h2>Search Wishlists</h2>
                </div>
                <p>How many times have you been looking for the perfect gift for a important event but you had no idea of what to buy? “Search wishlists” allows your e-shop users to access public wishlists of anyone, by simply knowing their name or email. This way you can grant higher visibility to your products and even encourage users to purchase. </p>
            </div>
        </div>
    </div>
    <div class="section section-cta section-odd">
        <div class="landing-container">
            <div class="wishlist-cta">
                <p>
                    Upgrade to the <span class="highlight">premium version</span><br/>
                    of <span class="highlight">YITH wishlist</span> to benefit from all features!
                </p>
                <a href="http://yithemes.com/themes/plugins/yith-woocommerce-wishlist/" target="_blank" class="wishlist-cta-button button btn">
                    <span class="highlight">UPGRADE</span>
                    <span>to the premium version</span>
                </a>
            </div>
        </div>
    </div>
</div>