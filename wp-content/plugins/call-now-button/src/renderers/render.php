<?php

/**
 * Find the proper renderer and load it. The renderer is responsible for adding itself to the proper hooks.
 *
 * Proper hooks are (probably/likely) `wp_head` and `wp_footer`
 *
 * This function should be scheduled on the front-end (only), usually via the `wp_loaded` hook.
 *
 * @global array $cnb_options The Call Now Buttons options array
 * @return void
 */
function cnb_render_button() {
    global $cnb_options;

    // If we're in the "wp_loaded" hook, we don't NEED is_admin(), but it's good to have as a safety check
    if ( ! is_admin() && isButtonActive( $cnb_options ) ) {
        $cnb_has_text   = ( $cnb_options['text'] == '' ) ? false : true;
        $cnb_is_classic = $cnb_options['classic'] == 1 && ! $cnb_has_text;

        $renderer = is_use_cloud( $cnb_options ) ? 'cloud' : ( $cnb_is_classic ? 'classic' : 'modern' );

        require_once dirname( __FILE__ ) . "/$renderer/wp_head.php";
        require_once dirname( __FILE__ ) . "/$renderer/wp_foot.php";
    }
}

// This queues the front-end to be rendered (`wp_loaded` should only fire on the front-end facing site
add_action('wp_loaded', 'cnb_render_button');
