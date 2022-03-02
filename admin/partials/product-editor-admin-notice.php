<?php
/**
 * Admin View: Welcome Notice
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( get_option('pe_hide_note_welcome') ) {
    return;
}
?>
<div id="message" class="notice notice-info is-dismissible">
    <a class="notice-dismiss" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'hide_notice_welcome')), 'pe_hide_notice_welcome', 'nonce' ) ); ?>"></a>
    <p>
        <strong><?php esc_html_e('Greetings!', 'product-editor');?></strong>
    </p>
    <p>
        <?php
        printf( wp_kses(
                __( 'If you are not familiar with the plugin, please watch this <a href="%s" target="_blank">video</a> first.<br/> If you still have questions, you can ask them under the video, on the plugin\'s <a href="%s" target="_blank">forum</a> or email <a href="mailto:%s">%s</a>.', 'product-editor' ),
                array(  'a' => array( 'href' => array() ), 'br' => array() ) ),
            // video url
            PRODUCT_EDITOR_VIDEO_URL,
            // forum url
            'https://wordpress.org/support/plugin/product-editor/',
            // email
            PRODUCT_EDITOR_SUPPORT_EMAIL, PRODUCT_EDITOR_SUPPORT_EMAIL
        );
        ?>
        <br/><br/>
    </p>
    <p>
        <?php
        printf( wp_kses(
            __( 'I would be grateful if you leave <a href="%s" target="_blank">plugin feedback</a>.', 'product-editor' ),
            array(  'a' => array( 'href' => array() ), 'br' => array() ) ),
            // review url
            'https://wordpress.org/plugins/product-editor/#reviews'
        );
        ?>
        <br/><br/>
    </p>
    <p>
        <?php
        printf( wp_kses(
            __( 'If you need additional functionality or just want to financially support the development of the plugin - write to <a href="mailto:%s">%s</a>', 'product-editor' ),
            array(  'a' => array( 'href' => array() ), 'br' => array() ) ),
            // email
            PRODUCT_EDITOR_SUPPORT_EMAIL, PRODUCT_EDITOR_SUPPORT_EMAIL
        );
        ?>
    </p>
</div>
