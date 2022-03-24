<?php

require_once dirname( __FILE__ ) . '/api/CnbAppRemote.php';
require_once dirname( __FILE__ ) . '/api/CnbAdminCloud.php';
require_once dirname( __FILE__ ) . '/partials/admin-functions.php';
require_once dirname( __FILE__ ) . '/partials/admin-header.php';
require_once dirname( __FILE__ ) . '/partials/admin-footer.php';
require_once dirname( __FILE__ ) . '/models/CnbButton.class.php';
require_once dirname( __FILE__ ) . '/models/CnbAction.class.php';
require_once dirname( __FILE__ ) . '/../utils/utils.php';
require_once dirname( __FILE__ ) . '/action-overview.php';
require_once dirname( __FILE__ ) . '/action-edit.php';

/**
 * Renders the "Edit <type>" header
 *
 * @param $button CnbButton (optional) Used to determine type if available
 */
function cnb_add_header_button_edit($button = null) {
    $type = strtoupper(filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING));
    $name = 'New Button';
    if ($button) {
        $type = $button->type;
        $name = $button->name;
    }
    $buttonTypes = cnb_get_button_types();
    $typeName = $buttonTypes[$type];
    echo 'Editing ' . esc_html($typeName) . ' <span class="cnb_button_name">' . esc_html($name) . '</span>';
}

function cnb_create_tab_url_button($button, $tab) {
    $url = admin_url('admin.php');
    $tab_link =
        add_query_arg(
            array(
                'page' => 'call-now-button',
                'action' => 'edit',
                'type' => strtolower($button->type),
                'id' => $button->id,
                'tab' => $tab),
            $url );
    return esc_url( $tab_link );
}

/**
 * This is called to update the button
 * via `call-now-button.php#cnb_create_<type>_button`
 */
function cnb_admin_create_button() {
    $nonce = filter_input( INPUT_POST, '_wpnonce_button', FILTER_SANITIZE_STRING );
    if( isset( $_REQUEST['_wpnonce_button'] ) && wp_verify_nonce( $nonce, 'cnb-button-edit') ) {

        // sanitize the input
        $button = filter_input(
                INPUT_POST,
                'cnb',
                FILTER_SANITIZE_STRING,
                FILTER_REQUIRE_ARRAY | FILTER_FLAG_NO_ENCODE_QUOTES);

        // ensure the position is valid for FULL
        if (strtoupper($button['type']) === 'FULL') {
            if (!empty($button['options']) && !empty($button['options']['placement'])) {
                $placement = $button['options']['placement'];
                if ($placement !== 'BOTTOM_CENTER' && $placement !== 'TOP_CENTER') {
                    $button['options']['placement'] = 'BOTTOM_CENTER';
                }
            } else {
                $button['options']['placement'] = 'BOTTOM_CENTER';
            }
        }

        // Do the processing
        $cnb_cloud_notifications = array();
        $new_button = CnbAdminCloud::cnb_create_button( $cnb_cloud_notifications, $button );

        // redirect the user to the appropriate page
        $tab          = filter_input( INPUT_POST, 'tab', FILTER_SANITIZE_STRING );
        $transient_id = 'cnb-' . wp_generate_uuid4();
        set_transient( $transient_id, $cnb_cloud_notifications, HOUR_IN_SECONDS );

        if ($new_button instanceof WP_Error) {
            $new_button_type = null;
            $new_button_id = null;
        } else {
            $new_button_type = strtolower( $new_button->type );
            $new_button_id = $new_button->id;
        }

        // Create link
        $url           = admin_url( 'admin.php' );
        $redirect_link =
            add_query_arg(
                array(
                    'page'   => 'call-now-button',
                    'action' => 'edit',
                    'type'   => $new_button_type,
                    'id'     => $new_button_id,
                    'tid'    => $transient_id,
                    'tab'    => $tab
                ),
                $url );
        $redirect_url  = esc_url_raw( $redirect_link );
        wp_safe_redirect( $redirect_url );
        exit;
    } else {
        wp_die( __( 'Invalid nonce specified', CNB_NAME), __( 'Error', CNB_NAME), array(
            'response' 	=> 403,
            'back_link' => 'admin.php?page=' . CNB_SLUG,
        ) );
    }
}

/**
 * This is called to update the button
 * via `call-now-button.php#cnb_update_<type>_button`
 */
function cnb_admin_update_button() {
    $nonce = filter_input( INPUT_POST, '_wpnonce_button', FILTER_SANITIZE_STRING );
    if( isset( $_REQUEST['_wpnonce_button'] ) && wp_verify_nonce( $nonce, 'cnb-button-edit') ) {

        // sanitize the input
        $button = filter_input(
                INPUT_POST,
                'cnb',
                FILTER_SANITIZE_STRING,
                FILTER_REQUIRE_ARRAY | FILTER_FLAG_NO_ENCODE_QUOTES);
        $actions = filter_input(
            INPUT_POST,
            'actions',
            FILTER_SANITIZE_STRING,
            FILTER_REQUIRE_ARRAY | FILTER_FLAG_NO_ENCODE_QUOTES);
        $conditions = filter_input(
            INPUT_POST,
            'condition',
            FILTER_SANITIZE_STRING,
            FILTER_REQUIRE_ARRAY | FILTER_FLAG_NO_ENCODE_QUOTES);

        if ($conditions === null) {
            $conditions = array();
        }

        // ensure the position is valid for FULL
        if (strtoupper($button['type']) === 'FULL') {
            if (!empty($button['options']) && !empty($button['options']['placement'])) {
                $placement = $button['options']['placement'];
                if ( $placement !== 'BOTTOM_CENTER' && $placement !== 'TOP_CENTER' ) {
                    $button['options']['placement'] = 'BOTTOM_CENTER';
                }
            } else {
                $button['options']['placement'] = 'BOTTOM_CENTER';
            }
        }

        // do the processing
        $processed_actions = array();
        if (is_array($actions)) {
            foreach ( $actions as $action ) {
                $processed_actions[] = cnb_admin_process_action( $action );
            }
        }
        $result = CnbAdminCloud::cnb_update_button_and_conditions( $button, $processed_actions, $conditions );

        // redirect the user to the appropriate page
        $tab = filter_input( INPUT_POST, 'tab', FILTER_SANITIZE_STRING );
        $transient_id = 'cnb-' . wp_generate_uuid4();
        set_transient($transient_id, $result, HOUR_IN_SECONDS);

        // Create link
        $url = admin_url('admin.php');
        $redirect_link =
            add_query_arg(
                array(
                    'page' => 'call-now-button',
                    'action' => 'edit',
                    'type' => strtolower($button['type']),
                    'id' => $button['id'],
                    'tid' => $transient_id,
                    'tab' => $tab),
                $url );
        $redirect_url = esc_url_raw( $redirect_link );
        wp_safe_redirect($redirect_url);
        exit;
    }
    else {
        wp_die( __( 'Invalid nonce specified', CNB_NAME), __( 'Error', CNB_NAME), array(
            'response' 	=> 403,
            'back_link' => 'admin.php?page=' . CNB_SLUG,
        ) );
    }
}

function cnb_button_edit_form($button_id, $button, $default_domain, $options=array()) {
    $domains = CnbAppRemote::cnb_remote_get_domains();

    $cnb_single_image = esc_url(plugins_url( '../../resources/images/button-new-single.png', __FILE__ ));
    $cnb_multi_image = esc_url(plugins_url( '../../resources/images/button-new-multi.png', __FILE__ ));
    $cnb_full_image = esc_url(plugins_url( '../../resources/images/button-new-full.png', __FILE__ ));

    $submit_button_text = array_key_exists('submit_button_text', $options) ? $options['submit_button_text'] : '';
    $hide_on_modal = array_key_exists('modal_view', $options) && $options['modal_view'] === true;
    if($hide_on_modal) {
        echo '<script type="text/javascript">cnb_hide_on_modal_set=1</script>';
    }

    // Create "add Action" link WITH Button association
    $url = admin_url('admin.php');
    $new_action_link =
        add_query_arg(
            array(
                'page' => 'call-now-button-actions',
                'action' => 'new',
                'id' => 'new',
                'tab' => 'actions',
                'bid' => $button->id),
            $url);
    $new_action_url = esc_url($new_action_link);

    // In case the API isn't working properly
    if ($default_domain instanceof WP_Error) {
        $default_domain = array();
        $default_domain['id'] = 0;
    }

    wp_enqueue_script(CNB_SLUG . '-action-type-to-icon-text');
    wp_enqueue_script(CNB_SLUG . '-form-to-json');
    wp_enqueue_script(CNB_SLUG . '-preview');
    wp_enqueue_script(CNB_SLUG . '-client');
    ?>
    <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post" class="cnb-container">
        <input type="hidden" name="page" value="call-now-button" />
        <input type="hidden" name="action" value="<?php echo $button_id === 'new' ? 'cnb_create_'.strtolower($button->type).'_button' :'cnb_update_'.esc_attr(strtolower($button->type)).'_button' ?>" />
        <input type="hidden" name="_wpnonce_button" value="<?php echo wp_create_nonce('cnb-button-edit')?>" />
        <input type="hidden" name="tab" value="<?php echo esc_attr(cnb_get_active_tab_name()) ?>" />

        <input type="hidden" name="cnb[id]" value="<?php echo esc_attr($button->id) ?>" />
        <input type="hidden" name="cnb[type]" value="<?php echo esc_attr($button->type) ?>" id="cnb_type" />
        <input type="hidden" name="cnb[active]"  value="<?php echo esc_attr($button->active) ?>" />
        <input type="hidden" name="cnb[domain]"  value="<?php echo esc_attr($default_domain->id) ?>" />
        <?php
        // Show all the current actions (needed to submit the form)
        foreach($button->actions as $action) { ?>
            <input type="hidden" name="actions[<?php echo esc_attr($action->id) ?>][id]" value="<?php echo esc_attr($action->id) ?>" />
        <?php } ?>

        <table class="form-table <?php if(!$hide_on_modal) { echo cnb_is_active_tab('basic_options'); } else { echo 'nav-tab-only'; } ?>" data-tab-name="basic_options">
          <tr class="cnb_hide_on_modal">
              <th></th>
              <td></td>
          </tr>
          <tr>
              <th scope="row"><label for="cnb[name]">Button name</label></th>

              <td class="activated">
                <label for="cnb[name]"><input type="text" name="cnb[name]" id="cnb[name]" required="required" value="<?php echo esc_attr($button->name); ?>" /></label>
              </td>
          </tr>
          <tr class="cnb_hide_on_modal">
              <th scope="row"><label for="cnb-enable">Button status</label></th>

              <td class="activated">
                    <input type="hidden" name="cnb[active]" value="0" />
                    <input id="cnb-enable" class="cnb_toggle_checkbox" type="checkbox" name="cnb[active]" value="1" <?php checked(true, $button->active); ?> />
                    <label for="cnb-enable" class="cnb_toggle_label">Toggle</label>
                    <span data-cnb_toggle_state_label="cnb-enable" class="cnb_toggle_state cnb_toggle_false">Inactive</span>
                    <span data-cnb_toggle_state_label="cnb-enable" class="cnb_toggle_state cnb_toggle_true">Active</span>
              </td>
          </tr>
          <tr class="cnb_hide_on_modal cnb_advanced_view">
              <th scope="row"><label for="cnb[domain]">Domain</label></th>
              <td>
                    <select name="cnb[domain]" id="cnb[domain]">
                        <?php
                        foreach ($domains as $domain) { ?>
                                    <option value="<?php echo esc_attr($domain->id) ?>"<?php selected($domain->id, $button->domain->id) ?>>
                                        <?php echo esc_html($domain->name) ?>
                                <?php if ($domain->id == $default_domain->id) { echo ' (current Wordpress domain)'; } ?>
                            </option>
                        <?php } ?>
                    </select>
              </td>
            </tr>
            <?php if ($button->type !== 'SINGLE') { ?>
            <tr class="cnb_hide_on_modal">
                <th colspan="2" class="cnb_padding_0">
                    <h2 >Actions <?php echo '<a href="' . $new_action_url . '" class="page-title-action">Add Action</a>'; ?></h2>
                </th>
            </tr>
            <?php }
            if ($button->type === 'SINGLE') {
                $action = new CnbAction();

                // If there is a real one, use that one
                if (sizeof($button->actions) > 0) {
                    $action = $button->actions[0];
                } else {
                    // Create a dummy Action
                    $action->id = 'new';
                    $action->actionType = '';
                    $action->actionValue = '';
                    $action->labelText = '';
                    $action->properties = new CnbActionProperties();
                }
                cnb_admin_page_action_edit_render_main($action, $button, $default_domain, false);
            } else {
            ?>
        </table>

        <div data-tab-name="basic_options" class="cnb-button-edit-action-table <?php if($hide_on_modal) { echo cnb_is_active_tab('basic_options'); } else { echo 'nav-tab-only'; } ?>" <?php if(!cnb_is_active_tab('basic_options')) { echo 'style="display:none"'; } ?>>
            <?php cnb_admin_page_action_overview_render_form(array('button' => $button)); ?>
            <script>
                let cnb_actions = <?php echo json_encode($button->actions) ?>;
                let cnb_domain = <?php echo json_encode($default_domain) ?>;
            </script>
        </div>
        <table class="form-table <?php if(!$hide_on_modal) { echo cnb_is_active_tab('basic_options'); } else { echo 'nav-tab-only'; } ?>"><?php
            } ?>
            <?php if ($button_id === 'new') { ?>
                <tr>
                    <th scope="row">Select button type</th>
                </tr>
                <tr>
                    <td scope="row" colspan="2" class="cnb_type_selector">
                      <div class="cnb-flexbox">
                        <div class="cnb_type_selector_item cnb_type_selector_single cnb_type_selector_active" data-cnb-selection="single">
                            <img style="max-width:100%;" alt="Choose a Single button type" src="<?php echo $cnb_single_image ?>">
                            <div style="text-align:center">Single button</div>
                        </div>
                        <div class="cnb_type_selector_item cnb_type_selector_multi" data-cnb-selection="multi">
                            <img style="max-width:100%;" alt="Choose a Multibutton type" src="<?php echo $cnb_multi_image ?>">
                            <div style="text-align:center">Multibutton</div>
                        </div>
                        <div class="cnb_type_selector_item cnb_type_selector_full" data-cnb-selection="full">
                            <img style="max-width:100%;" alt="Choose a Full button type" src="<?php echo $cnb_full_image ?>">
                            <div style="text-align:center">Buttonbar</div>
                        </div>
                      </div>
                    </td>
                </tr>
            <?php } ?>
        </table>
        <table class="form-table <?php echo cnb_is_active_tab('extra_options') ?>" data-tab-name="extra_options">
            <?php if ($button->type === 'FULL') { ?>
                <tr>
                    <th colspan="2">
                        <h2>Colors for the Buttonbar are defined via the Actions.</h2>
                        <input name="cnb[options][iconBackgroundColor]" type="hidden" value="<?php echo esc_attr($button->options->iconBackgroundColor); ?>" />
                        <input name="cnb[options][iconColor]" type="hidden" value="<?php echo esc_attr($button->options->iconColor); ?>" />
                    </th>
                </tr>
            <?php } else { ?>
                <tr class="cnb_hide_on_modal">
                    <th></th>
                    <td></td>
                </tr>
                <tr>
                    <th scope="row"><label for="cnb[options][iconBackgroundColor]">Background color</label></th>
                    <td>
                        <input name="cnb[options][iconBackgroundColor]" id="cnb[options][iconBackgroundColor]" type="text" value="<?php echo esc_attr($button->options->iconBackgroundColor); ?>"
                               class="cnb-iconcolor-field" data-default-color="#009900"/>
                        <?php if ($button->type === 'MULTI') { ?>
                            <p class="description"><span class="dashicons dashicons-info"></span>This color applies to the collapsable button only.</p>
                        <?php } ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="cnb[options][iconColor]">Icon color</label></th>
                    <td>
                        <input name="cnb[options][iconColor]" id="cnb[options][iconColor]" type="text" value="<?php echo esc_attr($button->options->iconColor); ?>"
                               class="cnb-iconcolor-field" data-default-color="#FFFFFF"/>
                        <?php if ($button->type === 'MULTI') { ?>
                            <p class="description"><span class="dashicons dashicons-info"></span>This color applies to the collapsable button only.</p>
                        <?php } ?>
                    </td>
                </tr>
            <?php } ?>
            <tr>
                <th scope="row">Position <a
                        href="<?php echo CNB_SUPPORT; ?>button-position/<?php cnb_utm_params("question-mark", "button-position"); ?>"
                        target="_blank" class="cnb-nounderscore">
                        <span class="dashicons dashicons-editor-help"></span>
                    </a></th>
                <td class="appearance">
                    <div class="appearance-options">
                        <?php if ($button->type === 'FULL') { ?>
                            <div class="cnb-radio-item">
                                <input type="radio" id="appearance1" name="cnb[options][placement]"
                                       value="TOP_CENTER" <?php checked('TOP_CENTER', $button->options->placement); ?>>
                                <label title="top-center" for="appearance1">Top</label>
                            </div>
                            <div class="cnb-radio-item">
                                <input type="radio" id="appearance2" name="cnb[options][placement]"
                                       value="BOTTOM_CENTER" <?php checked('BOTTOM_CENTER', $button->options->placement); ?>>
                                <label title="bottom-center" for="appearance2">Bottom</label>
                            </div>
                        <?php } else { ?>
                            <div class="cnb-radio-item">
                                <input type="radio" id="appearance1" name="cnb[options][placement]"
                                       value="BOTTOM_RIGHT" <?php checked('BOTTOM_RIGHT', $button->options->placement); ?>>
                                <label title="bottom-right" for="appearance1">Right corner</label>
                            </div>
                            <div class="cnb-radio-item">
                                <input type="radio" id="appearance2" name="cnb[options][placement]"
                                       value="BOTTOM_LEFT" <?php checked('BOTTOM_LEFT', $button->options->placement); ?>>
                                <label title="bottom-left" for="appearance2">Left corner</label>
                            </div>
                            <div class="cnb-radio-item">
                                <input type="radio" id="appearance3" name="cnb[options][placement]"
                                       value="BOTTOM_CENTER" <?php checked('BOTTOM_CENTER', $button->options->placement); ?>>
                                <label title="bottom-center" for="appearance3">Center</label>
                            </div>

                            <?php if ($button->type !== 'MULTI') { ?>
                            <!-- Extra placement options -->
                            <br class="cnb-extra-placement">
                            <div class="cnb-radio-item cnb-extra-placement <?php echo $button->options->placement == "MIDDLE_RIGHT" ? "cnb-extra-active" : ""; ?>">
                                <input type="radio" id="appearance5" name="cnb[options][placement]"
                                       value="MIDDLE_RIGHT" <?php checked('MIDDLE_RIGHT', $button->options->placement); ?>>
                                <label title="middle-right" for="appearance5">Middle right</label>
                            </div>
                            <div class="cnb-radio-item cnb-extra-placement <?php echo $button->options->placement == "MIDDLE_LEFT" ? "cnb-extra-active" : ""; ?>">
                                <input type="radio" id="appearance6" name="cnb[options][placement]"
                                       value="MIDDLE_LEFT" <?php checked('MIDDLE_LEFT', $button->options->placement); ?>>
                                <label title="middle-left" for="appearance6">Middle left </label>
                            </div>
                            <br class="cnb-extra-placement">
                            <div class="cnb-radio-item cnb-extra-placement <?php echo $button->options->placement == "TOP_RIGHT" ? "cnb-extra-active" : ""; ?>">
                                <input type="radio" id="appearance7" name="cnb[options][placement]"
                                       value="TOP_RIGHT" <?php checked('TOP_RIGHT', $button->options->placement); ?>>
                                <label title="top-right" for="appearance7">Top right corner</label>
                            </div>
                            <div class="cnb-radio-item cnb-extra-placement <?php echo $button->options->placement == "TOP_LEFT" ? "cnb-extra-active" : ""; ?>">
                                <input type="radio" id="appearance8" name="cnb[options][placement]"
                                       value="TOP_LEFT" <?php checked('TOP_LEFT', $button->options->placement); ?>>
                                <label title="top-left" for="appearance8">Top left corner</label>
                            </div>
                            <div class="cnb-radio-item cnb-extra-placement <?php echo $button->options->placement == "TOP_CENTER" ? "cnb-extra-active" : ""; ?>">
                                <input type="radio" id="appearance9" name="cnb[options][placement]"
                                       value="TOP_CENTER" <?php checked('TOP_CENTER', $button->options->placement); ?>>
                                <label title="top-center" for="appearance9">Center top</label>
                            </div>
                            <a href="#" id="cnb-more-placements">More placement options...</a>
                            <!-- END extra placement options -->
                            <?php } ?>
                        <?php } ?>
                    </div>
                </td>
            </tr>
        </table>
        <table class="form-table <?php echo cnb_is_active_tab('visibility') ?>" data-tab-name="visibility">
            <tbody id="cnb_form_table_visibility">
                <tr>
                    <th></th>
                    <td></td>
                </tr>
                <tr>
                  <th scope="row"><label for="cnb_button_options_displaymode">Display on </label></th>
                  <td class="appearance">
                      <select name="cnb[options][displayMode]" id="cnb_button_options_displaymode">
                          <option value="MOBILE_ONLY"<?php selected('MOBILE_ONLY', $button->options->displayMode) ?>>Mobile only</option>
                          <option value="DESKTOP_ONLY"<?php selected('DESKTOP_ONLY', $button->options->displayMode) ?>>Desktop only</option>
                          <option value="ALWAYS"<?php selected('ALWAYS', $button->options->displayMode) ?>>All screens</option>
                      </select>
                  </td>
                </tr>
                <tr>
                    <th>Show on all pages</th>
                    <td>
                        <input class="cnb_toggle_checkbox" type="checkbox" id="conditions_show_on_all_pages" <?php checked(true, empty($button->conditions)); ?> />
                        <label for="conditions_show_on_all_pages" class="cnb_toggle_label">Toggle</label>
                        <span data-cnb_toggle_state_label="conditions_show_on_all_pages" class="cnb_toggle_state cnb_toggle_false">(No)</span>
                        <span data-cnb_toggle_state_label="conditions_show_on_all_pages" class="cnb_toggle_state cnb_toggle_true">Yes</span>
                    </td>
                </tr>
            <tr class="cnb_hide_on_show_on_all_pages">
                <th><input type="button" onclick="return cnb_add_condition();" value="Add page rule" class="button button-secondary page-title-action"></th>
            </tr>
            <?php if (empty($button->conditions)) { ?>
                <tr class="cnb_hide_on_show_on_all_pages">
                    <td colspan="2">
                        <p class="cnb_paragraph">You have no page visibility rules set up. This means that your button will still show on all pages.</p>
                        <p class="cnb_paragraph">Click the <code>Add page rule</code> button above to add a page rule. You can freely mix and match rules to meet your requirements.</p>
                    </td>
                </tr>
            <?php } else { ?>
                <?php foreach ($button->conditions as $condition) { ?>
                  <tr class="appearance cnb-condition" id="cnb_condition_<?php echo esc_attr($condition->id) ?>">
                    <td colspan="2" style="padding: 0;">
                        <table class="cnb_condition_rule">
                            <tbody>
                                <tr>
                                    <td>
                                        <input type="hidden" name="condition[<?php echo esc_attr($condition->id) ?>][id]" value="<?php echo esc_attr($condition->id) ?>" />
                                        <input type="hidden" name="condition[<?php echo esc_attr($condition->id) ?>][conditionType]" value="<?php echo esc_attr($condition->conditionType) ?>" />
                                        <input type="hidden" name="condition[<?php echo esc_attr($condition->id) ?>][delete]" id="cnb_condition_<?php echo esc_attr($condition->id) ?>_delete" value="" />
                                         <label for="condition[<?php echo esc_attr($condition->id) ?>][filterType]">
                                            <select name="condition[<?php echo esc_attr($condition->id) ?>][filterType]" id="condition[<?php echo esc_attr($condition->id) ?>][filterType]">
                                                <option value="INCLUDE"<?php selected('INCLUDE', $condition->filterType) ?>>Include</option>
                                                <option value="EXCLUDE"<?php selected('EXCLUDE', $condition->filterType) ?>>Exclude</option>
                                            </select>
                                        </label>
                                    </td>
                                    <td>
                                        <select name="condition[<?php echo esc_attr($condition->id) ?>][matchType]">
                                            <?php foreach (cnb_get_condition_match_types() as $condition_match_type_key => $condition_match_type_value) { ?>
                                            <option value="<?php echo esc_attr($condition_match_type_key) ?>"<?php selected($condition_match_type_key, $condition->matchType) ?>>
                                                <?php echo esc_html($condition_match_type_value) ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </td>
                                    <td class="max_width_column">
                                        <input type="text" name="condition[<?php echo esc_attr($condition->id) ?>][matchValue]" value="<?php echo esc_attr($condition->matchValue); ?>"/>
                                        <a onclick="return cnb_remove_condition('<?php echo esc_js($condition->id) ?>');" title="Remove Condition" class="button-link button-link-delete"><span class="dashicons dashicons-no"></span></a>
                                    </td>
                                </tr>
                                <?php // Match old "Hide button on front page"
                                if ($condition->conditionType === 'URL' && $condition->filterType === 'EXCLUDE' && $condition->matchType === 'EXACT' && $condition->matchValue === get_home_url()) { ?>
                                <tr>
                                    <td colspan="3"><p class="description" style="text-align: center;"><span class="dashicons dashicons-info"></span> This condition matches the plugin's "<strong>Hide button on front page</strong>" checkbox.</p></td>
                                </tr>
                                <?php } ?>
                                <tr class="cnb_advanced_view">
                                    <td colspan="3" style="padding: 5px 10px"><div class="cnb_font_normal cnb_font_90">ID: <code class="cnb_font_90"><?php echo esc_html($condition->id) ?></code></div></td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                <?php } } ?>
            <tr id="cnb_form_table_add_condition">
                <th></th>
                <td></td>
            </tr>
            </tbody>
        </table>

        <input type="hidden" name="cnb[version]" value="<?php echo CNB_VERSION; ?>"/>
        <?php submit_button($submit_button_text); ?>
    </form>
    <?php
}

/**
 * Main entrypoint, used by `call-now-button.php`.
 */
function cnb_admin_page_edit_render() {
    global $cnb_options;

    $button_id = cnb_get_button_id();
    $button = new CnbButton();

    // Get the various supported domains
    $default_domain = CnbAppRemote::cnb_remote_get_wp_domain();

    if (strlen($button_id) > 0 && $button_id !== 'new') {
        $button = CnbAppRemote::cnb_remote_get_button_full( $button_id );
    } elseif ($button_id === 'new') {
        $button->type = strtoupper(filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING));
        $button->domain = $default_domain;
    }
    if ($button->actions === null) {
        $button->actions = array();
    }

    // Set some sane defaults
    CnbButton::setSaneDefault($button);

    // Create options
    $options = array();
    $options['advanced_view'] = $cnb_options['advanced_view'];

    add_action('cnb_header_name', function() use($button) {
        cnb_add_header_button_edit($button);
    });

    do_action('cnb_header');
    cnb_warn_about_timezone($default_domain);
    ?>

    <div class="cnb-two-column-section-preview">
      <div class="cnb-body-column">
        <div class="cnb-body-content">

          <h2 class="nav-tab-wrapper">
            <a href="<?php echo cnb_create_tab_url_button($button, 'basic_options') ?>"
               class="nav-tab <?php echo cnb_is_active_tab('basic_options') ?>" data-tab-name="basic_options">Basics</a>
            <?php if ($button_id !== 'new') { ?>
                <a href="<?php echo cnb_create_tab_url_button($button, 'extra_options') ?>"
                   class="nav-tab <?php echo cnb_is_active_tab('extra_options') ?>" data-tab-name="extra_options">Presentation</a>
                <a href="<?php echo cnb_create_tab_url_button($button, 'visibility') ?>"
                   class="nav-tab <?php echo cnb_is_active_tab('visibility') ?>" data-tab-name="visibility">Visibility</a>
            <?php } else { ?>
                <a class="nav-tab"><i>Additional options available after saving</i></a>
            <?php } ?>
          </h2>

    <?php
    cnb_button_edit_form($button_id, $button, $default_domain, $options);
    ?>
        <!-- <div id="cnb-button-preview"></div> -->
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

    <?php
    do_action('cnb_footer');
}
