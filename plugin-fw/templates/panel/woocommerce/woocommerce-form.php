<div id="<?php echo $this->settings['page']?>_<?php echo $this->get_current_tab()?>">
    <form id="plugin-fw-wc" method="post">
        <?php $this->add_fields() ?>
        <?php wp_nonce_field( 'yit_panel_wc_options_'.$this->settings['page'], 'yit_panel_wc_options_nonce' ); ?>
        <input style="float: left; margin-right: 10px;" class="button-primary" type="submit" value="<?php _e( 'Save Changes', 'yith-plugin-fw' )?>"/>
    </form>
    <form id="plugin-fw-wc-reset" method="post">
        <?php $warning = __( 'If you continue with this action, you will reset all options in this page.', 'yith-plugin-fw' ) ?>
        <input type="hidden" name="yit-action" value="wc-options-reset" />
        <input type="submit" name="yit-reset" class="button-secondary" value="<?php _e( 'Reset Defaults', 'yith-plugin-fw' ) ?>" onclick="return confirm('<?php echo $warning . '\n' . __( 'Are you sure?', 'yith-plugin-fw' ) ?>');" />
    </form>
</div>