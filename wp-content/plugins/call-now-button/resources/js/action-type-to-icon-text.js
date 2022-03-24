/**
 * Get the default glyph for a particular action type.
 *
 * This should have the same content as the PHP function cnb_actiontype_to_icontext
 *
 * @param {string} actionType
 *
 * @returns {string}
 */
function cnbActiontypeToIcontext(actionType) {
    switch (actionType) {
        case 'ANCHOR': return 'anchor';
        case 'EMAIL': return 'email';
        case 'HOURS': return 'access_time';
        case 'LINK': return 'link';
        case 'MAP': return 'directions';
        case 'PHONE': return 'call';
        case 'SMS': return 'chat';
        case 'WHATSAPP': return 'whatsapp';
        default:
            return 'call';
    }
}

function updateIconText() {
    const type = jQuery('#cnb_action_type').val();
    const iconText = cnbActiontypeToIcontext(type)
    jQuery('#cnb_action_icon_text').val(iconText);
}

function initUpdateIconText() {
    jQuery('form.cnb-container :input').on('change input', function() {
        updateIconText()
    });
}

jQuery(() => {
    initUpdateIconText()
})
