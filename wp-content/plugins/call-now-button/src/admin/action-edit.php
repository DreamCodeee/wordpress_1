<?php

require_once dirname( __FILE__ ) . '/api/CnbAppRemote.php';
require_once dirname( __FILE__ ) . '/api/CnbAdminCloud.php';
require_once dirname( __FILE__ ) . '/partials/admin-functions.php';
require_once dirname( __FILE__ ) . '/partials/admin-header.php';
require_once dirname( __FILE__ ) . '/partials/admin-footer.php';
require_once dirname( __FILE__ ) . '/models/CnbAction.class.php';
require_once dirname( __FILE__ ) . '/../utils/utils.php';
require_once dirname( __FILE__ ) . '/button-edit.php';

function cnb_add_header_action_edit($action) {
    $id = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_STRING );
    $name = 'New Action';
    if ($action && $action->id !== 'new') {
        $actionTypes = cnb_get_action_types();
        $name = $actionTypes[$action->actionType];
        if ($action->actionValue) {
            $name = $action->actionValue;
        }
    }
    if (strlen($id) > 0 && $id === 'new') {
        echo 'Add action';
    } else {
        echo 'Edit action: "' . esc_html($name) . '"';
    }
}

/**
 * This is called to create an Action
 * via `call-now-button.php#cnb_create_action`
 */
function cnb_admin_page_action_create_process() {
    $nonce  = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
    if( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $nonce, 'cnb-action-edit') ) {

        $actions = filter_input(
            INPUT_POST,
            'actions',
            FILTER_SANITIZE_STRING,
            FILTER_REQUIRE_ARRAY | FILTER_FLAG_NO_ENCODE_QUOTES);
        $action_id = filter_input( INPUT_POST, 'action_id', FILTER_SANITIZE_STRING );
        $action = $actions[$action_id];

        // Do the processing
        $cnb_cloud_notifications = array();
        if (isset($action['schedule']['daysOfWeek']) &&
            $action['schedule']['daysOfWeek'] !== null &&
            is_array($action['schedule']['daysOfWeek'])) {
            $action['schedule']['daysOfWeek'] = cnb_create_days_of_week_array($action['schedule']['daysOfWeek']);
        }

        // "Fix" the WHATSAPP values
        if ($action['actionType'] === 'WHATSAPP'
            && isset($action['actionValueWhatsappHidden'])
            && !empty($action['actionValueWhatsappHidden'])) {
            $action['actionValue'] = $action['actionValueWhatsappHidden'];
        }

        // Remove the "display" value
        unset($action['actionValueWhatsapp']);
        unset($action['actionValueWhatsappHidden']);

        $new_action = CnbAdminCloud::cnb_create_action( $cnb_cloud_notifications, $action );
        $new_action_id = $new_action->id;

        $bid = filter_input( INPUT_POST, 'bid', FILTER_SANITIZE_STRING );
        if (!empty($bid)) {
            // Tie this new Action to the provided Button
            $button = CnbAppRemote::cnb_remote_get_button( $bid );
            if (!($button instanceof WP_Error)) {
                $button->actions[] = $new_action_id;
                $button_array = json_decode(json_encode($button), true);
                CnbAdminCloud::cnb_update_button( $cnb_cloud_notifications, $button_array );
            } else {
                // TODO Add error to $cnb_cloud_notifications
            }
        }

        // redirect the user to the appropriate page
        $transient_id = 'cnb-' . wp_generate_uuid4();
        set_transient($transient_id, $cnb_cloud_notifications, HOUR_IN_SECONDS);

        // Create link
        $bid = !empty($_GET['bid']) ? sanitize_text_field($_GET['bid']) : null;
        $url = admin_url('admin.php');

        if (!empty($bid)) {
            $redirect_link =
                add_query_arg(
                    array(
                        'page' => 'call-now-button',
                        'action' => 'edit',
                        'id' => $bid,
                        'tid' => $transient_id,
                    ),
                    $url);
            $redirect_url = esc_url_raw($redirect_link);
            wp_safe_redirect($redirect_url);
            exit;
        } else {
            $redirect_link =
                add_query_arg(
                    array(
                        'page' => 'call-now-button-actions',
                        'action' => 'edit',
                        'id' => $new_action_id,
                        'tid' => $transient_id,
                        'bid' => $bid),
                    $url);
            $redirect_url = esc_url_raw($redirect_link);
            wp_safe_redirect($redirect_url);
            exit;
        }
    }
    else {
        $url = admin_url('admin.php');
        $redirect_link =
            add_query_arg(
                array(
                    'page' => CNB_SLUG
                ),
                $url );
        $redirect_url = esc_url_raw($redirect_link);
        wp_die( __( 'Invalid nonce specified', CNB_NAME), __( 'Error', CNB_NAME), array(
            'response' 	=> 403,
            'back_link' => $redirect_url,
        ) );
    }
}

/**
 * @param $action CnbAction
 *
 * @return array
 */
function cnb_admin_process_action($action) {
    if (isset($action['schedule']['daysOfWeek']) && $action['schedule']['daysOfWeek'] !== null && is_array($action['schedule']['daysOfWeek'])) {
        $action['schedule']['daysOfWeek'] = cnb_create_days_of_week_array($action['schedule']['daysOfWeek']);
    }

    // "Fix" the WHATSAPP values
    if (isset($action['actionType']) && $action['actionType'] === 'WHATSAPP'
        && isset($action['actionValueWhatsappHidden'])
        && !empty($action['actionValueWhatsappHidden'])) {
        $action['actionValue'] = $action['actionValueWhatsappHidden'];
    }

    // Remove the "display" value
    unset($action['actionValueWhatsapp']);
    unset($action['actionValueWhatsappHidden']);

    // Set the correct iconText
    if (isset($action['iconText']) && !empty($action['iconText'])) {
        // Reset the iconText based on type
        $action['iconText'] = cnb_actiontype_to_icontext($action['actionType']);
    }

    return $action;
}
/**
 * This is called to update the action
 * via `call-now-button.php#cnb_update_action`
 */
function cnb_admin_page_action_edit_process() {
    $nonce  = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
    if( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $nonce, 'cnb-action-edit') ) {

        // sanitize the input
        $actions = filter_input(
            INPUT_POST,
            'actions',
            FILTER_SANITIZE_STRING,
            FILTER_REQUIRE_ARRAY | FILTER_FLAG_NO_ENCODE_QUOTES);
        $result = '';
        $cnb_cloud_notifications = array();

        foreach($actions as $action) {
            $processed_action = cnb_admin_process_action($action);
            // do the processing
            $result = CnbAdminCloud::cnb_update_action( $cnb_cloud_notifications, $processed_action );
        }

        // redirect the user to the appropriate page
        $transient_id = 'cnb-' . wp_generate_uuid4();
        set_transient($transient_id, $cnb_cloud_notifications, HOUR_IN_SECONDS);

        // Create link
        $bid = !empty($_GET["bid"]) ? sanitize_text_field($_GET["bid"]) : null;
        $url = admin_url('admin.php');
        if (!empty($bid)) {
            $redirect_link =
                add_query_arg(
                    array(
                        'page' => 'call-now-button',
                        'action' => 'edit',
                        'id' => $bid,
                        'tid' => $transient_id,
                    ),
                    $url);
            $redirect_url = esc_url_raw($redirect_link);
            wp_safe_redirect($redirect_url);
            exit;
        } else {
            $redirect_link =
                add_query_arg(
                    array(
                        'page' => CNB_SLUG . '-actions',
                        'action' => 'edit',
                        'id' => $result->id,
                        'tid' => $transient_id,
                        'bid' => $bid),
                    $url);
            $redirect_url = esc_url_raw($redirect_link);
            wp_safe_redirect($redirect_url);
            exit;
        }
    }
    else {
        $url = admin_url('admin.php');
        $back_link =
            add_query_arg(
                array(
                    'page' => CNB_SLUG . '-actions',
                    ),
                $url);

        wp_die( __( 'Invalid nonce specified', CNB_NAME), __( 'Error', CNB_NAME), array(
            'response' 	=> 403,
            'back_link' => $back_link,
        ) );
    }
}

function cnb_action_edit_create_tab_url($button, $tab) {
    $url = admin_url('admin.php');
    $tab_link =
        add_query_arg(
            array(
                'page' => CNB_SLUG,
                'action' => 'edit',
                'type' => strtolower($button->type),
                'id' => $button->id,
                'tab' => $tab),
            $url );
    return esc_url( $tab_link );
}

/**
 *
 * WP_Locale considers "0" to be Sunday, whereas the CallNowButton APi considers "0" to be Monday. See the below table:
 *
    +-----------+-----------+------------+
    | Day       | WP_Locale | API Server |
    +-----------+-----------+------------+
    | Monday    | 1         | 0          |
    +-----------+-----------+------------+
    | Tuesday   | 2         | 1          |
    +-----------+-----------+------------+
    | Wednesday | 3         | 2          |
    +-----------+-----------+------------+
    | Thursday  | 4         | 3          |
    +-----------+-----------+------------+
    | Friday    | 5         | 4          |
    +-----------+-----------+------------+
    | Saturday  | 6         | 5          |
    +-----------+-----------+------------+
    | Sunday    | 0         | 6          |
    +-----------+-----------+------------+
 *
 * So, we need to translate.
 * @param int $wp_locale_day
 *
 * @return int The index for the CNB API Server
 */
function cnb_wp_locale_day_to_daysofweek_array_index($wp_locale_day) {
    if ($wp_locale_day == 0) return 6;
    return $wp_locale_day - 1;
}

/**
 * CNB week starts on Monday (0), WP_Local starts on Sunday (0)
 * See cnb_wp_locale_day_to_daysofweek_array_index()
 *
 * This array only signifies the order to DISPLAY the days in the UI according to WP_Locale
 * So, in this case, we make the UI render the week starting on Monday (1) and end on Sunday (0).
 */
function cnb_wp_get_order_of_days() {
    return array(1,2,3,4,5,6,0);
}

/**
* @param $action CnbAction
 * @param $button CnbButton
 * @param $domain CnbDomain
 * @param $show_table boolean
 */
function cnb_render_form_action($action, $button=null, $domain=null, $show_table=true) {
    /**
     * @global WP_Locale $wp_locale WordPress date and time locale object.
     */
    global $wp_locale;

    // In case a domain is not passed, we take it from the button
    $domain = isset($domain) ? $domain : (isset($button) ? $button->domain : null);

    $cnb_days_of_week_order = cnb_wp_get_order_of_days();

    if (empty($action->actionType)) {
        $action->actionType = 'PHONE';
    }
    $action->iconText = cnb_actiontype_to_icontext($action->actionType);
    $action->iconType = 'DEFAULT';

    wp_enqueue_style(CNB_SLUG . '-jquery-ui');
    wp_enqueue_script(CNB_SLUG . '-timezone-picker-fix');

    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui-core');
    wp_enqueue_script('jquery-ui-slider');
    wp_enqueue_script(CNB_SLUG . '-action-edit-scheduler');

    // Uses domain timezone if no timezone can be found
    $timezone = (isset($action->schedule) && !empty($action->schedule->timezone)) ? $action->schedule->timezone : (isset($domain) ? $domain->timezone : null);
    $action_tz_different_from_domain = isset($domain) && !empty($domain->timezone) && $domain->timezone !== $timezone;
    cnb_domain_timezone_check( $domain );
    $timezone_set_correctly = cnb_warn_about_timezone($domain);
    ?>
    <input type="hidden" name="actions[<?php echo esc_attr($action->id) ?>][id]" value="<?php if ($action->id !== null && $action->id !== 'new') { echo esc_attr($action->id); } ?>" />
    <input type="hidden" name="actions[<?php echo esc_attr($action->id) ?>][delete]" id="cnb_action_<?php echo esc_attr($action->id) ?>_delete" value="" />
    <input type="hidden" name="actions[<?php echo esc_attr($action->id) ?>][iconText]" value="<?php if (isset($action->iconText)) { echo esc_attr($action->iconText); } ?>" id="cnb_action_icon_text" />
    <input type="hidden" name="actions[<?php echo esc_attr($action->id) ?>][iconType]" value="<?php if (isset($action->iconType)) { echo esc_attr($action->iconType); } ?>" />
    <?php if ($show_table) { ?>
    <table data-tab-name="actions" class="form-table nav-tab-active">
    <?php } ?>
    <?php if (!$button) { ?>
        <tr>
            <th colspan="2"><h2>Action Settings</h2>
            </th>
        </tr>
    <?php } ?>

        <tr class="cnb_hide_on_modal">
            <th scope="row"><label for="cnb_action_type">Button type</label></th>
            <td>
                <select id="cnb_action_type" name="actions[<?php echo esc_attr($action->id) ?>][actionType]">
                    <?php foreach (cnb_get_action_types() as $action_type_key => $action_type_value) { ?>
                        <option value="<?php echo esc_attr($action_type_key) ?>"<?php selected($action_type_key, $action->actionType) ?>>
                            <?php echo esc_html($action_type_value) ?>
                        </option>
                    <?php } ?>
                </select>
        </tr>
        <tr class="cnb-action-value cnb_hide_on_modal">
            <th scope="row">
                <label for="cnb_action_value_input">
                    <span id="cnb_action_value">Action value</span>
                </label>

            </th>
            <td>
                <input type="text" id="cnb_action_value_input" name="actions[<?php echo esc_attr($action->id) ?>][actionValue]" value="<?php echo esc_attr($action->actionValue) ?>"/>
                <p class="description cnb-action-properties-map">Preview via <a href="#" onclick="cnb_action_update_map_link(this)" target="_blank">Google Maps</a></p>

            </td>
        </tr>
        <tr class="cnb-action-properties-whatsapp">
            <th scope="row"><label for="cnb_action_value_input_whatsapp">Whatsapp Number</label></th>
            <td>
                <input type="tel" id="cnb_action_value_input_whatsapp" name="actions[<?php echo esc_attr($action->id) ?>][actionValueWhatsapp]" value="<?php echo esc_attr($action->actionValue) ?>"/>
                <p class="description" id="cnb-valid-msg">✓ Valid</p>
                <p class="description" id="cnb-error-msg"></p>
            </td>
        </tr>
        <tr class="button-text cnb_hide_on_modal">
            <th scope="row"><label for="buttonTextField">Button label</label></th>
            <td>
                <input id="buttonTextField" type="text" name="actions[<?php echo esc_attr($action->id) ?>][labelText]"
                       value="<?php echo esc_attr($action->labelText) ?>" maxlength="30" placeholder="optional" />
                <p class="description">Leave this field empty to only show an icon.</p>
            </td>
        </tr>
        <tr class="cnb-action-properties-email">
            <th></th>
            <td><a class="cnb_cursor_pointer" onclick="jQuery('.cnb-action-properties-email-extra').show();jQuery(this).parent().parent().hide()">Extra email settings...</a></td>
        </tr>
        <tr class="cnb-action-properties-email-extra">
            <th colspan="2"><hr /></th>
        </tr>
        <tr class="cnb-action-properties-email-extra">
            <th scope="row"><label for="action-properties-subject">Subject</label></th>
            <td><input id="action-properties-subject" name="actions[<?php echo esc_attr($action->id) ?>][properties][subject]" type="text" value="<?php if (isset($action->properties) && isset($action->properties->subject)) { echo esc_attr($action->properties->subject); } ?>" /></td>
        </tr>
        <tr class="cnb-action-properties-email-extra">
            <th scope="row"><label for="action-properties-body">Body</label></th>
            <td><textarea id="action-properties-body" name="actions[<?php echo esc_attr($action->id) ?>][properties][body]" class="large-text code" rows="3"><?php if (isset($action->properties) && isset($action->properties->body)) { echo esc_textarea($action->properties->body); } ?></textarea></td>

        </tr>
        <tr class="cnb-action-properties-email-extra">
            <th scope="row"><label for="action-properties-cc">CC</label></th>
            <td><input id="action-properties-cc" name="actions[<?php echo esc_attr($action->id) ?>][properties][cc]" type="text" value="<?php if (isset($action->properties) && isset($action->properties->cc)) { echo esc_attr($action->properties->cc); } ?>" /></td>
        </tr>
        <tr class="cnb-action-properties-email-extra">
            <th scope="row"><label for="action-properties-bcc">BCC</label></th>
            <td><input id="action-properties-bcc" name="actions[<?php echo esc_attr($action->id) ?>][properties][bcc]" type="text" value="<?php if (isset($action->properties) && isset($action->properties->bcc)) { echo esc_attr($action->properties->bcc); } ?>" /></td>
        </tr>
        <tr class="cnb-action-properties-email-extra">
            <th colspan="2"><hr /></th>
        </tr>

        <tr class="cnb-action-properties-sms">
            <th></th>
            <td><a class="cnb_cursor_pointer" onclick="jQuery('.cnb-action-properties-sms-extra').show();jQuery(this).parent().parent().hide()">Extra SMS settings...</a></td>
        </tr>

        <tr class="cnb-action-properties-whatsapp">
            <th></th>
            <td><a class="cnb_cursor_pointer" onclick="jQuery('.cnb-action-properties-whatsapp-extra').show();jQuery(this).parent().parent().hide()">Extra Whatsapp settings...</a></td>
        </tr>
        <tr class="cnb-action-properties-whatsapp-extra cnb-action-properties-sms-extra">
            <th colspan="2"><hr /></th>
        </tr>
        <tr class="cnb-action-properties-whatsapp-extra cnb-action-properties-sms-extra">
            <th scope="row"><label for="action-properties-message">Default message</label></th>
            <td>
                <textarea id="action-properties-message" name="actions[<?php echo esc_attr($action->id) ?>][properties][message]" class="large-text code" rows="3"><?php if (isset($action->properties) && isset($action->properties->message)) { echo esc_textarea($action->properties->message); } ?></textarea>
            </td>
        </tr>
        <tr class="cnb-action-properties-whatsapp-extra cnb-action-properties-sms-extra">
            <th colspan="2"><hr /></th>
        </tr>

        <?php if ($button && $button->type === 'SINGLE') { ?>
        <tr class="cnb_hide_on_modal cnb_advanced_view">
            <th colspan="2">
                <h2>Colors for a Single button are defined on the Button, not the action.</h2>
                <input name="actions[<?php echo esc_attr($action->id) ?>][backgroundColor]" type="hidden" value="<?php echo esc_attr($action->backgroundColor) ?>" />
                <input name="actions[<?php echo esc_attr($action->id) ?>][iconColor]" type="hidden" value="<?php echo esc_attr($action->iconColor) ?>" />
                <!-- We always enable the icon when the type if SINGLE, original value is "<?php echo esc_attr($action->iconEnabled) ?>" -->
                <input name="actions[<?php echo esc_attr($action->id) ?>][iconEnabled]" type="hidden" value="1" />
            </th>
        </tr>
        <?php } else { ?>
        <tr class="cnb_hide_on_modal">
            <th></th>
            <td></td>
        </tr>
        <tr>
            <th scope="row"><label for="actions[<?php echo esc_attr($action->id) ?>][backgroundColor]">Background color</label></th>
            <td>
                <input name="actions[<?php echo esc_attr($action->id) ?>][backgroundColor]" id="actions[<?php echo esc_attr($action->id) ?>][backgroundColor]" type="text" value="<?php echo esc_attr($action->backgroundColor) ?>"
                       class="cnb-color-field" data-default-color="#009900"/>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="actions[<?php echo esc_attr($action->id) ?>][iconColor]">Icon color</label></th>
            <td>
                <input name="actions[<?php echo esc_attr($action->id) ?>][iconColor]" id="actions[<?php echo esc_attr($action->id) ?>][iconColor]" type="text" value="<?php echo esc_attr($action->iconColor) ?>"
                       class="cnb-iconcolor-field" data-default-color="#FFFFFF"/>
            </td>
        </tr>
            <?php if ($button && $button->type === 'MULTI') { ?>
                <input name="actions[<?php echo esc_attr($action->id) ?>][iconEnabled]" type="hidden" value="1" />
            <?php } else { ?>
            <tr>
                <th scope="row"></th>
                <td>
                    <input type="hidden" name="actions[<?php echo esc_attr($action->id) ?>][iconEnabled]" id="actions[<?php echo esc_attr($action->id) ?>][iconEnabled]" value="0" />
                    <input id="cnb-action-icon-enabled" class="cnb_toggle_checkbox" type="checkbox" name="actions[<?php echo esc_attr($action->id) ?>][iconEnabled]" id="actions[<?php echo esc_attr($action->id) ?>][iconEnabled]" value="true" <?php checked(true, $action->iconEnabled); ?>>
                    <label for="cnb-action-icon-enabled" class="cnb_toggle_label">Toggle</label>
                    <span data-cnb_toggle_state_label="cnb-action-icon-enabled" class="cnb_toggle_state cnb_toggle_false">Hide icon</span>
                    <span data-cnb_toggle_state_label="cnb-action-icon-enabled" class="cnb_toggle_state cnb_toggle_true">Show icon</span>
                </td>
            </tr>
            <?php } // End Multi/Buttonbar ?>
        <?php } ?>

        <tr class="cnb_hide_on_modal">
            <th scope="row">Show at all times</th>
            <td>
                <?php $showAlwaysValue = $action->id === 'new' || $action->schedule->showAlways; ?>
                <input name="actions[<?php echo esc_attr($action->id) ?>][schedule][showAlways]" type="hidden" value="false" />
                <input id="actions_schedule_show_always" class="cnb_toggle_checkbox" onchange="return cnb_hide_on_show_always();" name="actions[<?php echo esc_attr($action->id) ?>][schedule][showAlways]" type="checkbox"
                       value="true" <?php checked(true, $showAlwaysValue); ?>
                        <?php if (!$timezone_set_correctly) { ?>disabled="disabled" <?php } ?>/>
                <label for="actions_schedule_show_always" class="cnb_toggle_label">Toggle</label>
                <span data-cnb_toggle_state_label="actions_schedule_show_always" class="cnb_toggle_state cnb_toggle_true">Yes <?php if (!$timezone_set_correctly) { ?>(disabled)<?php } ?></span>
                <?php if (!$timezone_set_correctly && $showAlwaysValue){ ?>
                    <p class="description"><span class="dashicons dashicons-warning"></span>The scheduler is disabled because your timezone is not set correctly yet.</p>
                <?php } ?>
                <?php if (!$timezone_set_correctly && !$showAlwaysValue){ ?>
                    <p class="description"><span class="dashicons dashicons-warning"></span>Please set your timezone before making any more changes. See the notice at the top of the page for more information.</p>
                <?php } ?>
            </td>
        </tr>
        <tr>
          <td colspan="2" class="cnb_padding_0">
            <span id="domain-timezone-notice-placeholder"></span>
          </td>
        </tr>
    <tr class="cnb_hide_on_show_always">
        <th>Set days</th>
        <td>
            <?php
            foreach ($cnb_days_of_week_order as $cnb_day_of_week) {
                $selected = '';
                $api_server_index = cnb_wp_locale_day_to_daysofweek_array_index($cnb_day_of_week);
                if (isset($action->schedule) && isset($action->schedule->daysOfWeek)) {
                    $selected = (isset($action->schedule->daysOfWeek[$api_server_index]) && $action->schedule->daysOfWeek[$api_server_index] == true) ? 'checked="checked"' : '';
                }
                echo '
                <input class="cnb_day_selector" id="cnb_weekday_'.esc_attr($api_server_index).'" type="checkbox" name="actions[' . esc_attr($action->id) . '][schedule][daysOfWeek][' . esc_attr($api_server_index) . ']" value="true" '.$selected.'>
            	  <label title="'.$wp_locale->get_weekday($cnb_day_of_week).'" class="cnb_day_selector" for="cnb_weekday_'.esc_attr($api_server_index).'">'.$wp_locale->get_weekday_abbrev($wp_locale->get_weekday($cnb_day_of_week)).'</label>
                ';
            }

            ?>
        </td>
    </tr>
    <tr class="cnb_hide_on_show_always">
        <th><label for="actions_schedule_outside_hours">After hours</label></th>
        <td>
            <input id="actions_schedule_outside_hours" class="cnb_toggle_checkbox" name="actions[<?php echo esc_attr($action->id) ?>][schedule][outsideHours]" type="checkbox"
                   value="true" <?php checked(true, isset($action->schedule) && $action->schedule->outsideHours); ?> />
            <label for="actions_schedule_outside_hours" class="cnb_toggle_label">Toggle</label>
        </td>
    </tr>
    <tr class="cnb_hide_on_show_always">
        <th>Set times</th>
        <td class="cnb-scheduler-slider">
            <p id="cnb-schedule-range-text"></p>
            <div id="cnb-schedule-range" style="max-width: 300px"></div>
        </td>
    </tr>
    <tr class="cnb_hide_on_show_always cnb_advanced_view">
        <th><label for="actions-schedule-start">Start time</label></th>
        <td><input type="time" name="actions[<?php echo esc_attr($action->id) ?>][schedule][start]" id="actions-schedule-start" value="<?php if (isset($action->schedule)) { echo esc_attr($action->schedule->start); } ?>"></td>
    </tr>
    <tr class="cnb_hide_on_show_always cnb_advanced_view">
        <th><label for="actions-schedule-stop">End time</label></th>
        <td><input type="time" name="actions[<?php echo esc_attr($action->id) ?>][schedule][stop]" id="actions-schedule-stop" value="<?php if (isset($action->schedule)) { echo esc_attr($action->schedule->stop); } ?>"></td>
    </tr>
    <tr class="cnb_hide_on_show_always<?php if (!$action_tz_different_from_domain) { ?> cnb_advanced_view<?php } ?>">
        <th><label for="actions[<?php echo esc_attr($action->id) ?>][schedule][timezone]">Timezone</label></th>
        <td>
            <select name="actions[<?php echo esc_attr($action->id) ?>][schedule][timezone]" id="actions[<?php echo esc_attr($action->id) ?>][schedule][timezone]" class="cnb_timezone_picker">
                <?php
                echo wp_timezone_choice($timezone);
                ?>
            </select>
            <p class="description" id="domain_timezone-description">
                <?php if (empty($timezone)) { ?>
                    Please select your timezone.
                <?php } else { ?>
                    Currently set to <code><?php echo esc_html($timezone)?></code>.
                <?php } ?>
            </p>
            <?php if ($action_tz_different_from_domain) { ?>
                <div class="notice notice-warning inline">
                    <p>Be aware that the timezone for this action (<code><?php echo esc_html($timezone)?></code>) is different from the timezone for your domain (<code><?php echo esc_html($domain->timezone)?></code>).</p>
                </div>
            <?php } ?>
        </td>
    </tr>
    <?php if ($show_table) { ?>
    </table>
    <?php } ?>
    <?php
}

/**
 * @param $action CnbAction
 * @param $button CnbButton
 * @param $domain CnbDomain
 * @param $show_table boolean
 */
function cnb_admin_page_action_edit_render_main($action, $button, $domain=null, $show_table=true) {
    wp_enqueue_style(CNB_SLUG . '-intl-tel-input');
    wp_enqueue_script(CNB_SLUG . '-intl-tel-input');
    $bid = !empty($_GET["bid"]) ? sanitize_text_field($_GET["bid"]) : null;
    // Set some sane defaults
    $action->backgroundColor = !empty($action->backgroundColor)
        ? $action->backgroundColor
        : '#009900';
    $action->iconColor = !empty($action->iconColor)
        ? $action->iconColor
        : '#FFFFFF';
    $action->iconEnabled = isset($action->iconEnabled)
        // phpcs:ignore
        ? boolval($action->iconEnabled)
        : true;
    ?>

    <input type="hidden" name="bid" value="<?php echo $bid ?>" />
    <input type="hidden" name="action_id" value="<?php echo $action->id ?>" />
    <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('cnb-action-edit')?>" />
    <?php
    cnb_render_form_action($action, $button, $domain, $show_table);
}

function cnb_admin_page_action_edit_render() {
    $action_id = cnb_get_button_id();
    $action = new CnbAction();
    $action->id = 'new';
    $action->actionType = 'PHONE';
    $action->actionValue = null;
    $action->labelText = null;

    if (strlen($action_id) > 0 && $action_id !== 'new') {
        $action = CnbAppRemote::cnb_remote_get_action( $action_id );
    }

    add_action('cnb_header_name', function() use($action) {
        cnb_add_header_action_edit($action);
    });

    $button = null;
    $bid = !empty($_GET["bid"]) ? sanitize_text_field($_GET["bid"]) : null;
    if ($bid !== null) {
        $button = CnbAppRemote::cnb_remote_get_button_full( $bid );

        // Create back link
        $url = admin_url('admin.php');
        $back_to_button_link = esc_url(
            add_query_arg(
                array(
                    'page' => 'call-now-button',
                    'action' => 'edit',
                    'id' => $bid),
                $url ));

        $action_verb = $action->id === 'new' ? 'adding' : 'editing';
        $message = '<p><strong>You are '.$action_verb.' an Action</strong>.
                    Click <a href="'.$back_to_button_link.'">here</a> to go back to continue configuring the Button.</p>';
        CnbAdminNotices::get_instance()->renderInfo($message);
    }

    $url = admin_url('admin-post.php');
    $form_action = esc_url( $url );
    $redirect_link = add_query_arg(
        array(
            'bid' => $bid
        ),
        $form_action
    );

    wp_enqueue_script(CNB_SLUG . '-action-type-to-icon-text');
    wp_enqueue_script(CNB_SLUG . '-form-to-json');
    wp_enqueue_script(CNB_SLUG . '-preview');
    wp_enqueue_script(CNB_SLUG . '-client');

    do_action('cnb_header');

    ?>
    <div class="cnb-two-column-section-preview">
    <div class="cnb-body-column">
    <div class="cnb-body-content">

    <?php if ($bid !== null) { ?>
            <!-- These are FAKE buttons -->
    <h2 class="nav-tab-wrapper">
        <a href="<?php echo $back_to_button_link; ?>" class="cnb-nav-tab"><span class="dashicons dashicons-arrow-left-alt"></span></a>
        <a data-tab-name="actions" href="<?php echo cnb_action_edit_create_tab_url($button, 'basic_options') ?>"
           class="nav-tab nav-tab-active">Action</a>
    </h2>
    <?php } ?>
    <script>
        let cnb_button = <?php echo json_encode($button); ?>;
        let cnb_actions = <?php echo json_encode($button->actions); ?>;
        let cnb_domain = <?php echo json_encode($button->domain) ?>;
    </script>

    <form class="cnb-container" action="<?php echo $redirect_link; ?>" method="post">
        <input type="hidden" name="page" value="call-now-button-actions" />
        <input type="hidden" name="action" value="<?php echo $action->id === 'new' ? 'cnb_create_action' :'cnb_update_action' ?>" />
        <?php
        cnb_admin_page_action_edit_render_main($action, $button);
        submit_button();
        ?>
    </form>
  </div>
</div>
<div class="cnb-side-column">
  <div id="phone-preview">
    <div class="phone-outside double">
      <div class="speaker single"></div>
      <div class="phone-inside single">
        <div id="cnb-button-preview"></div>
      </div>
      <div class="mic double"></div>
    </div>
  </div>
</div>
</div>
    <?php do_action('cnb_footer');
}
