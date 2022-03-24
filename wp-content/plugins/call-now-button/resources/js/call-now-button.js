function cnb_setup_colors() {
	// This "change" options ensures that if a color is changed and a livePreview is available,
	// we update it. Cannot not be done via an ".on('change')", since the wpColorPicker cannot
	// respond to those events
	const options = {
		change: () => {
			jQuery(() => {
				if (typeof livePreview !== 'undefined') {
					livePreview();
				}
			});

		}
	}

	// Add color picker
	jQuery('.cnb-color-field').wpColorPicker(options);
	jQuery('.cnb-iconcolor-field').wpColorPicker(options);

	// Reveal additional button placements when clicking "more"
	jQuery("#cnb-more-placements").on('click', function(e){
		e.preventDefault();
		jQuery(".cnb-extra-placement").css("display","block");
		jQuery("#cnb-more-placements").remove();
	});

	// TODO The input names AND radioValue might have to be changed to reflect new values
	// Option to Hide Icon is only visible when the full width button is selected
	const radioValue = jQuery("input[name='cnb[appearance]']:checked").val();
	const textValue = jQuery("input[name='cnb[text]']").val();
	if(radioValue !== 'full' && radioValue !== 'tfull') {
		jQuery('#hideIconTR').hide();
	} else if(textValue.length < 1) {
		jQuery('#hideIconTR').hide();
	}
	jQuery('input[name="cnb[appearance]"]').on("change",function(){
		const radioValue = jQuery("input[name='cnb[appearance]']:checked").val();
		const textValue = jQuery("input[name='cnb[text]']").val();
		if(radioValue !== 'full' && radioValue !== 'tfull') {
			jQuery('#hideIconTR').hide();
		} else if(textValue.length > 0 ) {
			jQuery('#hideIconTR').show();
		}
	});
}

function cnb_setup_sliders() {
	jQuery('#cnb_slider').on("input change", function() {
		cnb_update_sliders();
	});
	jQuery('#cnb_order_slider').on("input change", function() {
		cnb_update_sliders();
	});
	cnb_update_sliders();
}

function cnb_update_sliders() {
	// Zoom slider - show percentage
	const cnb_slider = document.getElementById("cnb_slider");
	if (cnb_slider && cnb_slider.value) {
		const cnb_slider_value = document.getElementById("cnb_slider_value");
		cnb_slider_value.innerHTML = '(' + Math.round(cnb_slider.value * 100) + '%)';
	}

	// Z-index slider - show steps
	const cnb_order_slider = document.getElementById("cnb_order_slider");
	if (cnb_order_slider && cnb_order_slider.value) {
		const cnb_order_value = document.getElementById("cnb_order_value");
		cnb_order_value.innerHTML = cnb_order_slider.value;
	}
}

function cnb_hide_on_show_always() {
	let show_always_checkbox = document.getElementById('actions_schedule_show_always');
	if (show_always_checkbox) {
		if (show_always_checkbox.checked) {
			// Hide all items specific for Scheduler
			jQuery('.cnb_hide_on_show_always:not(.cnb_advanced_view)').hide();

			// Hide Domain Timezone notice
			jQuery('#cnb-notice-domain-timezone-unsupported').parent('.notice').hide();
		} else {
			// Show all items specific for Scheduler (except for "cnb_advanced_view")
			jQuery('.cnb_hide_on_show_always:not(.cnb_advanced_view)').show();

			// Show Domain Timezone notice (and move to the correct place)
			const domainTimezoneNotice = jQuery('#cnb-notice-domain-timezone-unsupported').parent('.notice');
			domainTimezoneNotice.show();
			const domainTimezoneNoticePlaceholder = jQuery('#domain-timezone-notice-placeholder');
			if (domainTimezoneNoticePlaceholder.length !== 0) {
				domainTimezoneNotice.insertAfter(domainTimezoneNoticePlaceholder);
			}
		}
	}
	return false;
}

/**
 * Disable the Cloud inputs when it is disabled (but only on the settings screen,
 * where that checkbox is actually visible)
 */
function cnb_disable_api_key_when_cloud_hosting_is_disabled() {
	const ele = jQuery('#cnb_cloud_enabled');
	if (ele.length) {
		jQuery('.when-cloud-enabled :input').prop('disabled', !ele.is(':checked'));
	}
}

function cnb_animate_saving() {
	jQuery('.call-now-button #submit').on('click', function (event) {
		// if value is saving, skip...
		if (jQuery(this).prop('value') === 'Saving...') {
			event.preventDefault();
			return;
		}
		// Check if the form will actually subbmit...
		const form = jQuery(this).closest('form');
		const valid = form[0].checkValidity();
		if (valid) {
			jQuery(this).addClass('is-busy');
			jQuery(this).prop('value', 'Saving...');
			jQuery(this).prop('aria-disabled', 'true');
		} else {
			// Clear old notices
			jQuery('.cnb-form-validation-notice').remove();

			const invalidFields = form.find(':invalid')
			// Find tab with error and switch to it if found
			const tabName = invalidFields.first().closest('[data-tab-name]').data('tabName')
			if (tabName) {
				cnb_switch_tab(tabName);
			}
			// Collect all errors and create notification
			invalidFields.each( function(index,node) {
				const inner = jQuery('<p/>');
				const notification = jQuery('<div />', {class: "cnb-form-validation-notice notice notice-warning"}).append(inner);
				const label = node.labels.length > 0 ? node.labels[0].innerText + ': ' : '';
				inner.text(label + node.validationMessage);
				notification.insertAfter(form.find('#submit'));
			})
		}
	})
}

let cnb_add_condition_counter = 0;
function cnb_add_condition() {
	let template = `
	<td colspan="2" class="cnb_padding_0">
		<div class="cnb_condition_rule">
			<input type="hidden" name="condition[${cnb_add_condition_counter}][id]" value="" />
			<input type="hidden" name="condition[${cnb_add_condition_counter}][conditionType]" value="URL" />
			<input type="hidden" name="condition[${cnb_add_condition_counter}][delete]" id="cnb_condition_${cnb_add_condition_counter}_delete" value="" />
	    <div>
				<select name="condition[${cnb_add_condition_counter}][filterType]">
					<option value="INCLUDE">Include</option>
					<option value="EXCLUDE">Exclude</option>
				</select>
			</div>

			<div>
				<select class="js-condition-matchtype" data-cnb-condition-id="${cnb_add_condition_counter}" name="condition[${cnb_add_condition_counter}][matchType]">
					<option value="SIMPLE">Page path is:</option>
					<option value="EXACT">Page URL is:</option>
					<option value="SUBSTRING">Page URL contains:</option>
					<option value="REGEX">Page URL matches RegEx:</option>
				</select>
			</div>

			<div class="max_width_column">
				<input class="js-condition-matchvalue-${cnb_add_condition_counter}" type="text" name="condition[${cnb_add_condition_counter}][matchValue]" value="" placeholder="e.g. /blog/" />
				<a onclick="return cnb_remove_condition('${cnb_add_condition_counter}');" title="Remove Condition" class="button-link button-link-delete">
					<span class="dashicons dashicons-no" style="padding-top: 5px;"></span>
				</a>
			</div>
		</div>
	</td>
`;

	let table = document.getElementById('cnb_form_table_visibility');
	let container = document.getElementById('cnb_form_table_add_condition');
	let rowElement = document.createElement('tr');
	rowElement.className = 'appearance cnb_condition_new cnb-condition';
	rowElement.vAlign = 'top';
	rowElement.id = 'cnb_condition_' + cnb_add_condition_counter;
	rowElement.innerHTML = template;
	table.insertBefore(rowElement, container);

	cnb_add_condition_counter++;
	cnb_show_condition_placeholder();
	cnb_button_edit_conditions_hide_on_show_on_all_pages();
	return false;
}

// Show an example condition in the form field for each of the match types
function cnb_show_condition_placeholder() {
	jQuery('.js-condition-matchtype').on('change', function () {
		const optionSelected = jQuery(this).val();
		const conditionId = jQuery(this).data('cnb-condition-id');
		let placeholderText;
		if(optionSelected === 'SIMPLE') {
			placeholderText = '/blog/';
		} else if(optionSelected === 'EXACT') {
			placeholderText = 'https://www.example.com/sample-page/';
		} else if(optionSelected === 'SUBSTRING') {
			placeholderText = 'category/';
		} else if(optionSelected === 'REGEX') {
			placeholderText = '/(index|about)(\?id=[0-9]+)?$';
		}
		jQuery('.js-condition-matchvalue-' + conditionId).attr('placeholder', 'e.g. ' + placeholderText);
	});
}

function cnb_button_edit_conditions_change_listener() {
	jQuery('#cnb_form_table_visibility').on('change', function() {
		cnb_button_edit_conditions_hide_on_show_on_all_pages();
	})
}
// Show the rules section based on the checkbox status
function cnb_button_edit_conditions_hide_on_show_on_all_pages() {
	const condition_count = jQuery('.cnb-condition').length;
	const condition_toggle = jQuery('#conditions_show_on_all_pages');
	condition_toggle.removeAttr('disabled');
	if (condition_count > 0) {
		// Ensure disabled
		condition_toggle.prop('checked', false);
		condition_toggle.attr('disabled', 'disabled');
	} else {
		if(condition_toggle.prop('checked')) {
			jQuery(".cnb_hide_on_show_on_all_pages").hide();
		} else {
			jQuery(".cnb_hide_on_show_on_all_pages").show();
		}
	}
	return false;
}

function cnb_setup_toggle_label_clicks() {
	jQuery('.cnb_toggle_state').on( "click", function() {
		const stateLabel = jQuery(this).data('cnb_toggle_state_label');
		jQuery('#' + stateLabel).trigger('click');
	});
}

function cnb_remove_condition(id) {
	let container = document.getElementById('cnb_condition_' + id);
	let deleteElement = document.getElementById('cnb_condition_' + id + '_delete');
	deleteElement.value = 'true';
	jQuery(container).css("background-color", "#ff726f");
	container.classList.remove('cnb-condition');
	jQuery(container).fadeOut(function() {
		jQuery(container).css("background-color", "");
		if (container.className.includes('cnb_condition_new')) {
			container.remove();
		}
	});
	cnb_button_edit_conditions_hide_on_show_on_all_pages();
}

function cnb_action_appearance() {
	jQuery('#cnb_action_type').on('change', function (obj) {
		cnb_action_update_appearance(obj.target.value);
	});

	// Setup WHATSAPP integration
	const input = document.querySelector("#cnb_action_value_input_whatsapp");
	if (!input || !window.intlTelInput) {
		return
	}

	const iti = window.intlTelInput(input, {
		utilsScript: 'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.12/js/utils.min.js',
		nationalMode: false,
		separateDialCode: true,
		hiddenInput: 'actionValueWhatsappHidden'
	});

	// here, the index maps to the error code returned from getValidationError - see readme
	const errorMap = [
		'Invalid number',
		'Invalid country code',
		'Too short',
		'Too long',
		'Invalid number'];

	const errorMsg = jQuery('#cnb-error-msg');
	const validMsg = jQuery('#cnb-valid-msg');

	const reset = function() {
		input.classList.remove('error');
		errorMsg.html('');
		errorMsg.hide();
		validMsg.hide();
	};

	const onBlur = function() {
		reset();
		if (input.value.trim()) {
			if (iti.isValidNumber()) {
				validMsg.show();
			} else {
				const errorCode = iti.getValidationError();
				if (errorCode < 0) {
					// Unknown error, ignore for now
					return
				}
				input.classList.add('error');
				errorMsg.text(errorMap[errorCode]);
				errorMsg.show();
			}
		} else {
			// Empty
			reset();
		}
	}

	// on blur: validate
	input.addEventListener('blur', onBlur);

	// on keyup / change flag: reset
	input.addEventListener('change', onBlur);
	input.addEventListener('keyup', onBlur);

	// init
	onBlur();
}

function cnb_action_update_appearance(value) {
	const emailEle = jQuery('.cnb-action-properties-email');
	const emailExtraEle = jQuery('.cnb-action-properties-email-extra');
	const whatsappEle = jQuery('.cnb-action-properties-whatsapp');
	const whatsappExtraEle = jQuery('.cnb-action-properties-whatsapp-extra');
	const smsEle = jQuery('.cnb-action-properties-sms');
	const smsExtraEle = jQuery('.cnb-action-properties-sms-extra');

	const propertiesEle = jQuery('.cnb-action-properties-map');
	const valueEle = jQuery('.cnb-action-value');
	const valueTextEle = jQuery('#cnb_action_value_input');
	const valuelabelEle = jQuery('#cnb_action_value');
	const whatsappValueEle = jQuery('#cnb_action_value_input_whatsapp')
	emailEle.hide();
	emailExtraEle.hide();
	whatsappEle.hide();
	whatsappExtraEle.hide();
	smsEle.hide();
	smsExtraEle.hide();
	propertiesEle.hide();

	valueEle.show();
	valueTextEle.prop( 'disabled', false );
	whatsappValueEle.prop( 'disabled', true );

	valueTextEle.removeAttr("required")
	whatsappValueEle.removeAttr("required")

	switch (value) {
		case 'ANCHOR':
			valuelabelEle.text('On-page anchor');
			valueTextEle.attr("required", "required");
			break
		case 'EMAIL':
			valuelabelEle.text('E-mail address');
			valueTextEle.attr("required", "required");
			emailEle.show()
			break
		case 'HOURS':
			// Not implemented yet
			break;
		case 'LINK':
			valuelabelEle.text('Full URL');
			valueTextEle.attr("required", "required");
			break
		case 'MAP':
			valuelabelEle.text('Address');
			valueTextEle.attr("required", "required");
			propertiesEle.show();
			break
		case 'PHONE':
			valuelabelEle.text('Phone number');
			valueTextEle.attr("required", "required");
			break
		case 'SMS':
			valuelabelEle.text('Phone number');
			valueTextEle.attr("required", "required");
			smsEle.show();
			break
		case 'WHATSAPP':
			valuelabelEle.text('Whatsapp number');
			valueEle.hide();
			valueTextEle.prop( 'disabled', true );
			whatsappValueEle.prop( 'disabled', false );
			whatsappEle.show();
			whatsappValueEle.attr("required", "required");
			break
		default:
			valuelabelEle.text('Action value');
			valueTextEle.attr("required", "required");
	}
}

function cnb_action_update_map_link(element) {
	jQuery(element).prop("href", "https://maps.google.com?q=" + jQuery('#cnb_action_value_input').val())
}

function cnb_hide_edit_action_if_advanced() {
	const element = jQuery('#toplevel_page_call-now-button li.current a');
	if (element.text() === 'Edit action') {
		element.removeAttr('href');
		element.css('cursor', 'default');
	}
}

function cnb_hide_edit_domain_upgrade_if_advanced() {
	const element = jQuery('#toplevel_page_call-now-button li.current a');
	if (element.text() === 'Upgrade domain') {
		element.removeAttr('href');
		element.css('cursor', 'default');
	}
}

function cnb_hide_on_modal() {
	jQuery('.cnb_hide_on_modal').hide();
	jQuery('.cnb_hide_on_modal input').removeAttr('required');
}

function show_advanced_view_only() {
	jQuery('.cnb_advanced_view').show();
}

function cnb_strip_beta_from_referrer() {
	const referer = jQuery('input[name="_wp_http_referer"]');
	if (referer && referer.val()) {
		referer.val(referer.val().replace(/[?&]beta/, ''))
		referer.val(referer.val().replace(/[?&]api_key=[0-9a-z-]+/, ''))
		referer.val(referer.val().replace(/[?&]api_key_ott=[0-9a-z-]+/, ''))
		referer.val(referer.val().replace(/[?&]cloud_enabled=[0-9]/, ''))
	}
}

/**
 * This calls the admin-ajax action called 'cnb_delete_action'
 */
function cnb_delete_action() {
	jQuery('.cnb-button-edit-action-table tbody[data-wp-lists="list:cnb_list_action"]#the-list span.delete a[data-ajax="true"]')
		.on('click', function(){
		// Prep data
		const id = jQuery(this).data('id');
		const bid = jQuery(this).data('bid');
		const data = {
			'action': 'cnb_delete_action',
			'id': id,
			'bid': bid,
			'_ajax_nonce': jQuery(this).data('wpnonce'),
		};

		// Send remove request
		jQuery.post(ajaxurl, data)
			.done((result) => {
				// Update the global "cnb_actions" variable
				if (result && result.button && result.button.actions) {
					cnb_actions = result.button.actions
					if (typeof livePreview !== 'undefined') {
						livePreview();
					}
				}
			});

		// Remove container
		const action_row = jQuery(this).closest('tr');
		jQuery(action_row).css("background-color", "#ff726f");
		jQuery(action_row).fadeOut(function() {
			jQuery(action_row).css("background-color", "");
			jQuery(action_row).remove();

			// Special case: if this is the last item, show a "no items" row
			const remaining_items = jQuery('#the-list tr').length;
			if (!remaining_items) {
				// Add row
				jQuery('#the-list').html('<tr class="no-items"><td class="colspanchange" colspan="3">This button has no actions yet. Let\'s add one!</td></tr>');
			}
		});

		// Remove ID from Button array
		jQuery('input[name^="actions['+id+']"').remove();
		return false;
	});
}

/**
 * function for the button type selection in the New button modal
 */
function cnb_button_overview_modal() {
	jQuery(".cnb_type_selector_item").on('click', function(){
		jQuery(".cnb_type_selector_item").removeClass('cnb_type_selector_active');
		jQuery(this).addClass("cnb_type_selector_active");
		const cnbType = jQuery(this).attr("data-cnb-selection");
		jQuery('#cnb_type').val(cnbType);
	});

	jQuery("#cnb-button-overview-modal-add-new").on("click", function() {
		setTimeout(function () {
			jQuery("input[name='cnb[name]']").trigger("focus");
		});
	});
}



/**
 * Logic for the profile form.
 * Toggles visibility and requirements based on selected country and VAT setting.
 * Erases values when switching to a different country during data entry (e.g. state value will be dropped when country is switched from USA to Belgium)
 */
function cnb_profile_edit_setup() {
	const countryEle = jQuery('#cnb_profile_country')
	const euVatEle = jQuery("#cnb-euvatbusiness")
	// First time setup of page
	const currentCountry = countryEle.val();
	cnb_profile_show_hide_fields(currentCountry);

	cnb_profile_show_hide_tax_fields(euVatEle)

	countryEle.on('change',function() {
		const currentCountry = jQuery(this).val();
		cnb_profile_show_hide_fields(currentCountry);
	});
	euVatEle.on('change',function() {
		const element = jQuery(this);
		cnb_profile_show_hide_tax_fields(element)
	});
}

function cnb_profile_show_hide_tax_fields(element) {
	if(element.is(":checked")) {
		jQuery(".cnb_vat_companies_show").show();
		jQuery(".cnb_vat_companies_required").attr("required","required");
	} else {
		jQuery(".cnb_vat_companies_show").hide();
		jQuery(".cnb_vat_companies_required").removeAttr("required");
	}
}

function cnb_profile_show_hide_fields(currentCountry) {
	const euCountries = [
		"AT",
		"BE",
		"BG",
		"HR",
		"CY",
		"CZ",
		"DK",
		"EE",
		"FI",
		"FR",
		"DE",
		"GR",
		"HU",
		"IE",
		"IT",
		"LV",
		"LT",
		"LU",
		"MT",
		"NL",
		"PL",
		"PT",
		"RO",
		"SK",
		"SI",
		"ES",
		"SE",
	]; // source https://www.belastingdienst.nl/wps/wcm/connect/bldcontentnl/belastingdienst/zakelijk/btw/zakendoen_met_het_buitenland/goederen_en_diensten_naar_andere_eu_landen/eu-landen_en_-gebieden/

	if(jQuery.inArray(currentCountry,euCountries) !== -1) {
		jQuery(".cnb_show_vat_toggle").show();
		jQuery(".cnb_us_required").removeAttr("required");
		//jQuery(".cnb_us_values_only").val('');
		if(currentCountry === 'IE') {
			jQuery(".cnb_ie_only").show();
		}
	} else if(currentCountry === 'US') {
		jQuery(".cnb_show_vat_toggle, .cnb_vat_companies_show").hide();
		jQuery(".cnb_us_show").show();
		jQuery(".cnb_us_required").attr("required","required");
		jQuery("#cnb-euvatbusiness, .cnb_vat_companies_required, #cnb_profile_vat").removeAttr("required checked");
		//jQuery(".cnb_eu_values_only").val('');
	} else {
		jQuery(".cnb_us_show, .cnb_show_vat_toggle, .cnb_vat_companies_show").hide();
		jQuery("#cnb-euvatbusiness, .cnb_us_required, .cnb_vat_companies_required, #cnb_profile_vat").removeAttr("required checked");
		//jQuery(".cnb_eu_values_only, .cnb_us_values_only, .cnb_useu_values_only").val('');
	}
}

function cnb_button_overview_add_new_click() {
	jQuery("#cnb-button-overview-modal-add-new").trigger("click");
	return false;
}


function cnb_init_tabs() {
	jQuery('a.nav-tab').on('click', (e) => {
		e.preventDefault();
		return cnb_switch_tab(jQuery( e.target ).data('tabName'))
	});
}

function cnb_switch_tab(tabName, addToHistory = true) {
	const tab = jQuery('a.nav-tab[data-tab-name][data-tab-name="' + tabName + '"]');
	const tabContent = jQuery('table[data-tab-name][data-tab-name="' + tabName + '"], div[data-tab-name][data-tab-name="' + tabName + '"]');

	// Does tab name exist (if not, don't do anything)
	if (tab.length === 0) return false;

	// Hide all tabs
	const otherTabs = jQuery('a.nav-tab[data-tab-name][data-tab-name!="' + tabName + '"]');
	const otherTabsContent = jQuery('table[data-tab-name][data-tab-name!="' + tabName + '"], div[data-tab-name][data-tab-name!="' + tabName + '"]');
	otherTabs.removeClass('nav-tab-active')
	otherTabsContent.hide();

	// Display passed in tab
	tab.addClass('nav-tab-active')
	tabContent.show();

	// Push this to URL
	if (addToHistory) {
		const url = new URL(window.location);
		const data = {
			cnb_switch_tab_event: true,
			tab_name: tabName
		}

		url.searchParams.set('tab', tabName);
		window.history.pushState(data, '', url);
	}

	return false;
}

function cnb_switch_tab_from_history_listener() {
	window.addEventListener('popstate', (event) => {
		if (event && event.state && event.state.cnb_switch_tab_event && event.state.tab_name) {
			// Switch back but do NOT add this action to the history again to prevent loops
			cnb_switch_tab(event.state.tab_name, false)
		}
	});
}

jQuery( function() {
	// Generic
	cnb_setup_colors();
	cnb_setup_sliders();
	cnb_hide_on_show_always();
	cnb_disable_api_key_when_cloud_hosting_is_disabled();
	cnb_action_appearance();
	cnb_action_update_appearance(jQuery('#cnb_action_type').val());
	cnb_hide_edit_action_if_advanced();
	cnb_hide_edit_domain_upgrade_if_advanced();
	cnb_strip_beta_from_referrer();
	cnb_animate_saving();
	cnb_setup_toggle_label_clicks();
	cnb_switch_tab_from_history_listener();

	// Allow for tab switching to be dynamic
	cnb_init_tabs();

	if (typeof show_advanced_view_only_set !== 'undefined' && show_advanced_view_only_set && show_advanced_view_only_set === 1) {
		show_advanced_view_only()
	}
	// This needs to go AFTER the "advanced_view" check so a modal does not get additional (unneeded) "advanced" items
	if (typeof cnb_hide_on_modal_set !== 'undefined' && cnb_hide_on_modal_set === 1) {
		cnb_hide_on_modal();
	}

	// page: button-edit (conditions tabs)
	cnb_button_edit_conditions_change_listener();
	cnb_button_edit_conditions_hide_on_show_on_all_pages();

	cnb_delete_action();
	cnb_button_overview_modal();

	// page: Profile edit (and domain-upgrade, since it's in a modal there)
	cnb_profile_edit_setup();
});
