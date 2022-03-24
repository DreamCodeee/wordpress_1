<?php

require_once dirname( __FILE__ ) . '/api/CnbAppRemote.php';
require_once dirname( __FILE__ ) . '/api/CnbAppRemotePayment.php';
require_once dirname( __FILE__ ) . '/api/CnbAdminCloud.php';
require_once dirname( __FILE__ ) . '/partials/admin-functions.php';
require_once dirname( __FILE__ ) . '/partials/admin-header.php';
require_once dirname( __FILE__ ) . '/partials/admin-footer.php';
require_once dirname( __FILE__ ) . '/models/CnbDomain.class.php';

require_once dirname( __FILE__ ) . '/partials/domain-upgrade/overview.php';
require_once dirname( __FILE__ ) . '/partials/domain-upgrade/upgraded.php';
require_once dirname( __FILE__ ) . '/partials/domain-upgrade/upgrading.php';

require_once dirname( __FILE__ ) . '/settings-profile.php';

function cnb_add_header_domain_upgrade() {
    echo 'Upgrade the Call Now Button';
}

/**
 * @return CnbDomain
 */
function cnb_get_domain() {
    $domain_id = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_STRING );
    $domain = new CnbDomain();
    if (strlen($domain_id) > 0 && $domain_id != 'new') {
        $domain = CnbAppRemote::cnb_remote_get_domain( $domain_id );
    }
    return $domain;
}

/**
 * @param $domain CnbDomain
 *
 * @return CnbNotice
 */
function cnb_print_domain_upgrade_notice($domain) {
    $upgradeStatus = filter_input( INPUT_GET, 'upgrade', FILTER_SANITIZE_STRING );
    $checkoutSesionId = filter_input( INPUT_GET, 'checkout_session_id', FILTER_SANITIZE_STRING );
    if ($upgradeStatus === 'success?payment=success') {
        // Get checkout Session Details
        $session = CnbAppRemotePayment::cnb_remote_get_subscription_session( $checkoutSesionId );
        if (!is_wp_error($session)) {
            // This results in a subscription (via ->subscriptionId), get that for ->type
            $subscription = CnbAppRemotePayment::cnb_remote_get_subscription( $session->subscriptionId );

            // This increases the cache ID if needed, since the Domain cache might have changed
            CnbAppRemote::cnb_incr_transient_base();

            return new CnbNotice( 'success', '<p>Your domain <strong>' . esc_html( $domain->name ) . '</strong> has been successfully upgraded to <strong>' . esc_html( $subscription->type ) . '</strong>!</p>' );
        } else {
            return new CnbNotice( 'warning', '<p>Something is going on upgrading domain <strong>' . esc_html( $domain->name ) . '</strong>.</p><p>Error: '.$session->get_error_message().'!</p>' );
        }
    }
    return null;
}

function cnb_admin_page_domain_upgrade_render_content() {
    $domain = CnbDomain::setSaneDefault(cnb_get_domain());

    // Bail out in case of error
    if (is_wp_error($domain)) {
        return;
    }

    // See if the domain is JUST upgraded
    $notice = cnb_print_domain_upgrade_notice( $domain );
    if ( $notice ) {
        // And if so, refetch the domain
        $domain = CnbDomain::setSaneDefault( cnb_get_domain() );
    }
    wp_enqueue_script(CNB_SLUG . '-domain-upgrade');

    // Stripe integration
    echo '<script src="https://js.stripe.com/v3/"></script>';
    echo '
    <script>
        jQuery(() => {
        try {
            stripe = Stripe("' . esc_js( CnbAppRemotePayment::cnb_remote_get_stripe_key()->key ) . '");
        } catch(e) {
            // Do not show "Live Stripe.js integrations must use HTTPS", we deal with that particular error internally
            if (e && e.message.includes("Live Stripe.js integrations must use HTTPS")) {
                return;
            }
            
            showMessage("error", e);
        }
        });
    </script>';

    // Print the content
    if ( $notice && $domain->type != 'PRO' ) {
        // Probably upgraded, but not reflected yet on the API side. Warn about this
        cnb_domain_upgrade_upgrading($domain);
    } else if ( $domain->type == 'PRO' ) {
        cnb_domain_upgrade_upgraded( $domain, $notice );
    } else {
        $user = cnb_admin_page_domain_upgrade_hidden_profile();
        cnb_domain_upgrade_overview( $domain, $user );
    }
}

function cnb_admin_page_domain_upgrade_hidden_profile() {
    add_thickbox();
    echo '<div id="cnb_admin_page_domain_upgrade_profile" style="display: none;"><div>';
    $user = cnb_admin_page_profile_edit_render_form(true);
    echo '</div></div>';
    return $user;
}

function cnb_admin_page_domain_upgrade_render() {

    add_action('cnb_header_name', 'cnb_add_header_domain_upgrade');

    do_action('cnb_header');
    cnb_admin_page_domain_upgrade_render_content();
    do_action('cnb_footer');
}
