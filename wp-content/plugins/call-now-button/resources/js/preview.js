function getCleanDomain() {
    return document.location.hostname;
}

function createButtonFromData(formData) {
    let domainType = 'FREE';
    if (formData && formData.domain) {
        domainType = formData.domain.type;
    }

    return {
        "userId": "local",
        "domains": [
            {
                "id": "domain",
                "user": "local",
                "type": domainType,
                "name": getCleanDomain()
            }
        ],
        "buttons": [
            {
                "id": "button",
                "domain": getCleanDomain(),
                "domainId": "domain",
                "active": true,
                "name": "Live preview",
                "type": formData.cnb.type,
                "options": formData.cnb.options,
                "actions": Object.keys(formData.actions)
                ,
                "conditions": []
            }
        ],
        "actions": Object.values(formData.actions),
        "conditions": [],
        "options": {
            "debugMode": true,
            "cssLocation": "https://static.callnowbutton.com/css/main.css"
        }
    }
}

/**
 * Via https://dev.to/afewminutesofcode/how-to-convert-an-array-into-an-object-in-javascript-25a4
 *
 */
function convertArrayToObject (array, key) {
    const initialValue = {};
    return array.reduce((obj, item) => {
        return {
            ...obj,
            [item[key]]: item,
        };
    }, initialValue);
}

function livePreview() {
    const parsedData = jQuery('.cnb-container').serializeAssoc()

    // Find a button via JS (instead of via a form)
    if (typeof cnb_button !== 'undefined') {
        parsedData.cnb = cnb_button
    }

    // Ensure it is always visible
    parsedData.cnb.options.displayMode = 'ALWAYS';

    // Ensure all Actions are visible
    if (typeof cnb_actions !== 'undefined' && cnb_ignore_schedule) {
        cnb_actions = cnb_actions.map((item) => {
           item.schedule.showAlways = true;
           return item
        });
    }

    // Ensure a Multi button / Buttonbar gets its actions
    if (typeof cnb_actions !== 'undefined') {
        if (parsedData &&
            parsedData.actions
            && ((parsedData.actions[Object.keys(parsedData.actions)[0]]
            && parsedData.actions[Object.keys(parsedData.actions)[0]].actionType)
            || parsedData.actions.new)) {
            parsedData.actions = Object.assign(convertArrayToObject(cnb_actions, 'id'), parsedData.actions)
        } else {
            parsedData.actions = convertArrayToObject(cnb_actions, 'id')
        }
    }


    // Fix iconenabled (should be true/false instead of 0/1)
    if (parsedData.action_id && parsedData.actions &&
        parsedData.actions[parsedData.action_id]) {
        const iconEnabled = parsedData.actions[parsedData.action_id].iconEnabled;
        parsedData.actions[parsedData.action_id].iconEnabled = iconEnabled !== "0";
    }

    if (typeof cnb_domain !== 'undefined') {
        parsedData.domain = cnb_domain;
    }

    // Ensure a "new" Action (in case of a new SINGLE) gets an ID to work with
    if (parsedData && parsedData.actions && parsedData.actions.new) {
        parsedData.actions.new.id = "new"
    }

    // Ensure WhatsApp works
    if (parsedData.action_id && parsedData.actions &&
        parsedData.actions[parsedData.action_id] && parsedData.actions[parsedData.action_id].actionType === 'WHATSAPP') {
        const input = document.querySelector('#cnb_action_value_input_whatsapp');
        const iti = window.intlTelInputGlobals.getInstance(input);
        parsedData.actions[parsedData.action_id].actionValue = iti.getNumber()
    }

    // Delete old items
    jQuery('.cnb-single.call-now-button').remove()
    jQuery('.cnb-full.call-now-button').remove()
    jQuery('.cnb-multi.call-now-button').remove()
    // And ensure that the actual WordPress editing screen does not disappear with the Button
    jQuery('.call-now-button').css('display', 'block');

    window.CNB_DATA = createButtonFromData(parsedData);
    if (typeof CNB !== 'undefined') {
        const result = CNB.render();
        // If there is a Multibutton, expand it
        const multiButton = jQuery('.cnb-multi.call-now-button .cnb-floating-main')
        if (multiButton.length > 0) {
            multiButton[0].dispatchEvent(new window.CustomEvent('toggle'));
        }

        // Move the result into a new special div (if found)
        const button = jQuery('.cnb-single.call-now-button, .cnb-full.call-now-button, .cnb-multi.call-now-button').detach()
        const previewContainer = jQuery('#cnb-button-preview')
        if (previewContainer.length > 0) {
            previewContainer.append(button)
        }
        return result
    }
}

function initButtonEdit() {
    jQuery(() => {
        const idElement = jQuery('form.cnb-container :input[name="cnb[id]"]');
        if (idElement.length > 0 && !idElement.val().trim()) {
            return false;
        }

        // Load the required dependencies and render the preview once
        // All refreshes happen inside
        formToJson();
        livePreview()
        jQuery("form.cnb-container :input").on('change input', function() {
            livePreview()
        });
        // No need to call "livePreview", this is done via the ".done()" handler on cnb_delete_action()
        // jQuery('form.cnb-container a[data-ajax="true"]').on('change input', function() {});
    })
}


jQuery(() => {
    // Default, we show all actions
    window.cnb_ignore_schedule = true;
    initButtonEdit()
})
