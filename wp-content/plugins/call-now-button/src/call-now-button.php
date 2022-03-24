<?php

require_once dirname( __FILE__ ) . '/renderers/render.php';
require_once dirname( __FILE__ ) . '/utils/utils.php';
require_once dirname( __FILE__ ) . '/admin/admin-ajax.php';
require_once dirname( __FILE__ ) . '/utils/CnbAdminNotices.class.php';
require_once dirname( __FILE__ ) . '/admin/partials/admin-header.php';

// Only include the WP_CLI suite when it is available
if ( class_exists( 'WP_CLI' ) && class_exists( 'WP_CLI_Command' ) ) {
    require_once dirname( __FILE__ ) . '/cli/CNB_CLI.class.php';
}

/**
 * There are a few global used throughout the plugin.
 *
 * All are named `cnb_*` so not to collide with others.
 *
 * This is setup early via the `plugins_loaded` hook
 *
 * @return void
 */
function cnb_setup_globals() {
    global $cnb_settings, $cnb_options, $cnb_cloud_hosting, $cloud_use_id;
    $cnb_settings      = array();
    $cnb_options       = array();
    $cnb_cloud_hosting = false;
    $cloud_use_id      = 0;
    cnb_reset_options();
}

add_action('plugins_loaded', 'cnb_setup_globals');

/**
 * Used by cnb_register_admin_page
 *
 * This adds all the CSS to the renderable pages (that are registered in the nav)
 */
function cnb_admin_styling() {
    wp_enqueue_style(CNB_SLUG . '-styling');
    wp_enqueue_style('wp-components');
}

function cnb_admin_button_overview() {
    require_once dirname( __FILE__ ) . '/admin/button-overview.php';
    cnb_admin_button_overview_render();
}

function cnb_admin_page_domain_overview() {
    require_once dirname( __FILE__ ) . '/admin/domain-overview.php';
    cnb_admin_page_domain_overview_render();
}

function cnb_admin_page_action_overview() {
    require_once dirname( __FILE__ ) . '/admin/action-overview.php';
    cnb_admin_page_action_overview_render();
}

function cnb_admin_page_condition_overview() {
    require_once dirname( __FILE__ ) . '/admin/condition-overview.php';
    cnb_admin_page_condition_overview_render();
}

function cnb_admin_page_apikey_overview() {
    require_once dirname( __FILE__ ) . '/admin/apikey-overview.php';
    cnb_admin_page_apikey_overview_render();
}

function cnb_admin_settings() {
    require_once dirname( __FILE__ ) . '/admin/settings.php';
    cnb_admin_settings_page();
}

function cnb_admin_page_legacy_edit() {
    require_once dirname( __FILE__ ) . '/admin/legacy-edit.php';
    cnb_admin_page_legacy_edit_render();
}

function cnb_admin_page_legacy_upgrade() {
    require_once dirname( __FILE__ ) . '/admin/legacy-upgrade.php';
    cnb_admin_page_legacy_upgrade_render();
}

function cnb_admin_page_profile_edit() {
    require_once dirname( __FILE__ ) . '/admin/settings-profile.php';
    cnb_admin_page_profile_edit_render();
}

/**
 * Adds the plugin to the options menu
 */
function cnb_register_admin_pages() {
    global $cnb_cloud_hosting, $cnb_options, $wp_version;

    $plugin_title  = apply_filters( 'cnb_plugin_title', CNB_NAME );

    $menu_page_function = $cnb_cloud_hosting ? 'cnb_admin_button_overview' : 'cnb_admin_page_legacy_edit';

    $counter = 0;
    $menu_page_title = 'Call Now Button<span class="awaiting-mod" id="cnb-nav-counter" style="display: none">'.$counter.'</span>';
    $menu_page_position = $cnb_cloud_hosting ? 30 : 66;

    $has_changelog = has_changelog($cnb_options);
    if ($has_changelog) $counter++;

    $has_welcome_banner = cnb_show_welcome_banner() && !$cnb_cloud_hosting;
    if ($has_welcome_banner) $counter++;

    // Detect errors (specific, - Premium enabled, but API key is not present yet)
    if ($cnb_cloud_hosting && !array_key_exists('api_key', $cnb_options)) {
        $counter = '!';
    }

    if ($counter) {
        $menu_page_title =  'Call Now Bu...<span class="awaiting-mod" id="cnb-nav-counter">'.$counter.'</span>';
    }


    // Oldest WordPress only has "smartphone", no "phone" (this is added in a later version)
    $icon_url = version_compare($wp_version, '5.5.0', '<') ? 'dashicons-smartphone' : 'dashicons-phone';
    $menu_page = add_menu_page(
        __( 'Call Now Button - Overview', CNB_NAME ),
        $menu_page_title,
        'manage_options',
        CNB_SLUG,
        $menu_page_function,
        $icon_url,
        $menu_page_position
    );
    add_action('admin_print_styles-' . $menu_page, 'cnb_admin_styling');

    if ($cnb_cloud_hosting) {
        // Button overview
        $button_overview = add_submenu_page( CNB_SLUG, $plugin_title, 'All buttons', 'manage_options', CNB_SLUG, 'cnb_admin_button_overview' );
        add_action('admin_print_styles-' . $button_overview, 'cnb_admin_styling');

        add_submenu_page( CNB_SLUG, $plugin_title, 'Add New', 'manage_options', CNB_SLUG . '&action=new', 'cnb_admin_button_overview' );

        if ($cnb_options['advanced_view'] === 1) {
            // Domain overview
            $domain_overview = add_submenu_page(CNB_SLUG, $plugin_title, 'Domains', 'manage_options', CNB_SLUG . '-domains', 'cnb_admin_page_domain_overview');
            add_action('admin_print_styles-' . $domain_overview, 'cnb_admin_styling');

            // Action overview
            $action_overview = add_submenu_page(CNB_SLUG, $plugin_title, 'Actions', 'manage_options', CNB_SLUG . '-actions', 'cnb_admin_page_action_overview');
            add_action('admin_print_styles-' . $action_overview, 'cnb_admin_styling');

            // Condition overview
            $condition_overview = add_submenu_page(CNB_SLUG, $plugin_title, 'Conditions', 'manage_options', CNB_SLUG . '-conditions', 'cnb_admin_page_condition_overview');
            add_action('admin_print_styles-' . $condition_overview, 'cnb_admin_styling');

            // Apikey overview
            $apikey_overview = add_submenu_page(CNB_SLUG, $plugin_title, 'API Keys', 'manage_options', CNB_SLUG . '-apikeys', 'cnb_admin_page_apikey_overview');
            add_action('admin_print_styles-' . $apikey_overview, 'cnb_admin_styling');

            // Profile edit
            $profile = add_submenu_page(CNB_SLUG, $plugin_title, 'Profile', 'manage_options', CNB_SLUG . '-profile', 'cnb_admin_page_profile_edit');
            add_action('admin_print_styles-' . $profile, 'cnb_admin_styling');
        } else {
            // Fake out Action overview
            if (isset($_GET['page']) && $_GET['page'] === 'call-now-button-actions' && $_GET['action']) {
                $action_overview = add_submenu_page(CNB_SLUG, $plugin_title, 'Edit action', 'manage_options', CNB_SLUG . '-actions', 'cnb_admin_page_action_overview');
                add_action('admin_print_styles-' . $action_overview, 'cnb_admin_styling');
            }
            // Fake out Domain upgrade page
            if (isset($_GET['page']) && $_GET['page'] === 'call-now-button-domains' && $_GET['action'] === 'upgrade') {
                $domain_overview = add_submenu_page(CNB_SLUG, $plugin_title, 'Upgrade domain', 'manage_options', CNB_SLUG . '-domains', 'cnb_admin_page_domain_overview');
                add_action('admin_print_styles-' . $domain_overview, 'cnb_admin_styling');
            }
        }
    } else {
        // Legacy edit
        $legacy_edit = add_submenu_page( CNB_SLUG, $plugin_title, 'My button', 'manage_options', CNB_SLUG, 'cnb_admin_page_legacy_edit' );
        add_action('admin_print_styles-' . $legacy_edit, 'cnb_admin_styling');

        $legacy_upgrade = add_submenu_page( CNB_SLUG, $plugin_title, 'Unlock features', 'manage_options', CNB_SLUG .'-upgrade', 'cnb_admin_page_legacy_upgrade' );
        add_action('admin_print_styles-' . $legacy_upgrade, 'cnb_admin_styling');
    }

    // Settings pages
    $settings = add_submenu_page(CNB_SLUG, $plugin_title, 'Settings', 'manage_options', CNB_SLUG.'-settings', 'cnb_admin_settings');
    add_action('admin_print_styles-' . $settings, 'cnb_admin_styling');
}

add_action('admin_menu', 'cnb_register_admin_pages');

function cnb_enqueue_color_picker() {
    wp_enqueue_style('wp-color-picker');
}

/**
 * Used for the modal in Button edit -> Actions edit
 */
function cnb_enqueue_script_dialog() {
    wp_enqueue_script('jquery-ui-core');
    wp_enqueue_script('jquery-ui-dialog');
    wp_enqueue_style('wp-jquery-ui-dialog');
}

function cnb_enqueue_scripts() {
    wp_enqueue_script(CNB_SLUG . '-call-now-button');
    wp_enqueue_script(CNB_SLUG . '-dismiss');
}

function cnb_plugin_meta($links, $file) {
    global $cnb_cloud_hosting;
    if ($file == CNB_BASENAME) {

        $url = admin_url('admin.php');

        $button_link =
            add_query_arg(
                array(
                    'page' => 'call-now-button'),
                $url);
        $button_url = esc_url($button_link);

        $settings_link =
            add_query_arg(
                array(
                    'page' => 'call-now-button-settings'),
                $url);
        $settings_url = esc_url($settings_link);

        $link_name = $cnb_cloud_hosting ? __('All buttons') : __('My button');
        $cnb_new_links = array(
            sprintf( '<a href="%s">%s</a>', $button_url, $link_name),
            sprintf( '<a href="%s">%s</a>', $settings_url, __('Settings')),
            sprintf( '<a href="%s">%s</a>', CNB_SUPPORT, __('Support'))
        );
        array_push(
            $links,
            $cnb_new_links[0],
            $cnb_new_links[1],
            $cnb_new_links[2]
        );
    }
    return $links;
}

add_filter('plugin_row_meta', 'cnb_plugin_meta', 10, 2);

function cnb_plugin_add_action_link($links) {
    global $cnb_cloud_hosting;

    $link_name = $cnb_cloud_hosting ? 'All buttons' : 'My button';
    $url = admin_url( 'admin.php' );
    $button_link =
        add_query_arg(
            array(
                'page' => 'call-now-button'
            ),
            $url );
    $button_url  = esc_url( $button_link );
    $button = sprintf( '<a href="%s">%s</a>', $button_url, __( $link_name ) );
    array_unshift($links, $button);

    if (!$cnb_cloud_hosting) {
        $link_name    = 'Get Premium';
        $upgrade_link =
            add_query_arg(
                array(
                    'page' => 'call-now-button-upgrade'
                ),
                $url );
        $upgrade_url  = esc_url( $upgrade_link );
        $upgrade = sprintf( '<a style="font-weight: bold;" href="%s">%s</a>', $upgrade_url, __( $link_name ) );
        array_unshift($links, $upgrade);
    }

    return $links;
}

add_filter('plugin_action_links_' . CNB_BASENAME, 'cnb_plugin_add_action_link');

/**
 * @param $input array The options for <code>cnb</code>
 *
 * @return array The adjusted options array for Call Now Button
 * @noinspection PhpUnused (it is used via cnb_options_init() -> register_setting())
 */
function cnb_options_validate($input) {
    require_once dirname( __FILE__ ) . '/admin/settings.php';
    return cnb_settings_options_validate($input);
}

function cnb_options_init() {
    // This ensures that we can validate and change/manipulate the "cnb" options before saving
    register_setting( 'cnb_options', 'cnb', 'cnb_options_validate' );

    wp_register_style(
        CNB_SLUG . '-styling',
        plugins_url('../resources/style/call-now-button.css', __FILE__),
        false,
        CNB_VERSION );
    // Original: https://code.jquery.com/ui/1.13.0/themes/base/jquery-ui.min.css
    wp_register_style(
        CNB_SLUG . '-jquery-ui',
        plugins_url('../resources/style/jquery-ui.min.css', __FILE__),
        false,
        '1.13.0');
    // Original: https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.12/css/intlTelInput.min.css
    wp_register_style(
        CNB_SLUG . '-intl-tel-input',
        plugins_url('../resources/style/intlTelInput.min.css', __FILE__),
        false,
        '1.13.0');

    wp_register_script(
        CNB_SLUG . '-call-now-button',
        plugins_url('../resources/js/call-now-button.js', __FILE__),
        array('wp-color-picker'),
        CNB_VERSION,
        true);
    wp_register_script(
        CNB_SLUG . '-dismiss',
        plugins_url( '../resources/js/dismiss.js', __FILE__ ),
        array('jquery', CNB_SLUG . '-call-now-button'),
        CNB_VERSION,
        true);
    wp_register_script(
        CNB_SLUG . '-timezone-picker-fix',
        plugins_url( '../resources/js/timezone-picker-fix.js', __FILE__ ),
        array('jquery', CNB_SLUG . '-call-now-button'),
        CNB_VERSION,
        true);
    wp_register_script(
        CNB_SLUG . '-action-type-to-icon-text',
        plugins_url( '../resources/js/action-type-to-icon-text.js', __FILE__ ),
        array('jquery', CNB_SLUG . '-call-now-button'),
        CNB_VERSION,
        true);

    wp_register_script(
        CNB_SLUG . '-form-to-json',
        plugins_url( '../resources/js/form-to-json.js', __FILE__ ),
        array('jquery', CNB_SLUG . '-call-now-button'),
        CNB_VERSION,
        true);
    wp_register_script(
        CNB_SLUG . '-preview',
        plugins_url( '../resources/js/preview.js', __FILE__ ),
        array('jquery', CNB_SLUG . '-call-now-button'),
        CNB_VERSION,
        true);
    wp_register_script(
        CNB_SLUG . '-domain-upgrade',
        plugins_url( '../resources/js/domain-upgrade.js', __FILE__ ),
        array('jquery', CNB_SLUG . '-call-now-button'),
        CNB_VERSION,
        true);
    wp_register_script(
        CNB_SLUG . '-settings',
        plugins_url( '../resources/js/settings.js', __FILE__ ),
        array(CNB_SLUG . '-call-now-button'),
        CNB_VERSION,
        true);
    wp_register_script(
        CNB_SLUG . '-action-edit-scheduler',
        plugins_url( '../resources/js/action-edit-scheduler.js', __FILE__ ),
        array(CNB_SLUG . '-call-now-button'),
        CNB_VERSION,
        true);

    // Special case: since the preview functionality depends on this,
    // and the source is always changing - we include it as external script
    wp_register_script(
        CNB_SLUG . '-client',
        'https://static.callnowbutton.com/js/client.js',
        array(),
        CNB_VERSION,
        true);

    // Original: https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.12/js/intlTelInput.min.js
    wp_register_script(
        CNB_SLUG . '-intl-tel-input',
        plugins_url( '../resources/js/intlTelInput.min.js', __FILE__ ),
        null,
        '17.0.12',
        true);
}

add_action('admin_init', 'cnb_options_init');

/**
 * Called when a Single/Multi/ButtonBar Button is created via POST
 */
function cnb_admin_post_create_button() {
    require_once dirname( __FILE__ ) . '/admin/button-edit.php';
    cnb_admin_create_button();
}
add_action('admin_post_cnb_create_single_button', 'cnb_admin_post_create_button');
add_action('admin_post_cnb_create_multi_button', 'cnb_admin_post_create_button');
add_action('admin_post_cnb_create_full_button', 'cnb_admin_post_create_button');

/**
 * Called when a Single Button is saved via POST
 */
function cnb_admin_post_update_button() {
    require_once dirname( __FILE__ ) . '/admin/button-edit.php';
    cnb_admin_update_button();
}
add_action('admin_post_cnb_update_single_button', 'cnb_admin_post_update_button');
add_action('admin_post_cnb_update_multi_button', 'cnb_admin_post_update_button');
add_action('admin_post_cnb_update_full_button', 'cnb_admin_post_update_button');

/**
 * Called when a Domain is created via POST
 */
function cnb_admin_create_domain() {
    require_once dirname( __FILE__ ) . '/admin/domain-edit.php';
    cnb_admin_page_domain_create_process();
}

add_action('admin_post_cnb_create_domain', 'cnb_admin_create_domain');

/**
 * Called when a Domain is saved via POST
 */
function cnb_admin_update_domain() {
    require_once dirname( __FILE__ ) . '/admin/domain-edit.php';
    cnb_admin_page_domain_edit_process();
}

add_action('admin_post_cnb_update_domain', 'cnb_admin_update_domain');

/**
 * Called when an Action is created via POST
 */
function cnb_admin_create_action() {
    require_once dirname( __FILE__ ) . '/admin/action-edit.php';
    cnb_admin_page_action_create_process();
}

add_action('admin_post_cnb_create_action', 'cnb_admin_create_action');

/**
 * Called when an Action is saved via POST
 */
function cnb_admin_update_action() {
    require_once dirname( __FILE__ ) . '/admin/action-edit.php';
    cnb_admin_page_action_edit_process();
}

add_action('admin_post_cnb_update_action', 'cnb_admin_update_action');

/**
 * Called when a condition is saved via POST
 */
function cnb_admin_create_condition() {
    require_once dirname( __FILE__ ) . '/admin/condition-edit.php';
    cnb_admin_page_condition_create_process();
}

add_action('admin_post_cnb_create_condition', 'cnb_admin_create_condition');

/**
 * Called when a condition is saved via POST
 */
function cnb_admin_update_condition() {
    require_once dirname( __FILE__ ) . '/admin/condition-edit.php';
    cnb_admin_page_condition_edit_process();
}

add_action('admin_post_cnb_update_condition', 'cnb_admin_update_condition');

/**
 * Called when an API key is created via POST
 */
function cnb_admin_create_apikey() {
    require_once dirname( __FILE__ ) . '/admin/apikey-overview.php';
    cnb_admin_page_apikey_create_process();
}

add_action('admin_post_cnb_create_apikey', 'cnb_admin_create_apikey');

/**
 * Called when an API key is created via POST
 */
function cnb_admin_profile_edit() {
    require_once dirname( __FILE__ ) . '/admin/settings-profile.php';
    cnb_admin_profile_edit_process();
}

add_action('admin_post_cnb_profile_edit', 'cnb_admin_profile_edit');

/**
 * Called when the Settings page is migrating from Legacy to the cloud
 */
function cnb_admin_migrate_to_cloud() {
    require_once dirname( __FILE__ ) . '/admin/settings.php';
    cnb_admin_setting_migrate();
}

add_action('admin_post_cnb_create_cloud_domain', 'cnb_admin_migrate_to_cloud');
add_action('admin_post_cnb_migrate_legacy_button', 'cnb_admin_migrate_to_cloud');

add_action('cnb_in_admin_header', 'cnb_admin_header_no_args');
add_action('cnb_header', 'cnb_admin_header');
add_action('cnb_footer', 'cnb_admin_footer');

// This updates the internal version number, called by CnbAdminNotices::action_admin_init
add_action('cnb_update_'.CNB_VERSION, 'cnb_update_version');
