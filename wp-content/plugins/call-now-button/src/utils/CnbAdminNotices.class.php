<?php
// don't load directly
defined( 'ABSPATH' ) || die( '-1' );

/**
 * @var $type string one of info, success, warning, error
 * @var $dismissable boolean false by default
 */
class CnbNotice {
    public function __construct($type = null, $message = null, $dismissable = false, $dismiss_option = null) {
        $this->type = $type;
        $this->message = $message;
        $this->dismissable = $dismissable;
        $this->dismiss_option = $dismiss_option;
    }

    /**
     * @var string|null info, success, warning, error
     */
    public $type;
    public $message;
    public $dismissable;
    public $dismiss_option;
}

class CnbNotices {
}

class CnbAdminNotices {

    private static $_instance;
    /**
     * @var CnbNotices
     */
    private $admin_notices;
    const TYPES = 'error,warning,info,success';

    private function __construct() {
        $this->admin_notices = new CnbNotices();
        foreach ( explode( ',', self::TYPES ) as $type ) {
            $this->admin_notices->{$type} = array();
        }
        add_action('admin_init', array(&$this, 'action_admin_init'));
        add_action('cnb_admin_notices', array(&$this, 'action_admin_notices'));
    }

    public static function get_instance() {
        if ( ! ( self::$_instance instanceof self ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function action_admin_init() {
        $dismiss_option = filter_input( INPUT_GET, CNB_SLUG . '_dismiss', FILTER_SANITIZE_STRING );
        if ( is_string( $dismiss_option ) && !empty($dismiss_option) ) {
            update_option( CNB_SLUG . '_dismissed_' . $dismiss_option, true );
            do_action($dismiss_option);
            wp_die(
                __( 'Dismissed notice: ' . $dismiss_option, CNB_NAME), __( 'Dismissed notice', CNB_NAME),
                array(
                    'response' 	=> 200,
                )
            );
        }
    }

    /**
     * @param $notice CnbNotice
     */
    public function renderNotice($notice) {
        if ($notice == null) {
            return;
        }

        $dismiss_classes = '';
        $dismiss_data_url = '';
        if ( $notice->dismiss_option ) {
            $notice->dismissable = true;
            $url = admin_url('admin.php');
            $dismiss_url = add_query_arg( array(
                CNB_SLUG . '_dismiss' => $notice->dismiss_option
            ), $url );

            $dismiss_data_url .= ' data-dismiss-url="' . esc_url( $dismiss_url ) . '"';
        }

        if ($notice->dismissable) {
            $dismiss_classes .= ' is-dismissible';
        }

        // Check if this particular Notice has already been dismissed
        $option = CNB_SLUG . '_dismissed_' . $notice->dismiss_option;
        if ( !$notice->dismiss_option || !get_option( $option ) ) {
            echo '<div class="notice notice-' . CNB_SLUG . ' notice-' . $notice->type . $dismiss_classes . '"'.$dismiss_data_url.'>';
            echo $notice->message;
            echo '</div>';
        }
    }

    public function get_dismiss_option_name($name) {
        return CNB_SLUG . '_dismissed_' . $name;
    }

    public function is_dismissed($name) {
        return get_option( $name );
    }

    public function action_admin_notices() {
        foreach ( explode( ',', self::TYPES ) as $type ) {
            foreach ( $this->admin_notices->{$type} as $admin_notice ) {
                $option = $this->get_dismiss_option_name($admin_notice->dismiss_option);
                if ( !$admin_notice->dismiss_option || !$this->is_dismissed( $option ) ) {
                    $this->renderNotice($admin_notice);
                }
            }
        }
    }

    /**
     * @param $notices CnbNotice[]
     */
    public function notices( $notices ) {
        foreach ($notices as $notice) {
            $this->notice($notice);
        }
    }

    /**
     * @param $notice CnbNotice
     */
    public function notice( $notice ) {
        $notice = $this->createNotice( $notice->type, $notice->message, $notice->dismissable, $notice->dismiss_option );
        $this->addNotice($notice);
    }

    public function error( $message, $dismiss_option = false ) {
        $notice = $this->createNotice( 'error', $message, false, $dismiss_option );
        $this->addNotice($notice);
    }

    public function renderError( $message, $dismiss_option = false ) {
        $notice = $this->createNotice( 'error', $message, false, $dismiss_option );
        $this->renderNotice($notice);
    }

    public function warning( $message, $dismiss_option = false ) {
        $notice = $this->createNotice( 'warning', $message, false, $dismiss_option );
        $this->addNotice($notice);
    }

    public function renderWarning( $message, $dismiss_option = false ) {
        $notice = $this->createNotice( 'warning', $message, false, $dismiss_option );
        $this->renderNotice($notice);
    }

    public function success( $message, $dismiss_option = false ) {
        $notice = $this->createNotice( 'success', $message, false, $dismiss_option );
        $this->addNotice($notice);
    }

    public function renderSuccess( $message, $dismiss_option = false ) {
        $notice = $this->createNotice( 'success', $message, false, $dismiss_option );
        $this->renderNotice($notice);
    }

    public function info( $message, $dismiss_option = false ) {
        $notice = $this->createNotice( 'info', $message, false,  $dismiss_option );
        $this->addNotice($notice);
    }

    public function renderInfo( $message, $dismiss_option = false ) {
        $notice = $this->createNotice( 'info', $message, false, $dismiss_option );
        $this->renderNotice($notice);
    }

    /**
     * @var $type string one of info, success, warning, error
     * @param $message string
     * @param $dismissable boolean
     * @param $dismiss_option boolean
     *
     * @return CnbNotice
     */
    private function createNotice( $type, $message, $dismissable = false, $dismiss_option = false ) {
        $notice = new CnbNotice();
        $notice->message = $message;
        $notice->dismissable = $dismissable;
        $notice->dismiss_option = $dismiss_option;
        $notice->type = $type;
        return $notice;
    }

    /**
     * @param $notice CnbNotice
     */
    private function addNotice( $notice ) {
        $this->admin_notices->{$notice->type}[] = $notice;
    }
}
