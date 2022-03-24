<?php

use cnb\admin\models\Cnb_User;

function cnb_add_header_profile_edit() {
    echo 'Profile';
}

// List from https://gist.github.com/jylopez/7a3eb87e94981a579303a73cf72a5086
// Based on https://stripe.com/global
// Updated list with all countries minus EU santioned countries + Russia for difficult VAT requirements
// https://sanctionsmap.eu/#/main
function cnb_stripe_get_countries() {
    return array(
      array( "country" => 'Afghanistan', "code" => 'AF'),
      array( "country" => 'Åland Islands', "code" => 'AX'),
      array( "country" => 'Albania', "code" => 'AL'),
      array( "country" => 'Algeria', "code" => 'DZ'),
      array( "country" => 'American Samoa', "code" => 'AS'),
      array( "country" => 'Andorra', "code" => 'AD'),
      array( "country" => 'Angola', "code" => 'AO'),
      array( "country" => 'Anguilla', "code" => 'AI'),
      array( "country" => 'Antarctica', "code" => 'AQ'),
      array( "country" => 'Antigua and Barbuda', "code" => 'AG'),
      array( "country" => 'Argentina', "code" => 'AR'),
      array( "country" => 'Armenia', "code" => 'AM'),
      array( "country" => 'Aruba', "code" => 'AW'),
      array( "country" => 'Australia', "code" => 'AU'),
      array( "country" => 'Austria', "code" => 'AT'),
      array( "country" => 'Azerbaijan', "code" => 'AZ'),
      array( "country" => 'Bahamas', "code" => 'BS'),
      array( "country" => 'Bahrain', "code" => 'BH'),
      array( "country" => 'Bangladesh', "code" => 'BD'),
      array( "country" => 'Barbados', "code" => 'BB'),
      array( "country" => 'Belarus', "code" => 'BY'),
      array( "country" => 'Belgium', "code" => 'BE'),
      array( "country" => 'Belize', "code" => 'BZ'),
      array( "country" => 'Benin', "code" => 'BJ'),
      array( "country" => 'Bermuda', "code" => 'BM'),
      array( "country" => 'Bhutan', "code" => 'BT'),
      array( "country" => 'Bolivia', "code" => 'BO'),
      array( "country" => 'Bonaire, Sint Eustatius and Saba', "code" => 'BQ'),
      array( "country" => 'Bosnia and Herzegovina', "code" => 'BA'),
      array( "country" => 'Botswana', "code" => 'BW'),
      array( "country" => 'Bouvet Island', "code" => 'BV'),
      array( "country" => 'Brazil', "code" => 'BR'),
      array( "country" => 'British Indian Ocean Territory', "code" => 'IO'),
      array( "country" => 'Brunei Darussalam', "code" => 'BN'),
      array( "country" => 'Bulgaria', "code" => 'BG'),
      array( "country" => 'Burkina Faso', "code" => 'BF'),
      array( "country" => 'Burundi', "code" => 'BI'),
      array( "country" => 'Cabo Verde', "code" => 'CV'),
      array( "country" => 'Cambodia', "code" => 'KH'),
      array( "country" => 'Cameroon', "code" => 'CM'),
      array( "country" => 'Canada', "code" => 'CA'),
      array( "country" => 'Cayman Islands', "code" => 'KY'),
      array( "country" => 'Central African Republic', "code" => 'CF'),
      array( "country" => 'Chad', "code" => 'TD'),
      array( "country" => 'Chile', "code" => 'CL'),
      array( "country" => 'China', "code" => 'CN'),
      array( "country" => 'Christmas Island', "code" => 'CX'),
      array( "country" => 'Cocos (Keeling) Islands', "code" => 'CC'),
      array( "country" => 'Colombia', "code" => 'CO'),
      array( "country" => 'Comoros', "code" => 'KM'),
      array( "country" => 'Congo', "code" => 'CG'),
      array( "country" => 'Cook Islands', "code" => 'CK'),
      array( "country" => 'Costa Rica', "code" => 'CR'),
      array( "country" => 'Côte d\'Ivoire', "code" => 'CI'),
      array( "country" => 'Croatia', "code" => 'HR'),
      array( "country" => 'Cuba', "code" => 'CU'),
      array( "country" => 'Curaçao', "code" => 'CW'),
      array( "country" => 'Cyprus', "code" => 'CY'),
      array( "country" => 'Czechia', "code" => 'CZ'),
      array( "country" => 'Denmark', "code" => 'DK'),
      array( "country" => 'Djibouti', "code" => 'DJ'),
      array( "country" => 'Dominica', "code" => 'DM'),
      array( "country" => 'Dominican Republic', "code" => 'DO'),
      array( "country" => 'Ecuador', "code" => 'EC'),
      array( "country" => 'Egypt', "code" => 'EG'),
      array( "country" => 'El Salvador', "code" => 'SV'),
      array( "country" => 'Equatorial Guinea', "code" => 'GQ'),
      array( "country" => 'Eritrea', "code" => 'ER'),
      array( "country" => 'Estonia', "code" => 'EE'),
      array( "country" => 'Eswatini', "code" => 'SZ'),
      array( "country" => 'Ethiopia', "code" => 'ET'),
      array( "country" => 'Falkland Islands (Malvinas)', "code" => 'FK'),
      array( "country" => 'Faroe Islands', "code" => 'FO'),
      array( "country" => 'Fiji', "code" => 'FJ'),
      array( "country" => 'Finland', "code" => 'FI'),
      array( "country" => 'France', "code" => 'FR'),
      array( "country" => 'French Guiana', "code" => 'GF'),
      array( "country" => 'French Polynesia', "code" => 'PF'),
      array( "country" => 'French Southern Territories', "code" => 'TF'),
      array( "country" => 'Gabon', "code" => 'GA'),
      array( "country" => 'Gambia', "code" => 'GM'),
      array( "country" => 'Georgia', "code" => 'GE'),
      array( "country" => 'Germany', "code" => 'DE'),
      array( "country" => 'Ghana', "code" => 'GH'),
      array( "country" => 'Gibraltar', "code" => 'GI'),
      array( "country" => 'Greece', "code" => 'GR'),
      array( "country" => 'Greenland', "code" => 'GL'),
      array( "country" => 'Grenada', "code" => 'GD'),
      array( "country" => 'Guadeloupe', "code" => 'GP'),
      array( "country" => 'Guam', "code" => 'GU'),
      array( "country" => 'Guatemala', "code" => 'GT'),
      array( "country" => 'Guernsey', "code" => 'GG'),
      array( "country" => 'Guinea-Bissau', "code" => 'GW'),
      array( "country" => 'Guyana', "code" => 'GY'),
      array( "country" => 'Haiti', "code" => 'HT'),
      array( "country" => 'Heard Island and McDonald Islands', "code" => 'HM'),
      array( "country" => 'Holy See', "code" => 'VA'),
      array( "country" => 'Honduras', "code" => 'HN'),
      array( "country" => 'Hong Kong', "code" => 'HK'),
      array( "country" => 'Hungary', "code" => 'HU'),
      array( "country" => 'Iceland', "code" => 'IS'),
      array( "country" => 'India', "code" => 'IN'),
      array( "country" => 'Indonesia', "code" => 'ID'),
      array( "country" => 'Iraq', "code" => 'IQ'),
      array( "country" => 'Ireland', "code" => 'IE'),
      array( "country" => 'Isle of Man', "code" => 'IM'),
      array( "country" => 'Israel', "code" => 'IL'),
      array( "country" => 'Italy', "code" => 'IT'),
      array( "country" => 'Jamaica', "code" => 'JM'),
      array( "country" => 'Japan', "code" => 'JP'),
      array( "country" => 'Jersey', "code" => 'JE'),
      array( "country" => 'Jordan', "code" => 'JO'),
      array( "country" => 'Kazakhstan', "code" => 'KZ'),
      array( "country" => 'Kenya', "code" => 'KE'),
      array( "country" => 'Kiribati', "code" => 'KI'),
      array( "country" => 'Korea, Republic of', "code" => 'KR'),
      array( "country" => 'Kuwait', "code" => 'KW'),
      array( "country" => 'Kyrgyzstan', "code" => 'KG'),
      array( "country" => 'Lao', "code" => 'LA'),
      array( "country" => 'Latvia', "code" => 'LV'),
      array( "country" => 'Lebanon', "code" => 'LB'),
      array( "country" => 'Lesotho', "code" => 'LS'),
      array( "country" => 'Liberia', "code" => 'LR'),
      array( "country" => 'Liechtenstein', "code" => 'LI'),
      array( "country" => 'Lithuania', "code" => 'LT'),
      array( "country" => 'Luxembourg', "code" => 'LU'),
      array( "country" => 'Macao', "code" => 'MO'),
      array( "country" => 'Madagascar', "code" => 'MG'),
      array( "country" => 'Malawi', "code" => 'MW'),
      array( "country" => 'Malaysia', "code" => 'MY'),
      array( "country" => 'Maldives', "code" => 'MV'),
      array( "country" => 'Malta', "code" => 'MT'),
      array( "country" => 'Marshall Islands', "code" => 'MH'),
      array( "country" => 'Martinique', "code" => 'MQ'),
      array( "country" => 'Mauritania', "code" => 'MR'),
      array( "country" => 'Mauritius', "code" => 'MU'),
      array( "country" => 'Mayotte', "code" => 'YT'),
      array( "country" => 'Mexico', "code" => 'MX'),
      array( "country" => 'Micronesia', "code" => 'FM'),
      array( "country" => 'Moldova', "code" => 'MD'),
      array( "country" => 'Monaco', "code" => 'MC'),
      array( "country" => 'Mongolia', "code" => 'MN'),
      array( "country" => 'Montenegro', "code" => 'ME'),
      array( "country" => 'Montserrat', "code" => 'MS'),
      array( "country" => 'Morocco', "code" => 'MA'),
      array( "country" => 'Mozambique', "code" => 'MZ'),
      array( "country" => 'Namibia', "code" => 'NA'),
      array( "country" => 'Nauru', "code" => 'NR'),
      array( "country" => 'Nepal', "code" => 'NP'),
      array( "country" => 'Netherlands', "code" => 'NL'),
      array( "country" => 'New Caledonia', "code" => 'NC'),
      array( "country" => 'New Zealand', "code" => 'NZ'),
      array( "country" => 'Nicaragua', "code" => 'NI'),
      array( "country" => 'Niger', "code" => 'NE'),
      array( "country" => 'Nigeria', "code" => 'NG'),
      array( "country" => 'Niue', "code" => 'NU'),
      array( "country" => 'Norfolk Island', "code" => 'NF'),
      array( "country" => 'North Macedonia', "code" => 'MK'),
      array( "country" => 'Northern Mariana Islands', "code" => 'MP'),
      array( "country" => 'Norway', "code" => 'NO'),
      array( "country" => 'Oman', "code" => 'OM'),
      array( "country" => 'Pakistan', "code" => 'PK'),
      array( "country" => 'Palau', "code" => 'PW'),
      array( "country" => 'Palestine', "code" => 'PS'),
      array( "country" => 'Panama', "code" => 'PA'),
      array( "country" => 'Papua New Guinea', "code" => 'PG'),
      array( "country" => 'Paraguay', "code" => 'PY'),
      array( "country" => 'Peru', "code" => 'PE'),
      array( "country" => 'Philippines', "code" => 'PH'),
      array( "country" => 'Pitcairn', "code" => 'PN'),
      array( "country" => 'Poland', "code" => 'PL'),
      array( "country" => 'Portugal', "code" => 'PT'),
      array( "country" => 'Puerto Rico', "code" => 'PR'),
      array( "country" => 'Qatar', "code" => 'QA'),
      array( "country" => 'Réunion', "code" => 'RE'),
      array( "country" => 'Romania', "code" => 'RO'),
      array( "country" => 'Rwanda', "code" => 'RW'),
      array( "country" => 'Saint Barthélemy', "code" => 'BL'),
      array( "country" => 'Saint Helena, Ascension and Tristan da Cunha', "code" => 'SH'),
      array( "country" => 'Saint Kitts and Nevis', "code" => 'KN'),
      array( "country" => 'Saint Lucia', "code" => 'LC'),
      array( "country" => 'Saint Martin (French part)', "code" => 'MF'),
      array( "country" => 'Saint Pierre and Miquelon', "code" => 'PM'),
      array( "country" => 'Saint Vincent and the Grenadines', "code" => 'VC'),
      array( "country" => 'Samoa', "code" => 'WS'),
      array( "country" => 'San Marino', "code" => 'SM'),
      array( "country" => 'Sao Tome and Principe', "code" => 'ST'),
      array( "country" => 'Saudi Arabia', "code" => 'SA'),
      array( "country" => 'Senegal', "code" => 'SN'),
      array( "country" => 'Serbia', "code" => 'RS'),
      array( "country" => 'Seychelles', "code" => 'SC'),
      array( "country" => 'Sierra Leone', "code" => 'SL'),
      array( "country" => 'Singapore', "code" => 'SG'),
      array( "country" => 'Sint Maarten (Dutch part)', "code" => 'SX'),
      array( "country" => 'Slovakia', "code" => 'SK'),
      array( "country" => 'Slovenia', "code" => 'SI'),
      array( "country" => 'Solomon Islands', "code" => 'SB'),
      array( "country" => 'Somalia', "code" => 'SO'),
      array( "country" => 'South Africa', "code" => 'ZA'),
      array( "country" => 'South Georgia + South Sandwich Isl.', "code" => 'GS'),
      array( "country" => 'Spain', "code" => 'ES'),
      array( "country" => 'Sri Lanka', "code" => 'LK'),
      array( "country" => 'Suriname', "code" => 'SR'),
      array( "country" => 'Svalbard and Jan Mayen', "code" => 'SJ'),
      array( "country" => 'Sweden', "code" => 'SE'),
      array( "country" => 'Switzerland', "code" => 'CH'),
      array( "country" => 'Syrian Arab Republic', "code" => 'SY'),
      array( "country" => 'Taiwan, Province of China', "code" => 'TW'),
      array( "country" => 'Tajikistan', "code" => 'TJ'),
      array( "country" => 'Tanzania, United Republic of', "code" => 'TZ'),
      array( "country" => 'Thailand', "code" => 'TH'),
      array( "country" => 'Timor-Leste', "code" => 'TL'),
      array( "country" => 'Togo', "code" => 'TG'),
      array( "country" => 'Tokelau', "code" => 'TK'),
      array( "country" => 'Tonga', "code" => 'TO'),
      array( "country" => 'Trinidad and Tobago', "code" => 'TT'),
      array( "country" => 'Tunisia', "code" => 'TN'),
      array( "country" => 'Turkey', "code" => 'TR'),
      array( "country" => 'Turkmenistan', "code" => 'TM'),
      array( "country" => 'Turks and Caicos Islands', "code" => 'TC'),
      array( "country" => 'Tuvalu', "code" => 'TV'),
      array( "country" => 'Uganda', "code" => 'UG'),
      array( "country" => 'Ukraine', "code" => 'UA'),
      array( "country" => 'United Arab Emirates', "code" => 'AE'),
      array( "country" => 'United Kingdom', "code" => 'GB'),
      array( "country" => 'United States Minor Outlying Islands', "code" => 'UM'),
      array( "country" => 'United States of America', "code" => 'US'),
      array( "country" => 'Uruguay', "code" => 'UY'),
      array( "country" => 'Uzbekistan', "code" => 'UZ'),
      array( "country" => 'Vanuatu', "code" => 'VU'),
      array( "country" => 'Venezuela (Bolivarian Republic of)', "code" => 'VE'),
      array( "country" => 'Viet Nam', "code" => 'VN'),
      array( "country" => 'Virgin Islands (British)', "code" => 'VG'),
      array( "country" => 'Virgin Islands (U.S.)', "code" => 'VI'),
      array( "country" => 'Wallis and Futuna', "code" => 'WF'),
      array( "country" => 'Western Sahara', "code" => 'EH'),
      array( "country" => 'Yemen', "code" => 'YE'),
      array( "country" => 'Zambia', "code" => 'ZM')
    );
}

function cnb_admin_profile_edit_process_real($nonce, $profile) {
    if( $nonce && wp_verify_nonce( $nonce, 'cnb-profile-edit') ) {
        // If VAT is disabled, ensure the VAT number itself is blanked as well
        if ( $profile['euvatbusiness'] == 0 ) {
            $profile['taxIds'][0]['value'] = '';
        }
        unset( $profile['euvatbusiness'] );

        return CnbAppRemote::cnb_remote_update_user_info( $profile );
    }
    return null;
}

function cnb_admin_profile_edit_process() {
    $nonce = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
    $page_source = filter_input( INPUT_POST, 'page_source', FILTER_SANITIZE_STRING );
    $profile = filter_input(
        INPUT_POST,
        'user',
        FILTER_SANITIZE_STRING,
        FILTER_REQUIRE_ARRAY | FILTER_FLAG_NO_ENCODE_QUOTES);
    $result = cnb_admin_profile_edit_process_real($nonce, $profile);
    if($result) {
        // Create notification
        $notification = array();
        $notification[] = new CnbNotice('success', '<p>Your profile has been updated.</p>');
        $transient_id = 'cnb-' . wp_generate_uuid4();
        set_transient( $transient_id, $notification, HOUR_IN_SECONDS );

        if ($page_source === 'domain-upgrade') {
            return;
        }
        // Redirect
        // Create link
        $url           = admin_url( 'admin.php' );
        $redirect_link =
            add_query_arg(
                array(
                    'page'   => CNB_SLUG . '-profile',
                    'tid'    => $transient_id
                ),
                $url );
        $redirect_url  = esc_url_raw( $redirect_link );
        wp_safe_redirect( $redirect_url );
    } else {
        wp_die( __( 'Invalid nonce specified', CNB_NAME), __( 'Error', CNB_NAME), array(
            'response' 	=> 403,
            'back_link' => 'admin.php?page=' . CNB_SLUG . '-profile',
        ) );
    }

}

/**
 * @param false $modal
 *
 * @return Cnb_User|WP_Error
 */
function cnb_admin_page_profile_edit_render_form($modal = false) {
    $cnb_user = CnbAppRemote::cnb_remote_get_user_info();
    if (is_wp_error($cnb_user)) {
        return $cnb_user;
    }

    $cnb_user_stripe_verified = isset($cnb_user->taxIds[0]->verification->status) && $cnb_user->taxIds[0]->verification->status === 'verified';
    $cnb_user_stripe_verification_pending = isset($cnb_user->taxIds[0]->verification->status) && $cnb_user->taxIds[0]->verification->status === 'pending';
    ?>
<form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post" class="cnb-container cnb-settings-profile">
    <input type="hidden" name="page" value="call-now-button" />
    <input type="hidden" name="action" value="cnb_profile_edit" />
    <?php wp_nonce_field('cnb-profile-edit') ?>
    <?php
    // Modal likely means "via domain-upgrade", so we need to send users back there
    if ($modal) { ?><input type="hidden" name="page_source" value="domain-upgrade" /><?php } ?>

        <table class="form-table nav-tab-only">
        <tbody>
        <?php if(!$modal) { ?>
        <tr>
            <th colspan="2"><h2>Account owner</h2></th>
        </tr>
        <tr class="cnb_advanced_view">
            <th scope="row"><label for="user_id">ID</label></th>
            <td>
                <code><?php echo esc_html($cnb_user->id) ?></code>
            </td>
        </tr>
        <?php } ?>
        <tr>
            <th scope="row"><label for="user_email">Email</label></th>
            <td>
                <input type="text" id="user_email" name="user[email]" value="<?php echo esc_html($cnb_user->email ) ?>"
                       disabled class="regular-text ltr">
                <p class="description">Contact support to change your account email address.</p>
            </td>
        </tr>

        <tr>
            <th scope="row"><label for="user[name]">Full name<span class="cnb_required">*</span></label></th>
            <td>
                <input type="text" id="user[name]" name="user[name]" value="<?php echo esc_html($cnb_user->name) ?>" required="required"
                       class="regular-text ltr">
            </td>
        </tr>

        <tr>
            <th scope="row"><label for="user[companyName]">Company name<span class="cnb_required cnb_vat_companies_show" style="display:none">*</span></label></th>
            <td>
                <input type="text" id="user[companyName]" name="user[companyName]" value="<?php echo esc_html($cnb_user->companyName) ?>"
                       class="regular-text ltr cnb_vat_companies_required">
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="cnb_profile_country">Country<span class="cnb_required">*</span></label></th>
            <td>
                <label>
                    <select id="cnb_profile_country" class="select-menu" name="user[address][country]"
                            required="required">
                        <option value=""></option>
                        <?php
                        foreach ( cnb_stripe_get_countries() as $country ) {
                            $selected = '';
                            if (isset($cnb_user->address)) {
                                $selected = $country['code'] === $cnb_user->address->country ? ' selected="selected"' : '';
                            }
                            echo '<option value="' . $country['code'] . '"' . $selected . '>' . $country['country'] . '</option>' . "\n";
                        }
                        ?>
                    </select>
                </label>
            </td>
        </tr>

        <tr class="cnb_show_vat_toggle" style="display:none">
            <th scope="row"><label for="cnb-euvatbusiness">VAT registered business?</label></th>
            <td>
                <input type="hidden" name="user[euvatbusiness]" value="0">
                <input id="cnb-euvatbusiness" type="checkbox" name="user[euvatbusiness]" value="1"
                       <?php if(!empty($cnb_user->taxIds[0]->value)) { ?>checked="checked"<?php } ?>
                       class="ltr cnb_eu_values_only">
                <label for="cnb-euvatbusiness">Yes</label>
            </td>
        </tr>

        <tr class="cnb_vat_companies_show" style="display:none">
            <th scope="row"><label for="user[address][line1]">Address<span class="cnb_required">*</span></label></th>
            <td>
                <input type="text" id="user[address][line1]" name="user[address][line1]" value="<?php echo esc_html( isset($cnb_user->address) ? $cnb_user->address->line1: '' ) ?>"
                       class="regular-text ltr cnb_vat_companies_required cnb_eu_values_only">
            </td>
        </tr>
        <tr class="cnb_vat_companies_show" style="display:none">
            <th scope="row"><label for="user[address][line2]">Building, apartment, etc.</label></th>
            <td>
                <input type="text" id="user[address][line2]" name="user[address][line2]" value="<?php echo esc_html( isset($cnb_user->address) ? $cnb_user->address->line2 : '') ?>"
                       class="regular-text ltr cnb_eu_values_only">
            </td>
        </tr>

        <tr>
            <th scope="row"><label for="user[address][postalCode]"><span class="cnb_ie_only" style="display:none">Eircode/</span>Zip/Postal code<span class="cnb_required">*</span></label></th>
            <td>
                <input type="text" id="user[address][postalCode]" name="user[address][postalCode]"
                       value="<?php echo esc_html( isset($cnb_user->address) ? $cnb_user->address->postalCode : '' ) ?>"
                       class="regular-text ltr cnb_us_required cnb_vat_companies_required cnb_useu_values_only">
            </td>
        </tr>

        <tr>
            <th scope="row"><label for="user[address][city]">City<span class="cnb_required">*</span></label></th>
            <td>
                <input type="text" id="user[address][city]" name="user[address][city]" value="<?php echo esc_html( isset($cnb_user->address) ? $cnb_user->address->city : '' ) ?>"
                       required="required" class="regular-text ltr">
            </td>
        </tr>

        <tr class="cnb_us_show" style="display:none">
            <th scope="row"><label for="user[address][state]">State<span class="cnb_required">*</span></label></th>
            <td>
                <input type="text" id="user[address][state]" name="user[address][state]" value="<?php echo esc_html( isset($cnb_user->address) ? $cnb_user->address->state : '' ) ?>"
                       class="regular-text ltr cnb_us_required cnb_us_values_only">
            </td>
        </tr>



        <tr class="cnb_vat_companies_show" style="display:none">
            <th scope="row"><label for="cnb_profile_vat">VAT number<span class="cnb_required">*</span></label>
            </th>
            <td>
                <input id="cnb_profile_vat" type="text" name="user[taxIds][0][value]"
                       value="<?php echo esc_html( isset($cnb_user->taxIds[0]) ? $cnb_user->taxIds[0]->value : '' ) ?>"
                       class="regular-text ltr cnb_vat_companies_required cnb_eu_values_only">
                <input id="cnb_user_taxids_type" type="hidden" name="user[taxIds][0][type]" value="eu_vat"
                       class="regular-text ltr cnb_vat_companies_required cnb_eu_values_only">
                
                <?php
                if ($cnb_user_stripe_verified) {
                    echo '<p class="description"><span class="dashicons dashicons-saved"></span><em>Your VAT number is verified.</em></p>';
                } else if ($cnb_user_stripe_verification_pending) {
                    echo '<p class="description"><span class="dashicons dashicons-info"></span><em>Your VAT number is being verified.</em></p>';
                }
                ?>
            </td>
        </tr>

        <tr><th></th><td><?php submit_button('Next','primary large') ?></td></tr>
        </tbody>
    </table>
</form>
    <?php
    return $cnb_user;
}

function cnb_admin_page_profile_edit_render() {
    add_action('cnb_header_name', 'cnb_add_header_profile_edit');
    do_action('cnb_header');
    cnb_admin_page_profile_edit_render_form();
    do_action('cnb_footer');
}
