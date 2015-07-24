<?php
/**
 * Share template
 *
 * @author Your Inspiration Themes
 * @package YITH WooCommerce Wishlist
 * @version 2.0.9
 */
?>

<div class="yith-wcwl-share">
    <h4 class="yith-wcwl-share-title"><?php echo $share_title ?></h4>
    <ul>
        <?php if( $share_facebook_enabled ): ?>
            <li style="list-style-type: none; display: inline-block;">
                <a target="_blank" class="facebook" href="https://www.facebook.com/sharer.php?s=100&amp;p%5Btitle%5D=<?php echo $share_link_title ?>&amp;p%5Burl%5D=<?php echo $share_link_url ?>&amp;p%5Bsummary%5D=<?php echo $share_summary ?>&amp;p%5Bimages%5D%5B0%5D=<?php echo $share_image_url ?>" title="<?php _e( 'Facebook', 'yit' ) ?>"></a>
            </li>
        <?php endif; ?>

        <?php if( $share_twitter_enabled ): ?>
            <li style="list-style-type: none; display: inline-block;">
                <a target="_blank" class="twitter" href="https://twitter.com/share?url=<?php echo $share_link_url ?>&amp;text=<?php echo $share_twitter_summary ?>" title="<?php _e( 'Twitter', 'yit' ) ?>"></a>
            </li>
        <?php endif; ?>

        <?php if( $share_pinterest_enabled ): ?>
            <li style="list-style-type: none; display: inline-block;">
                <a target="_blank" class="pinterest" href="http://pinterest.com/pin/create/button/?url=<?php echo $share_link_url ?>&amp;description=<?php echo $share_summary ?>&amp;media=<?php echo $share_image_url ?>" title="<?php _e( 'Pinterest', 'yit' ) ?>" onclick="window.open(this.href); return false;"></a>
            </li>
        <?php endif; ?>

        <?php if( $share_googleplus_enabled ): ?>
            <li style="list-style-type: none; display: inline-block;">
                <a target="_blank" class="googleplus" href="https://plus.google.com/share?url=<?php echo $share_link_url ?>&amp;title=<?php echo $share_link_title ?>" title="<?php _e( 'Google+', 'yit' ) ?>" onclick='javascript:window.open(this.href, "", "menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=600,width=600");return false;'></a>
            </li>
        <?php endif; ?>

        <?php if( $share_email_enabled ): ?>
            <li style="list-style-type: none; display: inline-block;">
                <a class="email" href="mailto:?subject=I%20wanted%20you%20to%20see%20this%20site&amp;body=<?php echo $share_link_url ?>&amp;title=<?php echo $share_link_title ?>" title="<?php _e( 'Email', 'yit' ) ?>"></a>
            </li>
        <?php endif; ?>
    </ul>
</div>