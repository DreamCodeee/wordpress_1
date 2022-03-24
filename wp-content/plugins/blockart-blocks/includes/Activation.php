<?php
/**
 * Activation class.
 *
 * @package BlockArt
 * @since 1.0.0
 */

namespace BlockArt;

defined( 'ABSPATH' ) || exit;

/**
 * Activation class.
 */
class Activation {

	/**
	 * Init.
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		register_activation_hook( BLOCKART_PLUGIN_FILE, array( __CLASS__, 'on_activate' ) );
	}

	/**
	 * Callback for plugin activation hook.
	 */
	public static function on_activate() {
		$blockart_version = get_option( '_blockart_version' );

		if ( empty( $blockart_version ) ) {
			update_option( '_blockart_activation_redirect', true );
		}
	}
}
