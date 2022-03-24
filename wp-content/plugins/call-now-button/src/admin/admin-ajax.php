<?php
require_once dirname( __FILE__ ) . '/api/CnbAppRemotePayment.php';
require_once dirname( __FILE__ ) . '/api/CnbAppRemote.php';
require_once dirname( __FILE__ ) . '/action-overview.php';
require_once dirname( __FILE__ ) . '/settings-profile.php';

// part of domain-upgrade
function cnb_admin_page_domain_upgrade_get_checkout() {
    $planId  = filter_input( INPUT_POST, 'planId', FILTER_SANITIZE_STRING );
    $domainId  = filter_input( INPUT_POST, 'domainId', FILTER_SANITIZE_STRING );

    $url = admin_url('admin.php');
    $redirect_link =
        add_query_arg(
            array(
                'page' => 'call-now-button-domains',
                'action' => 'upgrade',
                'id' => $domainId,
                'upgrade' => 'success'),
            $url );
    $callbackUri = esc_url_raw( $redirect_link );
    $checkoutSession = CnbAppRemotePayment::cnb_remote_post_subscription( $planId, $domainId, $callbackUri );

    if (is_wp_error($checkoutSession)) {
        $custom_message_data = $checkoutSession->get_error_data('CNB_ERROR');
        if (!empty($custom_message_data)) {
            $custom_message_obj = json_decode( $custom_message_data );
            $message            = $custom_message_obj->message;
            // Strip "request_id"
            if (stripos($message, '; request-id') !== 0) {
                $message = preg_replace('/; request-id.*/i', '', $message);
            }
            // Replace "customer" with "domain"
            $message = str_replace('customer', 'domain', $message);
            wp_send_json( array(
                'status'  => 'error',
                'message' => $message
            ) );
        } else {
            wp_send_json( array(
                'status'  => 'error',
                'message' => $checkoutSession->get_error_message()
            ) );
        }
    } else {
        // Get link based on Stripe checkoutSessionId
        wp_send_json( array(
            'status'  => 'success',
            'message' => $checkoutSession->checkoutSessionId
        ) );
    }
    wp_die();
}
add_action('wp_ajax_cnb_get_checkout', 'cnb_admin_page_domain_upgrade_get_checkout');

function cnb_admin_button_delete_actions()  {
    // Action ID
    $action_id = !empty($_REQUEST['id']) ? sanitize_text_field($_REQUEST['id']) : null;
    $button_id = !empty($_REQUEST['bid']) ? sanitize_text_field($_REQUEST['bid']) : null;

    $result = cnb_delete_action_real($action_id, $button_id);
    // Instead of sending just the actual result (which is currently ignored anyway)
    // We sent both the result and an updated button so the preview code can re-render the button
    $return = array(
        'result' => $result,
        'button' => CnbAppRemote::cnb_remote_get_button_full( $button_id )
    );
    wp_send_json($return);

}

add_action('wp_ajax_cnb_delete_action', 'cnb_admin_button_delete_actions');

function cnb_admin_settings_profile_save()  {
    $data = array();
    wp_parse_str($_REQUEST['data'], $data);
    $result = cnb_admin_profile_edit_process_real($data['_wpnonce'], $data['user']);
    wp_send_json($result);
}

add_action('wp_ajax_cnb_settings_profile_save', 'cnb_admin_settings_profile_save');

function cnb_admin_cnb_email_activation()  {
    $admin_email = esc_attr(get_bloginfo('admin_email'));
    $admin_url = esc_url( admin_url('admin.php') );

    $custom_email = trim(filter_input( INPUT_POST, 'admin_email', FILTER_SANITIZE_STRING ));
    if (!empty($custom_email)) {
        $admin_email = $custom_email;
    }
    $data = CnbAppRemote::cnb_remote_email_activation($admin_email, $admin_url);
    wp_send_json($data);
}

add_action('wp_ajax_cnb_email_activation', 'cnb_admin_cnb_email_activation');

function cnb_time_format_($time) {
    $time_format = get_option('time_format');
    $time_formatted = strtotime($time);
    return date_i18n( $time_format, $time_formatted );
}

function cnb_time_format()  {
    $start = trim(filter_input( INPUT_POST, 'start', FILTER_SANITIZE_STRING ));
    $stop = trim(filter_input( INPUT_POST, 'stop', FILTER_SANITIZE_STRING ));
    wp_send_json(array(
        'start' => cnb_time_format_($start),
        'stop' => cnb_time_format_($stop),
        )
    );
}

add_action('wp_ajax_cnb_time_format', 'cnb_time_format');
