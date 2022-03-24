<?php
function cnb_get_plan($plans, $name) {
    foreach ($plans as $plan) {
        if ($plan->nickname === $name) {
            return $plan;
        }
    }
    return null;
}

function getProfileEditModal($additional_classes=null, $link_text='Enter or verify your information', $modal_header=null, $data_title='') {
    if (!$modal_header) {$modal_header=$link_text;}
    $url = admin_url('admin.php');
    $full_url =  add_query_arg(
        array(
            'TB_inline' => 'true',
            'inlineId' => 'cnb_admin_page_domain_upgrade_profile',
            'height' => '525'),
        $url );
    printf(
        '<a href="%1$s" title="%2$s" class="thickbox open-profile-details-modal %4$s" onclick="cnb_btn=\'%5$s\'">%3$s</a>',
        $full_url,
        esc_html__($modal_header),
        esc_html__($link_text),
        esc_attr($additional_classes),
        esc_attr__($data_title)
    );
}
function cnb_domain_upgrade_overview($domain, $user) {
    // Render upgrade form
    $plans = CnbAppRemotePayment::cnb_remote_get_plans();

    $active_currency = null;
    if ($user && !is_wp_error($user) && isset($user->stripeDetails) && !empty($user->stripeDetails->currency)) {
        $active_currency = $user->stripeDetails->currency;
    }

    $profile_set = false;
    if ($user && !is_wp_error($user) && isset($user->address) && !empty($user->address->country)) {
        $profile_set = true;
    }

    ?>
        <script>
            <?php if (!$profile_set) { ?>
            // Unless a profile hasn't been set yet, in which case, ensure we ask customers for that first
            jQuery(function() {
                jQuery('.button-upgrade').hide();
            });
            <?php } else { ?>
            // Hide the "Next" buttons, we already have a profile
            jQuery(function() {
                jQuery('.open-profile-details-modal').hide();
            });
            <?php } ?>

            <?php if ($active_currency) { ?>
            // We already know the currency, so a "select currency" tab menu makes no sense
            jQuery(function() {
                jQuery('.nav-tab-wrapper').hide();
            });
            <?php } ?>
        </script>
    <p>Your domain  is currently on the Premium <code><?php echo esc_html($domain->type) ?></code> plan.</p>

    <form id="wp_domain_upgrade" method="post">
        <input type="hidden" name="cnb_domain_id" id="cnb_domain_id" value="<?php echo esc_attr($domain->id) ?>">

        <h2>Select a plan that works best for <strong><?php echo esc_html($domain->name) ?></strong></h2>

        <h2 class="nav-tab-wrapper">
            <a href="#" data-cnb-currency="eur" class="cnb-currency-select cnb-currency-eur nav-tab<?php if($active_currency !== 'usd') {?> nav-tab-active<?php }?>">Euro (&euro;)</a>
            <a href="#" data-cnb-currency="usd" class="cnb-currency-select cnb-currency-usd nav-tab<?php if($active_currency === 'usd') {?> nav-tab-active<?php }?>">US Dollar ($)</a>
        </h2>
        <div class="cnb-message notice"><p class="cnb-error-message"></p></div>
        <div class="cnb-price-plans">
            <div class="currency-box currency-box-eur cnb-flexbox<?php if($active_currency !== 'usd') {?> currency-box-active<?php }?>">
              <?php if($domain->type === 'FREE') { ?>
                <div class="pricebox cnb-premium-free">
                    <h3 class="free">Premium (free)</h3>
                    <div class="benefit">Shows "Powered by" branding<br>Up to 20k monthly pageviews</div>
                    <div class="plan-amount"><span class="currency"></span><span class="euros">&nbsp;</span><span class="cents"></span><span class="timeframe"></span></div>
                    <div class="billingprice">&nbsp;</div>
                    <button class="button button-disabled" disabled="disabled">Currently active</button>
                </div>
              <?php } ?>
                <?php $plan = cnb_get_plan($plans, 'powered-by-eur-yearly'); ?>
                <div class="pricebox">
                    <h3 class="yearly"><span class="cnb-premium-label">PRO </span>Yearly</h3>
                    <div class="benefit">No "Powered by" branding<br>Up to 50k monthly pageviews</div>
                    <div class="plan-amount"><span class="currency">€</span><span class="euros">2</span><span class="cents">.49</span><span class="timeframe">/month</span></div>
                    <div class="billingprice">
                        Billed at €29.88 annually
                    </div>
                    <?php getProfileEditModal('button button-primary', 'Upgrade', 'Enter or verify your information', 'powered-by-eur-yearly'); ?>
                    <a class="button button-primary button-upgrade powered-by-eur-yearly" href="#" onclick="cnb_get_checkout('<?php echo esc_attr($plan->id) ?>')">Upgrade</a>
                </div>

                <?php $plan = cnb_get_plan($plans, 'powered-by-eur-monthly'); ?>
                <div class="pricebox">
                    <h3 class="monthly"><span class="cnb-premium-label">PRO </span>Monthly</h3>
                    <div class="benefit">No "Powered by" branding<br>Up to 50k monthly pageviews</div>
                    <div class="plan-amount"><span class="currency">€</span><span class="euros">4</span><span class="cents">.98</span><span class="timeframe">/month</span></div>
                    <div class="billingprice">
                        Billed monthly
                    </div>
                    <?php getProfileEditModal('button button-secondary', 'Upgrade', 'Enter or verify your information', 'powered-by-eur-monthly'); ?>
                    <a class="button button-secondary button-upgrade powered-by-eur-monthly" href="#" onclick="cnb_get_checkout('<?php echo esc_attr($plan->id) ?>')">Upgrade</a>
                </div>
            </div>
            <div class="currency-box currency-box-usd cnb-flexbox<?php if($active_currency === 'usd') {?> currency-box-active<?php }?>">
              <?php if($domain->type === 'FREE') { ?>
                <div class="pricebox cnb-premium-free">
                    <h3 class="free">Premium (free)</h3>
                    <div class="benefit">Shows "Powered by" branding<br>Up to 20k monthly pageviews</div>
                    <div class="plan-amount"><span class="currency"></span><span class="euros">&nbsp;</span><span class="cents"></span><span class="timeframe"></span></div>
                    <div class="billingprice">&nbsp;</div>
                    <button class="button button-disabled" disabled="disabled">Currently active</button>
                </div>
              <?php } ?>
                <?php $plan = cnb_get_plan($plans, 'powered-by-usd-yearly'); ?>
                <div class="pricebox">
                    <h3 class="yearly"><span class="cnb-premium-label">PRO </span>Yearly</h3>
                    <div class="benefit">No "Powered by" branding<br>Up to 50k monthly pageviews</div>
                    <div class="plan-amount"><span class="currency">$</span><span class="euros">2</span><span class="cents">.99</span><span class="timeframe">/month</span></div>
                    <div class="billingprice">
                        Billed at $34.88 annually
                    </div>
                    <?php getProfileEditModal('button button-primary', 'Upgrade', 'Enter or verify your information', 'powered-by-usd-yearly'); ?>
                    <a class="button button-primary button-upgrade powered-by-usd-yearly" href="#" onclick="cnb_get_checkout('<?php echo esc_attr($plan->id) ?>')">Upgrade</a>
                </div>
                <?php $plan = cnb_get_plan($plans, 'powered-by-usd-monthly'); ?>
                <div class="pricebox">
                    <h3 class="monthly"><span class="cnb-premium-label">PRO </span>Monthly</h3>
                    <div class="benefit">No "Powered by" branding<br>Up to 50k monthly pageviews</div>
                    <div class="plan-amount"><span class="currency">$</span><span class="euros">5</span><span class="cents">.98</span><span class="timeframe">/month</span></div>
                    <div class="billingprice">
                        Billed monthly
                    </div>
                    <?php getProfileEditModal('button button-secondary', 'Upgrade', 'Enter or verify your information', 'powered-by-usd-monthly'); ?>
                    <a class="button button-secondary button-upgrade powered-by-usd-monthly" href="#" onclick="cnb_get_checkout('<?php echo esc_attr($plan->id) ?>')">Upgrade</a>
                </div>
            </div>
        </div>
    </form>

    <div class="cnb-callout-bar"><p>Is your website enjoying more than 50k monthly pageviews?</p> <a class="button button-primary" href="https://callnowbutton.com/support/wordpress/contact/sales/" target="_blank">Contact us</a></div>

    <h3 class="cnb-center">Premium plans contain the following features:</h3>
    <div  class="cnb-center" style="margin-bottom:50px;">
        <div><b>&check;</b> Phone, Email, Location, WhatsApp, Links</div>
        <div><b>&check;</b> Multiple buttons</div>
        <div><b>&check;</b> Multibutton&trade; (expandable single button with multiple actions)</div>
        <div><b>&check;</b> Buttonbar&trade; (full width with multiple actions)</div>
        <div><b>&check;</b> Advanced page targeting options</div>
        <div><b>&check;</b> Scheduling</div>
        <div><b>&check;</b> And more!</div>
    </div>
<?php }
