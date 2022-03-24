<?php

namespace cnb\cli;

use WP_CLI;
use WP_CLI_Command;

require_once dirname( __FILE__ ) . '/CNB_CLI_Api.class.php';
require_once dirname( __FILE__ ) . '/CNB_CLI_User.class.php';
require_once dirname( __FILE__ ) . '/CNB_CLI_Button.class.php';

/**
 * Adds, removes and fetches Call Now Buttons objects
 *
 * @since  1.0.6
 * @author Jasper Roel
 *
 * @noinspection PhpUnused (it is used as a WP CLI class)
 */
class CNB_CLI extends WP_CLI_Command {

    /**
     * Registers the Call Now Button commands when CLI gets initialized.
     *
     * @noinspection PhpUnused (it is used via cli_init)
     */
    static function cli_register_command() {
        WP_CLI::add_command( 'cnb', 'cnb\cli\CNB_CLI' );
    }
}

add_action( 'cli_init', '\cnb\cli\CNB_CLI::cli_register_command' );
