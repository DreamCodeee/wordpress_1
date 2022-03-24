<?php

function cnb_domain_upgrade_upgrading($domain) {
    $busy_notice = new CnbNotice(
        'warning',
        '<p>We are processing the upgrade for <strong>'.esc_html($domain->name).'</strong>, please hold on.</p>
                    <p>This page will refresh in 2 seconds...</p>');
    CnbAdminNotices::get_instance()->renderNotice( $busy_notice );
    echo '<script>setTimeout(window.location.reload.bind(window.location), 2000);</script>';
}
