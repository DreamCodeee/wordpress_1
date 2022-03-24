<?php

require_once dirname( __FILE__ ) . '/api/CnbAppRemote.php';
require_once dirname( __FILE__ ) . '/api/CnbAdminCloud.php';
require_once dirname( __FILE__ ) . '/domain-edit.php';
require_once dirname( __FILE__ ) . '/legacy-edit.php';
require_once dirname( __FILE__ ) . '/partials/admin-functions.php';
require_once dirname( __FILE__ ) . '/partials/admin-header.php';
require_once dirname( __FILE__ ) . '/partials/admin-footer.php';
require_once dirname( __FILE__ ) . '/../utils/notices.php';

function cnb_add_header_settings() {
    echo 'Settings';
}

function cnb_settings_create_tab_url($tab, $additional_query_args = array()) {
    $url = admin_url('admin.php');
    $tab_link =
        add_query_arg(
            array_merge(
                array(
                    'page' => 'call-now-button-settings',
                    'tab' => $tab),
                $additional_query_args
            ),
        $url );
    return esc_url_raw( $tab_link );
}

/**
 * For the Legacy button, disallow setting it to active with a missing phone number
 *
 * @param array $input
 *
 * @return void|WP_Error
 */
function cnb_settings_disallow_active_without_phone_number($input) {
    $number = trim($input['number']);
    $cloud_enabled = array_key_exists('cloud_enabled', $input) ? $input['cloud_enabled'] : 0;
    if ($input['active'] == 1 && $cloud_enabled == 0 && empty($number)) {
        return new WP_Error('CNB_PHONE_NUMBER_MISSING', 'Please enter a phone number before enabling your button.');
    }
}

/**
 * @param array $input
 *
 * @return array The new (fully adjusted) settings for <code>cnb</code>
 */
function cnb_settings_options_validate($input) {
    $original_settings = get_option('cnb');

    // Since "active" and "cloud_enabled" have been merged into "status", we have to deal with that
    if (array_key_exists('status', $input)) {
        switch ($input['status']) {
            case 'disabled':
                $input['active'] = 0;
                $input['cloud_enabled'] = 0;
                break;
            case 'enabled':
                $input['active'] = 1;
                $input['cloud_enabled'] = 0;
                break;
            case 'cloud':
                $input['active'] = 1;
                $input['cloud_enabled'] = 1;
                break;
        }
    }

    $messages = array();

    // Cloud Domain settings can be set here as well
    if(array_key_exists('domain', $_POST) &&
       array_key_exists('cloud_enabled', $input) &&
       $input['cloud_enabled'] == 1) {
        $domain = $_POST['domain'];
        $transient_id = null;
        cnb_admin_page_domain_edit_process_domain($domain, $transient_id);
        $message = get_transient($transient_id);

        // Only add the message to the results if something went wrong
        if (is_array($message) && sizeof($message) === 1 &&
            $message[0] instanceof CnbNotice && $message[0]->type != 'success') {
            $messages = array_merge( $messages, $message);
        }

        // Remove from settings
        unset($input['domain']);
    }

    // If api_key is empty, assume unchanged and unset it (so it uses the old value)
    if (isset($input['api_key']) && empty($input['api_key'])) {
        unset($input['api_key']);
    }
    // If api_key is "delete_me", this is the special value to trigger "forget this key"
    if ((isset($input['api_key']) && $input['api_key'] === 'delete_me') ||
        (isset($original_settings['api_key']) && $original_settings['api_key'] === 'delete_me') ) {
        $input['api_key'] = '';
        $updated_options['api_key'] = '';
        $input['cloud_use_id'] = '';
        $updated_options['cloud_use_id'] = '';

        $messages[] = new CnbNotice( 'success', '<p>Your API key has been removed - you can now activate Call Now Button with another API key.</p>' );
    }

    $updated_options = array_merge($original_settings, $input);

    // If the cloud is enabled, this is a fail-safe to ensure the user ID is set, even if it isn't
    // explicitly set by the user YET. Since the whole "cnb[cloud_use_id]" input field doesn't exist yet...
    if (isset($updated_options['cloud_enabled']) && $updated_options['cloud_enabled'] == 1) {
        $cloud_id = CnbAdminCloud::cnb_set_default_option_for_cloud( $updated_options );
        // Normally, this returns null, since there is a cnb[cloud_use_id].
        if ($cloud_id != null) {
            $updated_options['cloud_use_id'] = $cloud_id;
        }
    }

    if (!empty($original_settings['api_key']) && !empty($input['api_key']) && $original_settings['api_key'] !== $input['api_key']) {
        unset($updated_options['cloud_use_id']);
        $cloud_id = CnbAdminCloud::cnb_set_default_option_for_cloud( $updated_options );
        // Normally, this returns null, since there is a cnb[cloud_use_id].
        if ($cloud_id != null) {
            $updated_options['cloud_use_id'] = $cloud_id;
        }
    }

    $version_upgrade = $original_settings['version'] != $updated_options['version'] || $original_settings['changelog_version'] != $updated_options['changelog_version'];

    // Check for legacy button
    $check = cnb_settings_disallow_active_without_phone_number($updated_options);
    if (is_wp_error($check)) {
        if ($check->get_error_code() === 'CNB_PHONE_NUMBER_MISSING') {
            $messages[] = new CnbNotice( 'warning', '<p>Your settings have been updated, but your button could <strong>not</strong> be enabled. Please enter a <i>Phone number</i>.</p>' );
            // Reset enabled/active to false
            $updated_options['active'] = 0;
        } else {
            // This part is VERY generic and should not be reached, since
            // cnb_settings_disallow_active_without_phone_number() returns a single WP_Error.
            // But just in case, this is here for other unseen errors..
            $messages[] = CnbAdminCloud::cnb_admin_get_error_message( 'save', 'settings', $check );
        }
    } else if ($version_upgrade) {
        // NOOP - Do nothing for a version upgrade
    } else {
        $messages[] = new CnbNotice( 'success', '<p>Your settings have been updated!</p>' );
    }

    $transient_id = 'cnb-options';
    $_GET['tid'] = $transient_id;
    set_transient($transient_id, $messages, HOUR_IN_SECONDS);

    return $updated_options;
}

function cnb_admin_settings_create_cloud_domain($cnb_user) {
    $nonce = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
    if( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $nonce, 'cnb_create_cloud_domain') ) {
        return CnbAdminCloud::cnb_wp_create_domain( $cnb_user );
    }
    return null;
}

function cnb_admin_settings_migrate_legacy_to_cloud() {
    $nonce = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
    if( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $nonce, 'cnb_migrate_legacy_button') ) {
        return CnbAdminCloud::cnb_wp_migrate_button();
    }
    return null;
}

function cnb_admin_setting_migrate() {
    // Update the cloud if requested
    $cnb_cloud_notifications = array();

    $action = !empty($_POST['action']) ? sanitize_text_field($_POST['action']) : null;
    switch ($action) {
        case 'cnb_create_cloud_domain':
            $cnb_user = CnbAppRemote::cnb_remote_get_user_info();
            $cnb_cloud_notifications = cnb_admin_settings_create_cloud_domain($cnb_user);
            break;
        case 'cnb_migrate_legacy_button':
            $cnb_cloud_notifications = cnb_admin_settings_migrate_legacy_to_cloud();
            break;
    }

    // redirect the user to the appropriate page
    $transient_id = 'cnb-' . wp_generate_uuid4();
    set_transient($transient_id, $cnb_cloud_notifications, HOUR_IN_SECONDS);

    // Create link
    $url = admin_url('admin.php');
    $redirect_link =
        add_query_arg(
            array(
                'page' => 'call-now-button-settings',
                'tid' => $transient_id,
            ),
            $url );
    $redirect_url = esc_url_raw( $redirect_link );
    wp_safe_redirect($redirect_url);
    exit;
}

/**
 * @param $api_key_ott
 *
 * @return {string} The API key if found
 */
function cnb_try_api_key_ott($api_key_ott, $render_notice=true) {
    $api_key_obj = CnbAppRemote::cnb_remote_get_apikey_via_ott($api_key_ott);
    if ($api_key_obj !== null) {
        if (!is_wp_error($api_key_obj)) {
            return $api_key_obj->key;
        } else {
            if (empty($cnb_options['api_key']) && $render_notice) {
                $error_details = CnbAdminCloud::cnb_admin_get_error_message_details( $api_key_obj );
                $message       = '<p>We could not enable <strong>Premium</strong> with the <em>one-time token</em> <code>' . esc_html( $api_key_ott ) . '</code> :-(.' . $error_details . '</p>';
                $notice        = new CnbNotice( 'error', $message );
                CnbAdminNotices::get_instance()->renderNotice( $notice );
            }
        }
    }
    return null;
}

function cnb_admin_settings_page() {
    global $cnb_options;

    add_action('cnb_header_name', 'cnb_add_header_settings');

    // Fix for https://github.com/callnowbutton/wp-plugin/issues/263
    $cnb_options['cloud_enabled'] = isset($cnb_options['cloud_enabled']) ? $cnb_options['cloud_enabled'] : 0;

    // Parse special header(s)
    $api_key = null;
    $api_key_ott = filter_input(INPUT_GET, 'api_key_ott', FILTER_SANITIZE_STRING);
    if (!empty($api_key_ott)) {
        $api_key = cnb_try_api_key_ott($api_key_ott);
    }

    // If the API key was not passed via api_key_ott, see if it was passed directly via api_key
    if (!$api_key) {
        $api_key = filter_input( INPUT_GET, 'api_key', FILTER_SANITIZE_STRING );
    }

    $options = array();
    if (empty($cnb_options['api_key']) && $api_key) {
        // This also enabled the cloud
        $options['cloud_enabled'] = 1;
        $options['api_key'] = $api_key;
        update_option('cnb', $options);

        $cnb_options['cloud_enabled'] = 1;
        $cnb_options['api_key'] = $api_key;
        cnb_reset_options();
    // In case a token is provided (api_key[_ott]) but there already is an API key (so no need to update)
    } else if (!empty($cnb_options['api_key']) && ($api_key || $api_key_ott) && $cnb_options['cloud_enabled'] != 1) {
        CnbAdminNotices::get_instance()->warning("<p>You have followed a link, but an API key is already present or the token has expired.</p><p>We have enabled <strong>Premium</strong>, but did not change the already present API key.</p>");
        $options['cloud_enabled'] = 1;
        update_option('cnb', $options);

        $cnb_options['cloud_enabled'] = 1;
        cnb_reset_options();
    }

    /**
     * @type CnbDomain
     */
    $cnb_cloud_domain = null;
    $cnb_cloud_domains = array();
    $cnb_clean_site_url = null;
    $cnb_user = null;
    if ($cnb_options['cloud_enabled']) {
        $cnbAppRemote = new CnbAppRemote();
        $cnb_user     = CnbAppRemote::cnb_remote_get_user_info();

        if ( ! ( $cnb_user instanceof WP_Error ) ) {
            // Let's check if the domain already exists
            $cnb_cloud_domain   = CnbAppRemote::cnb_remote_get_wp_domain();
            $cnb_cloud_domains  = CnbAppRemote::cnb_remote_get_domains();
            $cnb_clean_site_url = $cnbAppRemote->cnb_clean_site_url();
            CnbDomain::setSaneDefault($cnb_cloud_domain);
        }
    }

    wp_enqueue_script(CNB_SLUG . '-settings');
    wp_enqueue_script(CNB_SLUG . '-timezone-picker-fix');

    do_action('cnb_header');
    $show_advanced_view_only = array_key_exists('advanced_view', $cnb_options) && $cnb_options['advanced_view'] === 1;
    $use_cloud = is_use_cloud($cnb_options);

    $cloud_successful = $cnb_options['status'] === 'cloud' && isset($cnb_cloud_domain) && !($cnb_cloud_domain instanceof WP_Error);
    if ($cloud_successful) { ?>
        <script>
            jQuery(() => {
                const counter = jQuery("#cnb-nav-counter")
                if (counter.length && counter.text() === '!') {
                    counter.hide();
                }
            });
        </script>
    <?php
        cnb_warn_about_timezone($cnb_cloud_domain);
    } ?>
    <div class="cnb-two-column-section">
      <div class="cnb-body-column">
        <div class="cnb-body-content">

    <h2 class="nav-tab-wrapper">
        <a data-tab-name="basic_options" href="<?php echo cnb_settings_create_tab_url('basic_options') ?>"
           class="nav-tab <?php echo cnb_is_active_tab('basic_options') ?>">General</a>
        <?php if ($use_cloud) { ?>
            <a data-tab-name="account_options" href="<?php echo cnb_settings_create_tab_url('account_options') ?>"
               class="nav-tab <?php echo cnb_is_active_tab('account_options') ?>">Account</a>
            <a data-tab-name="advanced_options" href="<?php echo cnb_settings_create_tab_url('advanced_options') ?>"
               class="nav-tab <?php echo cnb_is_active_tab('advanced_options') ?>">Advanced</a>
        <?php } ?>
    </h2>
    <form method="post" action="<?php echo esc_url( admin_url('options.php') ); ?>" class="cnb-container">
        <?php settings_fields('cnb_options'); ?>
        <table data-tab-name="basic_options" class="form-table <?php echo cnb_is_active_tab('basic_options'); ?>">
            <tr><th colspan="2"></th></tr>
            <tr>
                <th scope="row">
                    Plugin type
                    <?php if ($cnb_options['cloud_enabled'] == 0) { ?>
                    <a href="<?php echo cnb_legacy_upgrade_page(); ?>" class="cnb-nounderscore">
                        <span class="dashicons dashicons-editor-help"></span>
                    </a>
                    <?php } ?>
                </th>
                <td>
                    <div class="cnb-radio-item">
                        <input type="radio" name="cnb[cloud_enabled]" id="cnb_cloud_disabled" value="0" <?php checked('0', $cnb_options['cloud_enabled']); ?>>
                        <label for="cnb_cloud_disabled">Normal</label>

                    </div>
                    <div class="cnb-radio-item">
                        <input type="radio" name="cnb[cloud_enabled]" id="cnb_cloud_enabled" value="1" <?php checked('1', $cnb_options['cloud_enabled']); ?>>
                        <label for="cnb_cloud_enabled">Premium</label>

                        <?php if ($cnb_options['cloud_enabled'] == 0) { ?>
                          <p class="description">Paid and free options. <a href="<?php echo cnb_legacy_upgrade_page(); ?>">Learn more</a></p>
                        <?php } ?>

                        <?php if ($cnb_options['cloud_enabled'] == 1 && isset($cnb_cloud_domain) && !is_wp_error($cnb_cloud_domain) && $cnb_cloud_domain->type !== 'PRO') { ?>
                          <p class="description">Paid and free options. <a href="<?php echo get_cnb_domain_upgrade($cnb_cloud_domain); ?>">Learn more</a></p>
                        <?php } ?>
                    </div>
                </td>
            </tr>
            <?php if ($cnb_options['status'] !== 'cloud') { ?>
            <tr>
                <th colspan="2"><h2>Tracking</h2></th>
            </tr>
            <?php
            cnb_admin_page_leagcy_edit_render_tracking();
            cnb_admin_page_leagcy_edit_render_conversions();
            ?>
            <tr>
                <th colspan="2"><h2>Button display</h2></th>
            </tr>
            <?php
            cnb_admin_page_leagcy_edit_render_zoom();
            cnb_admin_page_leagcy_edit_render_zindex();

            if($cnb_options['classic'] == 1) { ?>
            <tr class="classic">
                <th scope="row">Classic button: <a href="https://callnowbutton.com/new-button-design/<?php cnb_utm_params("question-mark", "new-button-design"); ?>" target="_blank" class="cnb-nounderscore">
                        <span class="dashicons dashicons-editor-help"></span>
                    </a></th>
                <td>
                    <input type="hidden" name="cnb[classic]" value="0" />
                    <input id="classic" name="cnb[classic]" type="checkbox" value="1" <?php checked('1', $cnb_options['classic']); ?> /> <label title="Enable" for="classic">Active</label>
                </td>
            </tr>
            <?php
            }
        }
        if($cloud_successful) {
            cnb_admin_page_domain_edit_render_form_plan_details($cnb_cloud_domain);
            cnb_admin_page_domain_edit_render_form_tracking($cnb_cloud_domain);
            cnb_admin_page_domain_edit_render_form_button_display($cnb_cloud_domain);
        } ?>
        </table>
        <?php if ($cnb_options['status'] === 'cloud') { ?>
        <table data-tab-name="account_options" class="form-table <?php echo cnb_is_active_tab('account_options'); ?>">
            <tr><th colspan="2"></th></tr>
            <tr>
                <th scope="row">API key</th>
                <td>
                    <?php if (is_wp_error($cnb_user) || $show_advanced_view_only) { ?>
                        <label>
                            <input type="text" class="regular-text" name="cnb[api_key]" id="cnb_api_key" placeholder="e.g. b52c3f83-38dc-4493-bc90-642da5be7e39"/>
                        </label>
                        <p class="description">Get your API key at <a href="<?php echo CNB_WEBSITE?>"><?php echo CNB_WEBSITE?></a></p>
                    <?php } ?>
                    <?php if (is_wp_error($cnb_user) && !empty($cnb_options['api_key'])) { ?>
                        <p><span class="dashicons dashicons-warning"></span> There is an API key, but it seems to be invalid or outdated.</p>
                        <p class="description">Clicking "Disconnect account" will drop the API key and disconnect the plugin from your account. You will lose access to your buttons and Premium functionality until you reconnect with a callnowbutton.com account.
                            <br>
                            <input type="button" name="cnb_api_key_delete" id="cnb_api_key_delete" class="button button-link" value="<?php _e('Disconnect account') ?>" onclick="return cnb_delete_apikey();">
                        </p>
                    <?php } ?>
                    <?php if (!is_wp_error($cnb_user) && isset($cnb_options['api_key'])) { ?>
                        <p><strong><span class="dashicons dashicons-saved"></span>Success!</strong> <Br>The plugin is connected to your callnowbutton.com account.</p>
                        <p class="description">Clicking "Disconnect account" will drop the API key and disconnect the plugin from your account. You will lose access to your buttons and Premium functionality until you reconnect with a callnowbutton.com account.
                            <br>
                            <input type="button" name="cnb_api_key_delete" id="cnb_api_key_delete" class="button button-link" value="<?php _e('Disconnect account') ?>" onclick="return cnb_delete_apikey();">
                        </p>
                        <input type="hidden" name="cnb[api_key]" id="cnb_api_key" value="delete_me" disabled="disabled" />
                    <?php }?>
                </td>
            </tr>
             <?php if ($cnb_user !== null && !$cnb_user instanceof WP_Error) { ?>
                 <tr>
                    <th scope="row">Account owner</th>
                    <td>
                        <?php echo esc_html($cnb_user->name) ?>
                        <?php
                            if ($cnb_user->email !== $cnb_user->name) { echo esc_html(' (' . $cnb_user->email . ')');
                        } ?>
                    </td>
                </tr>
                 <tr>
                    <th scope="row">Account ID</th>
                    <td><code><?php echo esc_html($cnb_user->id) ?></code></td>
                </tr>
            <?php } ?>
        </table>
        <?php } ?>
        <table data-tab-name="advanced_options" class="form-table <?php echo cnb_is_active_tab('advanced_options'); ?>">
            <?php if(isset($cnb_cloud_domain) && !($cnb_cloud_domain instanceof WP_Error) && $cnb_options['status'] === 'cloud') {
                ?>
                <tr>
                    <th colspan="2"><h2>Domain settings</h2></th>
                </tr>
                <?php
                cnb_admin_page_domain_edit_render_form_advanced($cnb_cloud_domain, false);
            } ?>
            <tr class="when-cloud-enabled cnb_advanced_view">
                <th colspan="2"><h2>For power users</h2></th>
            </tr>
            <tr class="when-cloud-enabled cnb_advanced_view">
                <th><label for="cnb-advanced-view">Advanced view</label></th>
                <td>
                  <input type="hidden" name="cnb[advanced_view]" value="0" />
                  <input id="cnb-advanced-view" class="cnb_toggle_checkbox" type="checkbox" name="cnb[advanced_view]" value="1" <?php checked('1', $cnb_options['advanced_view']); ?> />
                  <label for="cnb-advanced-view" class="cnb_toggle_label">Toggle</label>
                  <span data-cnb_toggle_state_label="cnb-advanced-view" class="cnb_toggle_state cnb_toggle_false">Disabled</span>
                  <span data-cnb_toggle_state_label="cnb-advanced-view" class="cnb_toggle_state cnb_toggle_true">Enabled</span>
                  <p class="description">For power users only.</p>
                </td>
            </tr>
            <?php if ($cnb_options['status'] === 'cloud') { ?>
                <tr class="cnb_advanced_view">
                    <th><label for="cnb-show-traces">Show traces</label></th>
                    <td>
                      <input type="hidden" name="cnb[footer_show_traces]" value="0" />
                      <input id="cnb-show-traces" class="cnb_toggle_checkbox" type="checkbox" name="cnb[footer_show_traces]" value="1" <?php checked('1', $cnb_options['footer_show_traces']); ?> />
                        <label for="cnb-show-traces" class="cnb_toggle_label">Toggle</label>
                        <span data-cnb_toggle_state_label="cnb-show-traces" class="cnb_toggle_state cnb_toggle_false">Disabled</span>
                        <span data-cnb_toggle_state_label="cnb-show-traces" class="cnb_toggle_state cnb_toggle_true">Enabled</span>
                      <p class="description">Display API calls and timings in the footer.</p>
                    </td>
                </tr>
                <?php if (!($cnb_user instanceof WP_Error) && isset($cnb_cloud_domain) && $cnb_options['status'] === 'cloud') { ?>
                    <tr class="when-cloud-enabled cnb_advanced_view">
                        <th scope="row"><label for="cnb[cloud_use_id]">Domain</label></th>
                        <td>
                            <div>
                                <p>Your domain: <strong><?php echo esc_html($cnb_clean_site_url) ?></strong></p>
                                <?php if ($cnb_cloud_domain instanceof WP_Error) {
                                    CnbAdminNotices::get_instance()->warning('Almost there! Create your domain using the button at the top of this page.')
                                    ?>
                                <?php } else { ?>
                                    <p class="description">Your domain <strong><?php echo esc_html($cnb_cloud_domain->name) ?></strong> is connected with ID <code><?php echo esc_html($cnb_cloud_domain->id) ?></code></p>
                                <?php }?>
                                <?php if (isset($cnb_options['cloud_use_id'])) { ?>
                                <label><select name="cnb[cloud_use_id]" id="cnb[cloud_use_id]">

                                    <optgroup label="Account-wide">
                                        <option
                                                value="<?php echo esc_attr($cnb_user->id) ?>"
                                            <?php selected($cnb_user->id, $cnb_options['cloud_use_id']) ?>
                                        >
                                            Let the button decide
                                        </option>
                                    </optgroup>
                                    <optgroup label="Specific domain">
                                        <?php foreach ($cnb_cloud_domains as $domain) { ?>
                                            <option
                                                    value="<?php echo esc_attr($domain->id) ?>"
                                                <?php selected($domain->id, $cnb_options['cloud_use_id']) ?>
                                            >
                                                <?php echo esc_html($domain->name) ?>
                                            </option>
                                        <?php } ?>
                                    </optgroup>
                                </select></label>
                                <p class="description">The current value is <code><?php echo esc_html($cnb_options['cloud_use_id']) ?></code></p>
                                <?php } ?>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
            <tr class="when-cloud-enabled cnb_advanced_view">
                <th><label for="cnb-all-domains">Show all buttons</label></th>
                <td>
                  <input type="hidden" name="cnb[show_all_buttons_for_domain]" value="0" />
                  <input id="cnb-all-domains" class="cnb_toggle_checkbox" type="checkbox" name="cnb[show_all_buttons_for_domain]" value="1" <?php checked('1', $cnb_options['show_all_buttons_for_domain']); ?> />
                    <label for="cnb-all-domains" class="cnb_toggle_label">Toggle</label>
                    <span data-cnb_toggle_state_label="cnb-all-domains" class="cnb_toggle_state cnb_toggle_false">Disabled</span>
                    <span data-cnb_toggle_state_label="cnb-all-domains" class="cnb_toggle_state cnb_toggle_true">Enabled</span>
                  <p class="description">When checked, the "All Buttons" overview shows all buttons for this account, not just for the current domain.</p>
                </td>
            </tr>
            <tr class="when-cloud-enabled cnb_advanced_view">
                <th><label for="cnb[api_base]">API endpoint</label></th>
                <td><label>
                        <input type="text" id="cnb[api_base]" name="cnb[api_base]" class="regular-text"
                               value="<?php echo CnbAppRemote::cnb_get_api_base() ?>" />
                    </label>
                    <p class="description">The API endpoint to use to communicate with the CallNowButton Cloud service.<br />
                        <strong>Do not change this unless you know what you're doing!</strong>
                    </p>
                </td>
            </tr>
            <tr class="cnb_advanced_view">
              <th><label for="cnb-api-caching">API caching</label></th>
                <td>
                  <input type="hidden" name="cnb[api_caching]" value="0" />
                  <input id="cnb-api-caching" class="cnb_toggle_checkbox" type="checkbox" name="cnb[api_caching]" value="1" <?php checked('1', $cnb_options['api_caching']); ?> />
                    <label for="cnb-api-caching" class="cnb_toggle_label">Toggle</label>
                    <span data-cnb_toggle_state_label="cnb-api-caching" class="cnb_toggle_state cnb_toggle_false">Disabled</span>
                    <span data-cnb_toggle_state_label="cnb-api-caching" class="cnb_toggle_state cnb_toggle_true">Enabled</span>
                  <p class="description">Cache API requests (using WordPress transients)</p>
                </td>
              </tr>
            <?php } // end of cloud check ?>
        </table>
        <input type="hidden" name="cnb[version]" value="<?php echo CNB_VERSION; ?>"/>
        <?php submit_button(); ?>
    </form>
    </div>
  </div>
  <div class="cnb-postbox-container cnb-side-column">
    <?php if(!$use_cloud) {
      cnb_promobox(
        'blue',
        'More buttons!',
        'Switch to Premium to enable lots of buttons. Coupled with advanced page selection options you can get really creative.</p>
        <p>If you need more phone numbers on a single page, then the Multibutton&trade; and the Buttonbar&trade; give you exactly what you need.</p>',
        'cloud',
        '<strong>Give it a try!</strong>',
        'Learn more',
          cnb_legacy_upgrade_page()
      );
      cnb_promobox(
        'grey',
        'Go Premium for free',
        'Premium comes in 2 versions: <em>Free</em> and <em>Paid</em>.</p>
        <p>Both plans give you access to <strong>all features</strong>.</p>
        <p>The only differences are that the Free version shows <em>Powered by Call Now Button</em> next to your buttons and there\'s a monthly pageviews limit of 20k.</p>',
        'info-outline',
        '',
        'Try Premium',
          cnb_legacy_upgrade_page()
      );
    } ?>
    <?php if($use_cloud && isset($cnb_cloud_domain) && !is_wp_error($cnb_cloud_domain) && $cnb_cloud_domain->type !== 'PRO') {
        cnb_promobox(
          'blue',
          'Introduction offer',
          '<p>Remove the <em>powered by</em> branding from your buttons!</p>
          <p>Benefit from our introductory offer and enjoy unlimited access to all features without our branding.</p>',
          'flag',
          '<strong>&euro;2.49/$2.99 per month</strong>',
          'Upgrade',
            get_cnb_domain_upgrade($cnb_cloud_domain)
    );
      } ?>
  </div>
</div>

    <?php do_action('cnb_footer'); ?>
<?php }
