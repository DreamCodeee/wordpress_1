<?php

require_once dirname( __FILE__ ) . '/CnbAdminNotices.class.php';
require_once dirname( __FILE__ ) . '/../admin/api/CnbAdminCloud.php';

/**
 * @param $cnb_changelog
 * @param $cnb_old_version "$cnb_options['changelog_version']" most likely
 *
 * @return string
 */
function cnb_get_changelog_message($cnb_changelog, $cnb_old_version) {
    $message = '';
    foreach ( $cnb_changelog as $key => $value ) {
        if ( $key > $cnb_old_version ) {
            $message .= '<h3>' . esc_html($key) . '</h3>';
            if ( is_array( $value ) ) {
                foreach ( $value as $item ) {
                    $message .= '<p><span class="dashicons dashicons-yes"></span> ' . esc_html($item) . '</p>';
                }
            } else {
                $message .= '<p><span class="dashicons dashicons-yes"></span> ' . esc_html($value) . '</p>';
            }
        }
    }
    return $message;
}

function cnb_get_upgrade_notice_dismiss_name() {
    return 'cnb_update_'.CNB_VERSION;
}
/**
 * Create a dismissable notice to inform users about changes
 * @param $cnb_options
 * @param $cnb_changelog
 *
 * @return void
 */
function cnb_upgrade_notice($cnb_options, $cnb_changelog) {
    $changelog = cnb_get_changelog_message($cnb_changelog, get_changelog_version($cnb_options));

    if (empty($changelog)) return;

    $message = '<h3 id="cnb_is_updated">' . CNB_NAME . ' has been updated!</h3><h4>What\'s new?</h4>';
    $message .= $changelog;

    $adminNotices = CnbAdminNotices::get_instance();
    $adminNotices->warning($message, cnb_get_upgrade_notice_dismiss_name());
}

function cnb_settings_email_activation_input() {
    $message = '<div id="cnb_email_activation_alternate_formd">';
    $message .= '<input type="text" class="cnb_activation_input_field" name="cnb_email_activation_alternate_address" id="cnb_email_activation_alternate_address" placeholder="email@example.com" value="' . esc_attr(get_bloginfo('admin_email')) . '"/> ';
    $message .= get_submit_button(__('Activate Premium'), 'primary', 'cnb_email_activation_alternate', false, array('onclick' => 'return cnb_email_activation_alternate()'));
    $message .= '</div>';
    $message .= '<p id="cnb_email_activation"></p>';

    $message .= '<p class="nonessential">By clicking <u>Activate Premium</u> an account will be created with your email address on callnowbutton.com and you agree to our <a href="https://callnowbutton.com/terms.html" target="_blank">Terms & Conditions</a> and <a href="https://callnowbutton.com/privacy.html" target="_blank">Privacy statement</a>. You also acknowledge that the Free plan allows up to 20k pageviews per month.</p>';

    return $message;
}

function cnb_settings_get_account_missing_notice() {
    $message = '<h3 class="title">Activating Premium</h3>
            <p>To activate Premium, you\'ll need a <a href="https://app.callnowbutton.com/register" target="_blank">callnowbutton.com</a> account and an API key. There\'s 2 ways to do this:</p>

            <h4>Option 1: Email activation (easy and fast!)</h4>';
    $message .= cnb_settings_email_activation_input();

    $message .= '
            <hr>
            <h4>Option 2: Web activation (manual process)</h4>
            <ol>
                <li>Create your account at <a href="https://app.callnowbutton.com?utm_source=wp-plugin&utm_medium=referral&utm_campaign=beta_tester&utm_term=sign-up-for-api">https://app.callnowbutton.com</a></li>
                <li>Go to your profile info by clicking on the user icon in the top right corner and then click <strong>Create new API key</strong>.</li>
                <li>Copy the API key that appears, paste it into the field below and click <strong>Store API key</strong>.</li>
            </ol>';
    $message .= cnb_settings_api_key_input();

    $adminNotices = CnbAdminNotices::get_instance();
    $adminNotices->warning($message);
}

function cnb_settings_api_key_invalid_notice() {
    $message = '<h3 class="title">Ooops, that API key doesn\'t seem right</h3>
            <p>The saved API key is invalid. Let\'s give it another try:</p>
            <ol>
                <li>Head over to the profile panel on <a href="https://app.callnowbutton.com/app/profile" target="_blank">app.callnowbutton.com</a>.</li>
                <li>Click <strong>Create new API key</strong>, enter a unique name (can be anything. I always enter my website domain here) and click <strong>Generate new API key</strong>.</li>
                <li>Copy the API key, paste it into the field below and click <strong>Save key</strong>.</li>
            </ol>
            <p>If it\'s still not working, we might be experiencing server issues. Please wait a few minutes and try again. You can check our <a target="_blank" href="https://status.callnowbutton.com">status page</a> to be sure.</p>';
    $message .= cnb_settings_api_key_input();

    $adminNotices = CnbAdminNotices::get_instance();
    $adminNotices->warning($message);
}

function get_cnb_generic_error_notice() {
    return '<h3 class="title">Something went wrong!</h3>
            <p>Something has gone wrong and we do not know why...</p>
            <p>As unlikely as it is, our service might be experiencing issues (check <a href="https://status.callnowbutton.com">our status page</a>).</p>
            <p>If you think you\'ve found a bug, please report it at our <a href="https://callnowbutton.com/support/" target="_blank">Help Center</a>.';
}

function cnb_generic_error_notice($user) {
    $message = get_cnb_generic_error_notice();
    $message .= CnbAdminCloud::cnb_admin_get_error_message_details($user);

    $adminNotices = CnbAdminNotices::get_instance();
    $adminNotices->warning($message);
}

function cnb_settings_api_key_input() {
    $message = '<form method="post" action="' . esc_url( admin_url('options.php') ) . '" class="cnb-container">';
    ob_start();
    settings_fields('cnb_options');
    $message .= ob_get_clean();
    $message .= '<input type="hidden" name="page" value="call-now-button-settings" />
            <div>
              <input type="text" class="cnb_activation_input_field" name="cnb[api_key]"
                           placeholder="Paste API key here"/>
                    '. get_submit_button(__('Store API key'), 'primary', 'submit', false).'
                </div>
            </form>';
    return $message;
}

function cnb_settings_get_domain_missing_notice($domain) {
    $message = '<h3 class="title">Domain not found yet</h3>
                <p>You have enabled Call Now Button Premium and you are logged in. Now we need to create this domain remotely.</p>
                <p>
                <form action="' . esc_url( admin_url('admin-post.php') ) . '" method="post">
                    <input type="hidden" name="page" value="call-now-button-settings" />
                    <input type="hidden" name="action" value="cnb_create_cloud_domain" />
                    <input type="hidden" name="_wpnonce" value="' . wp_create_nonce('cnb_create_cloud_domain') .'" />
                    '. get_submit_button(__('Create domain'), 'secondary', 'submit', false).'
                </form>
                </p>';
    $message .= CnbAdminCloud::cnb_admin_get_error_message_details( $domain );

    $adminNotices = CnbAdminNotices::get_instance();
    $adminNotices->warning($message);
}

function cnb_settings_get_button_missing_notice() {
    $message = '<h3 class="title">Creating your first button</h3>
            <p>You have enabled Call Now Button Premium and your domain have been set up.
            Now it\'s time to create your first button.</p>
            <p>To make it easy, we can migrate your existing button to your account.</p>
            <p><form action="'. esc_url( admin_url('admin-post.php') ) .'" method="post">
                <input type="hidden" name="page" value="call-now-button-settings" />
                <input type="hidden" name="action" value="cnb_migrate_legacy_button" />
                <input type="hidden" name="_wpnonce" value="'. wp_create_nonce('cnb_migrate_legacy_button') .'" />
                '. get_submit_button(__('Migrate button'), 'secondary', 'submit', false).'
            </form></p>';

    $notice = new CnbNotice('warning', $message);
    $notice->dismiss_option = 'cnb_settings_get_button_missing_notice';
    $adminNotices = CnbAdminNotices::get_instance();
    $adminNotices->notice($notice);
}

function cnb_settings_get_buttons_missing_notice($error) {
    $message = '<h3 class="title">Could not retrieve Buttons</h3>
            <p>Something unexpected went wrong retrieving the Buttons for this API key</p>';
    $message .= CnbAdminCloud::cnb_admin_get_error_message_details( $error );

    $adminNotices = CnbAdminNotices::get_instance();
    $adminNotices->warning($message);
}

function cnb_api_key_invalid_notice($error) {
    $url = admin_url('admin.php');
    $redirect_link =
        add_query_arg(
            array(
                'page' => 'call-now-button-settings',
            ),
            $url );
    $redirect_url = esc_url( $redirect_link );

    $message = '<h3 class="title">API Key invalid</h3>
            <p>You have enabled Call Now Button Premium, but you still need a valid API key.</p>
            <p>Go to <a href="'.$redirect_url.'">Settings</a> for instructions.</p>';
    $message .= CnbAdminCloud::cnb_admin_get_error_message_details( $error );

    $adminNotices = CnbAdminNotices::get_instance();
    $adminNotices->renderError($message);

}

function cnb_button_disabled_notice() {
    $url = admin_url('admin.php');
    $redirect_link =
        add_query_arg(
            array(
                'page' => 'call-now-button',
            ),
            $url );
    $redirect_url = esc_url( $redirect_link );

    $message = '<p>The Call Now Button is currently <strong>inactive</strong>.';

    $adminNotices = CnbAdminNotices::get_instance();
    $adminNotices->warning($message);
}

function cnb_button_classic_enabled_but_no_number_notice() {
    $url = admin_url('admin.php');
    $redirect_link =
        add_query_arg(
            array(
                'page' => 'call-now-button',
            ),
            $url );
    $redirect_url = esc_url( $redirect_link );

    $message = '<p>The Call Now Button is currently <strong>active without a phone number</strong>.
        Change the <i>Button status</i> under <a href="'.$redirect_url.'">My button</a> to disable or enter a phone number.</p>';

    $adminNotices = CnbAdminNotices::get_instance();
    $adminNotices->warning($message);
}

function cnb_caching_plugin_warning_notice($caching_plugin_name) {
    $message = '<p><span class="dashicons dashicons-warning"></span>
        Your website is using a <strong><i>Caching Plugin</i></strong> (' . $caching_plugin_name . ').
        If you\'re not seeing your button or your changes, make sure you empty your cache first.</p>';

    $adminNotices = CnbAdminNotices::get_instance();
    $adminNotices->error($message);
}

function cnb_show_welcome_banner() {
    $dismiss_value = 'welcome-panel';
    $dismissed_option = CnbAdminNotices::get_instance()->get_dismiss_option_name($dismiss_value);
    $is_dismissed = CnbAdminNotices::get_instance()->is_dismissed($dismissed_option);
    return !$is_dismissed;
}

function cnb_get_welcome_banner() {
    if (!cnb_show_welcome_banner()) return;
    $dismiss_value = 'welcome-panel';

    $url = admin_url('admin.php');
    $upgrade_link =
        add_query_arg(
            array('page' => 'call-now-button-upgrade'),
            $url );
    $upgrade_url = esc_url( $upgrade_link );

    $dismiss_data_url = '';
    $dismiss_url = add_query_arg( array(
        CNB_SLUG . '_dismiss' => $dismiss_value
    ), $url );

    $dismiss_data_url .= ' data-dismiss-url="' . esc_url( $dismiss_url ) . '"';

    ?>
    <div id="welcome-banner" class="welcome-banner is-dismissible notice-call-now-button" <?php echo $dismiss_data_url ?>>
        <div class="welcome-banner-content">
            <h2>Welcome to Call&nbsp;Now&nbsp;Button&nbsp;verson&nbsp;1.0</h2>
            <p class="about-description">After 10 years we have finally reached v1!</p>
            <div class="welcome-banner-column-container">
                <div class="welcome-banner-column">
                  <h3>Here's why</h3>
                  <div class="welcome-column-box">
                    <p class="only-in-columns">Why we promoted the plugin from the zeros to a 1:</p>

                    <p class="cnb-mobile-inline">🎉 The Call Now Button is turning 10 years!</p>
                    <p class="cnb-mobile-inline">❤️ 200k+ active installs and rated 4.9!</p>
                    <p class="cnb-mobile-inline">💎 Call Now Button <strong>Premium</strong> is finally here!</p>
                  </div>
                </div>
                <div class="welcome-banner-column">
                  <h3>What's Premium?</h3>
                  <p class="cnb-mobile-inline">+ Create multiple buttons</p>
                  <p class="cnb-mobile-inline">+ WhatsApp, SMS/text, Email, Maps and Links</p>
                  <p class="cnb-mobile-inline">+ Multi action buttons</p>
                  <p class="cnb-mobile-inline">+ Button scheduler</p>
                  <p class="cnb-mobile-inline">+ Advanced page targeting</p>
                </div>
                <div class="welcome-banner-column">
                    <a class="button button-primary button-hero" href="<?php echo $upgrade_url ?>">Try Premium for Free</a>
                    <p><a href="<?php echo $upgrade_url ?>">More info about Premium</a></p>
                    <h3>Other resources</h3>
                    <p><a href="<?php echo CNB_SUPPORT; ?>wordpress-free/">The new help center</a></p>
                    <p><a href="<?php echo CNB_SUPPORT; ?>wordpress-free/#faq">FAQ</a></p>
                </div>
            </div>
        </div>
        <button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php _e('Dismiss this notice.') ?></span></button>
    </div>
<?php }

/**
 *
 * Also warning if timezone is not yet set
 * @param $domain
 *
 * @return boolean true is all is alright
 */
function cnb_warn_about_timezone($domain) {
    if ($domain && !is_wp_error($domain)) {
        $domain_timezone = $domain->timezone;
        if ( empty( $domain_timezone ) ) {
            $url           = admin_url( 'admin.php' );
            $redirect_link =
                add_query_arg(
                    array(
                        'page' => 'call-now-button-settings',
                        'tab'  => 'advanced_options#domain_timezone',

                    ),
                    $url );
            $redirect_url  = esc_url( $redirect_link );
            CnbAdminNotices::get_instance()->renderWarning( "<p>Please set your timezone in the <a href=\"" . $redirect_url . "\">Advanced settings</a> tab to avoid unpredictable behavior when using the scheduler.</p>" );

            return false;
        }
    }
    return true;
}
