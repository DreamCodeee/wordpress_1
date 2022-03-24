<?php

require_once dirname( __FILE__ ) . '/../../utils/notices.php';

function cnb_get_active_tab_name() {
    // using filter_var instead of filter_input so we can do some "just in time" rewriting of the tab variable if needed
    return isset($_GET['tab']) ? filter_var( $_GET['tab'], FILTER_SANITIZE_STRING ) : 'basic_options';
}

function cnb_is_active_tab($tab_name) {
    $active_tab = cnb_get_active_tab_name();
    return $active_tab === $tab_name ? 'nav-tab-active' : '';
}

function set_changelog_version() {
    $cnb_options = get_option('cnb');
    $updated_options = array_merge(
        $cnb_options,
        array(
            'changelog_version' => CNB_VERSION
        )
    );
    update_option('cnb', $updated_options);
}

function get_changelog_version($cnb_options) {
    if (!$cnb_options) {
        return CNB_VERSION;
    }

    if (!key_exists('changelog_version', $cnb_options)) {
        // Get 1 version behind, so new users always get the latest
        $changelog = cnb_get_changelog();
        $keys = array_keys($changelog);
        return $keys[1];
    }

    return $cnb_options['changelog_version'];
}

function has_changelog($cnb_options) {
    // These is a message...
    $changelog_version = get_changelog_version($cnb_options);
    $changelog = cnb_get_changelog();
    $changelog_message = cnb_get_changelog_message($changelog, $changelog_version);

    // ... and the notice has not been dismissed yet
    $dismiss_name = CnbAdminNotices::get_instance()->get_dismiss_option_name(cnb_get_upgrade_notice_dismiss_name());
    $dismissed = CnbAdminNotices::get_instance()->is_dismissed($dismiss_name);
    return !empty($changelog_message) && !$dismissed;
}

function cnb_get_changelog() {
    return array(
        '1.0.6' => array(
          '💬 SMS/Text support in Premium',
          '⏱️ More intuitive button scheduler in Premium',
        ),
        '1.0.4' => array(
          'Live button preview for Premium',
          'Easy premium activation via email',
          'Switch between tabs while editing your button',
        ),
        '1.0.0' => '🎉Introducing Call Now Button Premium!🎉',
        '0.5.0' => 'Better button creation flow, UI improvements, small fixes',
        '0.4.7' => 'Small UI improvements',
        '0.4.2' => 'Button styling adjustments, security improvements',
        '0.4.0' => array(
            'Text bubbles for standard buttons',
            'Set the icon color',
            'Google Ads conversion tracking',
            'Tabbed admin interface',
            '6 additional button locations, small button design changes',
            'Added support articles for (nearly) all settings',
            'Control visibility on front page',
            'Plus a bunch of smaller fixes. Enjoy!'),
        '0.3.6' => 'Small validation fixes and zoom now controls icon size in full width buttons.',
        '0.3.5' => 'Small JS fix',
        '0.3.4' => 'Option to resize your button and change where it sits in the stack order (z-index).',
        '0.3.3' => 'Some small improvements.',
        '0.3.2' => 'Option to hide icon in text button, small bug fixes.',
        '0.3.1' => 'You can now add text to your button and it\'s possible to switch between including and excluding specific pages.',
        '0.3.0' => 'Option to add text to your button.',
        '0.2.1' => 'Some small fixes',
        '0.2.0' => 'The Call Now Button has a new look!'
    );
}

/**
 * Return an array of all ButtonTypes
 *
 * @return string[] array of ButtonTypes to their nice names
 */
function cnb_get_button_types() {
    return array(
        'SINGLE' => 'Single button',
        'FULL' => 'Buttonbar',
        'MULTI' => 'Multibutton',
    );
}

/**
 * Return an array of all ActionTypes
 *
 * Note(s):
 * - This is NOT in alphabetical order, but rather in order of
 *   what feels more likely to be choosen
 * - HOURS is missing, since that is not implemented yet
 *
 * @return string[] array of ActionType to their nice names
 */
function cnb_get_action_types() {
    return array(
        'PHONE' => 'Phone',
        'EMAIL' => 'Email',
        'ANCHOR' => 'Anchor',
        'LINK' => 'Link',
        'MAP' => 'Google Maps',
        'WHATSAPP' => 'Whatsapp',
        'SMS' => 'SMS',
    );
}

function cnb_get_condition_filter_types() {
    return array(
        'INCLUDE' => 'Include',
        'EXCLUDE' => 'Exclude',
    );
}

function cnb_get_condition_match_types() {
    return array(
        'SIMPLE' => 'Page path is:',
        'EXACT' => 'Page URL is:',
        'SUBSTRING' => 'Page URL contains:',
        'REGEX' => 'Page URL matches RegEx:',
    );
}

/**
 * @param array $original Array of "daysOfWeek", index 0 == Monday, values should be strings and contain "true"
 * in order to be evaulated correctly.
 *
 * @return array cleaned up array with proper booleans for the days.
 */
function cnb_create_days_of_week_array($original) {
    // If original does not exist, leave it as it is
    if ($original === null || !is_array($original)) {
        return $original;
    }

    // Default everything is NOT selected, then we enable only those days that are passed in via $original
    $result = array(false, false, false, false, false, false, false);
    foreach ($result as $day_of_week_index => $day_of_week) {
        $day_of_week_is_enabled = isset($original[$day_of_week_index]) && $original[$day_of_week_index] === "true";
        $result[$day_of_week_index] = $day_of_week_is_enabled;
    }
    return $result;
}

/**
 * <p>Echo the promobox.</p>
 * <p>The CTA block is optional and displays only when there's a link provided or $cta_button_text = 'none'.</p>
 * <p>Defaut CTA text is "Let's go". Default <code>$icon</code> is flag (value should be a dashicon name)</p>
 *
 * <p><strong>NOTE: all values are presumed to be already escaped!</strong></p>
 *
 * @param $color
 * @param $headline
 * @param $body
 * @param $icon
 * @param $cta_pretext
 * @param $cta_button_text
 * @param $cta_button_link
 * @param $cta_footer_notice
 *
 * @return null It <code>echo</code>s html output of the promobox
 */
 function cnb_promobox($color, $headline, $body, $icon = 'flag', $cta_pretext = null, $cta_button_text = 'Let\'s go', $cta_button_link = null, $cta_footer_notice = null) {
    $output = '
        <div id="cnb_upgrade_box" class="cnb-promobox">
            <div class="cnb-promobox-header cnb-promobox-header-'.$color.'">
                <span class="dashicons dashicons-'.$icon.'"></span>
                <h2 class="hndle">'.
                    $headline
                .'</h2>
            </div>
            <div class="inside">
                <div class="cnb-promobox-copy">
                    <div class="cnb_promobox_item">'.
                        $body
                    .'</div>
                    <div class="clear"></div>';
    if(!is_null($cta_button_link) || $cta_button_text == 'none') {
        $output .= '
                    <div class="cnb-promobox-action">
                        <div class="cnb-promobox-action-left">'.
                            $cta_pretext
                        .'</div>';
        if($cta_button_text != 'none' && $cta_button_link != 'disabled') {
            $output .= '
                        <div class="cnb-promobox-action-right">
                            <a class="button button-primary button-large" href="'.$cta_button_link.'">'.$cta_button_text.'</a>
                        </div>';
        } elseif($cta_button_link == 'disabled') {
            $output .= '
                        <div class="cnb-promobox-action-right">
                            <button class="button button-primary button-large" disabled>'.$cta_button_text.'</a>
                        </div>';
                      }
        $output .= '
                        <div class="clear"></div>';
        if(!is_null($cta_footer_notice)) {
          $output .= '<div class="nonessential" style="padding-top: 5px;">'.$cta_footer_notice.'</div>';
        }
        $output .= '
                    </div>
                    ';
    }
    $output .= '
                </div>
            </div>
        </div>
    ';
    echo $output;
    return null;
}

/**
 * Returns the url for the Upgrade to cloud page
 *
 * @return string upgrade page url
 */
function cnb_legacy_upgrade_page() {
    $url      = admin_url('admin.php');
    $new_link = add_query_arg( 'page', 'call-now-button-upgrade', $url );
    return esc_url_raw($new_link);
}
/**
 * Returns the url for the Settings page
 *
 * @return string admin page url
 */
function cnb_settings_url() {
    $url = admin_url('admin.php');
    $new_link = add_query_arg('page', 'call-now-button-settings', $url);
    return esc_url( $new_link );
}

/**
 * The equivalent of the JS function `set_cnb_wordpress_upgrade_link`
 *
 * @return string URL of the upgrade URL (path parameters are already escaped)
 */
function get_cnb_wordpress_upgrade_link() {
    $email = esc_attr(get_bloginfo('admin_email'));
    $action = esc_url( admin_url('admin.php') );

	return 'https://www.callnowbutton.com/wordpress'
        . '?e=' . $email
        . '&a=' . $action;
}
