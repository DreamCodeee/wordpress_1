<?php

function cnb_admin_footer() {
    cnb_show_feedback_collection();
    cnb_show_api_traces();
    echo '</div> <!-- /wrap -->'; // This is started in cnb_admin_header
}

function cnb_show_feedback_collection() {
    global $cnb_cloud_hosting;

    $url = admin_url('admin.php');
    $upgrade_link =
        add_query_arg(
            array('page' => 'call-now-button-upgrade'),
            $url );
    $upgrade_url = esc_url( $upgrade_link );?>
    <div class="feedback-collection">
        <div class="cnb-clear"></div>
        <p class="cnb-url cnb-center"><a href="<?php echo CNB_WEBSITE; ?><?php cnb_utm_params("footer-links", "branding"); ?>" target="_blank">Call Now Button<?php if ($cnb_cloud_hosting) {
            echo '<span class="cnb_footer_beta">PREMIUM</span>';
          } ?></a></p>
        <p class="cnb-center">Version <?php echo CNB_VERSION; ?>
        <p class="cnb-center cnb-spacing">
            <a href="<?php echo CNB_SUPPORT;
            cnb_utm_params("footer-links", "support"); ?>" target="_blank" title="Support">Support</a> &middot;
            <a href="<?php echo CNB_SUPPORT; ?>feature-request/<?php cnb_utm_params("footer-links", "suggestions"); ?>"
               target="_blank" title="Feature Requests">Suggestions</a>
            <?php if (!$cnb_cloud_hosting) { ?>
            &middot;
            <strong><a href="<?php echo $upgrade_url ?>" title="Unlock features">Upgrade</a></strong>
            <?php } ?>
        </p>
    </div>
<?php
}

function cnb_show_api_traces() {
    global $cnb_options;
    if (isset($cnb_options['footer_show_traces']) && $cnb_options['footer_show_traces'] == 1 &&
        isset($cnb_options['advanced_view']) && $cnb_options['advanced_view'] == 1) {
        $cnb_remoted_traces = RemoteTracer::getInstance();
        if ( $cnb_remoted_traces ) {
            echo '<p>';
            $traces = $cnb_remoted_traces->getTraces();
            echo '<strong>' . count( $traces ) . '</strong> remote calls executed';
            $totaltime = 0.0;
            foreach ( $traces as $trace ) {
                $totaltime += (float) $trace->getTime();
            }
            echo ' in <strong>' . $totaltime . '</strong>sec:<br />';

            echo '<ul>';
            foreach ( $traces as $trace ) {
                echo '<li>';
                echo '<code>' . $trace->getEndpoint() . '</code> in <strong>' . $trace->getTime() . '</strong>sec';
                if ( $trace->isCacheHit() ) {
                    echo ' (from cache)';
                }
                echo '.</li>';
            }
            echo '</ul>';

            echo '</p>';
        }
    }
}
