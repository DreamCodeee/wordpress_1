<?php
require_once dirname( __FILE__ ) . '/partials/admin-functions.php';
require_once dirname( __FILE__ ) . '/partials/admin-header.php';
require_once dirname( __FILE__ ) . '/partials/admin-footer.php';

function cnb_add_header_legacy_upgrade() {
    echo 'Unlock extra features';
}

function cnb_standard_plugin_promobox() { ?>
    <div class="cnb-body-column hide-on-mobile">
        <?php
        cnb_promobox(
            'grey',
            'Standard plugin',
            '<p>&check; One button<br>
                &check; Phone<br>
                &check; Circular (single action)<br>
                &check; Buttonbar (single action)<br>
                &check; Action label<br>
                </p>
                <hr>
                <p>
                &check; Placement options<br>
                &check; For mobile devices<br>
                &check; Include or exclude pages<br>
                &nbsp;
                </p>
                <hr>
                <p>
                &check; Google Analytics tracking<br>
                &check; Google Ads conversion tracking<br>
                </p>
                <hr>
                <p>
                &check; Adjust the button size<br>
                &check; Flexible z-index<br>
                &nbsp;
                </p>',
            'admin-plugins',
            '<strong>Free</strong>',
            'Currently active',
            'disabled'
        );
        ?>
    </div>
<?php }

function cnb_premium_plugin_promobox() { ?>
    <div class="cnb-body-column">
        <?php
        cnb_promobox(
            'blue',
            'Premium',
            '
                <p><strong>&check; Lots of buttons!</strong><br>
                &check; Phone, SMS/Text, Email, WhatsApp, Maps, URLs<br>
                &check; Circular button (multi action)<br>
                &check; Buttonbar (multi action)<br>
                &check; Actions labels<br>
                </p>
                <hr>
                <p>
                &check; Placement options<br>
                &check; For mobile and desktop/laptop<br>
                &check; Advanced page targeting<br>
                &check; Scheduling
                </p>
                <hr>
                <p>
                &check; Google Analytics tracking<br>
                &check; Google Ads conversion tracking<br>
                </p>
                <hr>
                <p>
                &check; Adjust the button size<br>
                &check; Flexible z-index<br>
                &check; Live button preview</p>
                <hr>
                <p class="cnb_align_center">From <strong>&euro;2.49/$2.99</strong> per month or <strong style="text-decoration:underline">FREE*</strong> with subtle "<em>powered by</em>" branding.</p>',
            'cloud',
            cnb_settings_email_activation_input(),
            'none'
        );
        ?>
    </div>
<?php }

function cnb_upgrade_faq() { ?>
    <div style="max-width:600px;margin:0 auto">
        <h1 class="cnb-center">FAQ</h1>
        <h3>Can I really get Premium for Free?</h3>
        <p>Yes. It's possible to access all premium features of the Call Now Button for free. No credit card is required. You only need an account for that. The difference with the paid Premium plans is that a small "Powered by Call Now Button" notice is added to your buttons and there's a monthly pageviews limit of 20k.</p>
        <h3>My website has more than 20k monthly pageviews. Can I still use Premium for Free?</h3>
        <p>The Free plan can be used by all websites. But once you hit the 20k pageview limit you will need to upgrade to PRO to keep using the plugin. PRO starts at &euro;2.49/$2.99 per month.</p>
        <h3>Does the Premium plan require an account?</h3>
        <p>Yes. We want the Call Now Button to be accessible to all website owners. Even those that do not have a WordPress powered website. The Premium version of the Call Now Button can be used by everyone. You can continue to manage your buttons from your WordPress instance, but you could also do this via our web app. And should you ever move to a different CMS, your button(s) will just move with you.</p>
        <h3>What is the "powered by" notice on the Free Premium plan?</h3>
        <p>Call Now Button Premium is available for a small yearly or annual fee, but it is also possible to get it for <em>free</em>. The free option introduces a small notice to your buttons that says "Powered by Call Now Button". It's very delicate and will not distract the the visitor from your key message.</p>
    </div>
<?php }

function cnb_admin_page_legacy_upgrade_render() {
    wp_enqueue_script(CNB_SLUG . '-settings');

    add_action('cnb_header_name', 'cnb_add_header_legacy_upgrade');
    do_action('cnb_header');
?>

    <div class="cnb-one-column-section">
      <div class="cnb-body-content">
        <div class="cnb-two-promobox-row">
            <?php cnb_standard_plugin_promobox() ?>
            <?php cnb_premium_plugin_promobox() ?>
        </div>
        <?php cnb_upgrade_faq() ?>
      </div>
    </div>
<hr>
    <?php
    do_action('cnb_footer');
}
