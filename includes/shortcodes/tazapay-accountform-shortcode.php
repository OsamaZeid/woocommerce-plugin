<div class="wrap tazapay-account-information">
  <?php
  global $woocommerce, $wpdb;

  $countries_obj = new WC_Countries();
  $countries     = $countries_obj->__get('countries');

  $woocommerce_tz_tazapay_settings  = get_option('woocommerce_tz_tazapay_settings');
  $sandboxmode                      = $woocommerce_tz_tazapay_settings['sandboxmode'];
  $tazapay_seller_type              = $woocommerce_tz_tazapay_settings['tazapay_seller_type'];
  $tazapay_multi_seller_plugin      = $woocommerce_tz_tazapay_settings['tazapay_multi_seller_plugin'];

  if ($sandboxmode == 'sandbox') {
    $api_url     = 'https://api-sandbox.tazapay.com';
    $environment = 'sandbox';
  } else {
    $api_url     = 'https://api.tazapay.com';
    $environment = 'production';
  }

  if (is_user_logged_in() && $tazapay_seller_type == 'multiseller' && !is_admin()) {
    $seller_user = get_userdata(get_current_user_id());
    $user_email  = $seller_user->user_email;
  } else {
    $user_email  = $woocommerce_tz_tazapay_settings['seller_email'];
  }

  $tablename = $wpdb->prefix . 'tazapay_user';
  $seller_results = $wpdb->get_results("SELECT * FROM $tablename WHERE email = '" . $user_email . "' AND environment = '" . $environment . "'");
  $db_account_id  = isset($seller_results[0]->account_id) ? $seller_results[0]->account_id : '';
  $apiRequestCall = new TCPG_Gateway();
  $getuserapi   = $apiRequestCall->tcpg_request_api_getuser($user_email);

  if (!empty($getuserapi->data->id)) {
    $account_id = $getuserapi->data->id;

    if (empty($db_account_id)) {

      $wpdb->insert(
        $tablename,
        array(
          'account_id'           => $account_id,
          'user_type'            => "seller",
          'email'                => $getuserapi->data->email,
          'first_name'           => $getuserapi->data->first_name,
          'last_name'            => $getuserapi->data->last_name,
          'contact_code'         => $getuserapi->data->contact_code,
          'contact_number'       => $getuserapi->data->contact_number,
          'country'              => $getuserapi->data->country_code,
          'ind_bus_type'         => $getuserapi->data->ind_bus_type,
          'business_name'        => $getuserapi->data->business_name,
          'partners_customer_id' => $getuserapi->data->customer_id,
          'environment'          => $environment,
          'created'              => current_time('mysql')
        ),
        array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
      );
    }
  }

  if (empty($db_account_id) || empty($getuserapi->data->id)) {

    if (isset($_POST['submit'])) {

      $indbustype           = sanitize_text_field($_POST['indbustype']);
      $first_name           = sanitize_text_field($_POST['first_name']);
      $last_name            = sanitize_text_field($_POST['last_name']);
      $business_name        = sanitize_text_field($_POST['business_name']);
      $phone_number         = sanitize_text_field($_POST['phone_number']);      
      $country              = sanitize_text_field($_POST['country']);
      $seller_email         = $user_email;

      $phoneCode = $apiRequestCall->tcpg_getphonecode($country);

      if ($business_name) {
        $args = array(
          "email" => $seller_email,
          "country" => $country,
          "contact_code" => $phoneCode,
          "contact_number" => $phone_number,
          "ind_bus_type" => $indbustype,
          "business_name" => $business_name,
        );
      } else {
        $args = array(
          "email" => $seller_email,
          "first_name" => $first_name,
          "last_name" => $last_name,
          "contact_code" => $phoneCode,
          "contact_number" => $phone_number,
          "country" => $country,
          "ind_bus_type" => $indbustype,
        );
      }

      $api_endpoint = "/v1/user";
      $api_url      = $api_url . '/v1/user';
      $createUser   = $apiRequestCall->tcpg_request_apicall($api_url, $api_endpoint, $args, '');

      if ($createUser->status == 'success') {

        $tablename = $wpdb->prefix . 'tazapay_user';
        $account_id = $createUser->data->account_id;

        $wpdb->insert(
          $tablename,
          array(
            'account_id' => $account_id,
            'user_type' => "seller",
            'email' => $seller_email,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'contact_code' => $phoneCode,
            'contact_number' => $phone_number,
            'country' => $country,
            'ind_bus_type' => $indbustype,
            'business_name' => $business_name,
            'environment' => $environment,
            'created' => current_time('mysql'),
          ),
          array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        $woocommerce_tz_tazapay_settings['seller_id'] = $account_id;
        update_option('woocommerce_tz_tazapay_settings', $woocommerce_tz_tazapay_settings);
  ?>
        <div class="notice notice-success is-dismissible">
          <p><?php esc_html_e($createUser->message, 'wc-tp-payment-gateway'); ?></p>
        </div>
        <?php
      } else {

        $create_user_error_msg = "";
        $create_user_error_msg = "Create Tazapay User Error: " . $createUser->message;

        foreach ($createUser->errors as $key => $error) {

          if (isset($error->code)) {
            $create_user_error_msg .= "code: " . $error->code . '<br>';
          }
          if (isset($error->message)) {
            $create_user_error_msg .= "Message: " . $error->message . '<br>';
          }
          if (isset($error->remarks)) {
            $create_user_error_msg .= "Remarks: " . $error->remarks . '<br>';
          }
        }
        ?>
        <div class="notice notice-error is-dismissible">
          <p><?php esc_html_e($create_user_error_msg, 'wc-tp-payment-gateway'); ?></p>
        </div>
    <?php
      }
    }
    ?>
    <h2><?php esc_html_e('Create Tazapay Account', 'wc-tp-payment-gateway'); ?></h2>
    <form method="post" name="accountform" action="" class="tazapay_form dokan-form-horizontal">
      <div class="container">
        <div class="dokan-form-group">
          <label for="firstname" class="dokan-w3 dokan-control-label"><b><?php esc_html_e('Ind Bus Type', 'wc-tp-payment-gateway'); ?></b></label>
          <div class="dokan-w5">
            <select id="indbustype" name="indbustype" class="dokan-form-control">
              <option value=""><?php esc_html_e('Select Type', 'wc-tp-payment-gateway'); ?></option>
              <option value="Individual"><?php esc_html_e('Individual', 'wc-tp-payment-gateway'); ?></option>
              <option value="Business"><?php esc_html_e('Business', 'wc-tp-payment-gateway'); ?></option>
            </select>
          </div>
        </div>
        <div id="individual">
          <div class="dokan-form-group">
            <label for="firstname" class="dokan-w3 dokan-control-label"><b><?php esc_html_e('First Name', 'wc-tp-payment-gateway'); ?></b></label>
            <div class="dokan-w5">
              <input type="text" placeholder="First Name" name="first_name" id="first_name">
            </div>
          </div>
          <div class="dokan-form-group">
            <label for="lastname" class="dokan-w3 dokan-control-label"><b><?php esc_html_e('Last Name', 'wc-tp-payment-gateway'); ?></b></label>
            <div class="dokan-w5">
              <input type="text" placeholder="Last Name" name="last_name" id="last_name">
            </div>
          </div>
        </div>
        <div id="business" class="dokan-form-group">
          <label for="businessname" class="dokan-w3 dokan-control-label"><b><?php esc_html_e('Business Name', 'wc-tp-payment-gateway'); ?></b></label>
          <div class="dokan-w5">
            <input type="text" placeholder="Business Name" name="business_name" id="business_name">
          </div>
        </div>
        <div class="dokan-form-group">
          <label for="email" class="dokan-w3 dokan-control-label"><b><?php esc_html_e('E-Mail', 'wc-tp-payment-gateway'); ?></b></label>
          <div class="dokan-w5">
            <?php
            if ($user_email) {
            ?>
              <input type="text" placeholder="Enter Email" name="email" id="email" value="<?php esc_html_e($user_email, 'wc-tp-payment-gateway'); ?>" readonly disabled>
            <?php } else { ?>
              <input type="text" placeholder="Enter Email" name="email" id="email">
            <?php
            }
            ?>
          </div>
        </div>
        <div class="dokan-form-group">
          <label for="phonenumber" class="dokan-w3 dokan-control-label"><b><?php esc_html_e('Phone Number', 'wc-tp-payment-gateway'); ?></b></label>
          <div class="dokan-w5">
            <input type="text" placeholder="Phone Number" name="phone_number" id="phone_number">
          </div>
        </div>
        <div class="dokan-form-group">
          <label for="country" class="dokan-w3 dokan-control-label"><b><?php esc_html_e('Country', 'wc-tp-payment-gateway'); ?></b></label>
          <div class="dokan-w5">
            <select id="country" name="country" class="dokan-form-control">
              <option value=""><?php esc_html_e('Select country', 'wc-tp-payment-gateway'); ?></option>
              <?php
              foreach ($countries as $country_code => $country) {
              ?>
                <option value="<?php esc_html_e($country_code, 'wc-tp-payment-gateway'); ?>"><?php esc_html_e($country, 'wc-tp-payment-gateway'); ?></option>
              <?php
              }
              ?>
            </select>
          </div>
        </div>
        <input type="submit" class="registerbtn dokan-btn dokan-btn-danger dokan-btn-theme" name="submit" value="<?php esc_html_e('Submit', 'wc-tp-payment-gateway'); ?>">
      </div>
    </form>
  <?php
  }

  if (!empty($db_account_id)) {

    $first_name = $seller_results[0]->first_name;
    $last_name = $seller_results[0]->last_name;
    $user_type = $seller_results[0]->user_type;
    $contact_code = $seller_results[0]->contact_code;
    $contact_number = $seller_results[0]->contact_number;
    $country_name = $seller_results[0]->country;
    $ind_bus_type = $seller_results[0]->ind_bus_type;
    $business_name = $seller_results[0]->business_name;
    $created = $seller_results[0]->created;
    $environment = $seller_results[0]->environment;
    $countryName = WC()->countries->countries[$country_name];
  ?>
    <table class="wp-list-table widefat fixed striped table-view-list">
      <tr>
        <th><?php esc_html_e('Tazapay Account UUID:', 'wc-tp-payment-gateway'); ?></th>
        <td><?php esc_html_e($account_id, 'wc-tp-payment-gateway'); ?></td>
      </tr>
      <tr>
        <th><?php esc_html_e('User Type:', 'wc-tp-payment-gateway'); ?></th>
        <td><?php esc_html_e($user_type, 'wc-tp-payment-gateway'); ?></td>
      </tr>
      <tr>
        <th><?php esc_html_e('Entity Type:', 'wc-tp-payment-gateway'); ?></th>
        <td><?php esc_html_e($ind_bus_type, 'wc-tp-payment-gateway'); ?></td>
      </tr>
      <?php if ($business_name) { ?>
        <tr>
          <th><?php esc_html_e('Bussiness Name:', 'wc-tp-payment-gateway'); ?></th>
          <td><?php esc_html_e($business_name, 'wc-tp-payment-gateway'); ?></td>
        </tr>
      <?php } else { ?>
        <tr>
          <th><?php esc_html_e('First Name:', 'wc-tp-payment-gateway'); ?></th>
          <td><?php esc_html_e($first_name, 'wc-tp-payment-gateway'); ?></td>
        </tr>
        <tr>
          <th><?php esc_html_e('Last Name:', 'wc-tp-payment-gateway'); ?></th>
          <td><?php esc_html_e($last_name, 'wc-tp-payment-gateway'); ?></td>
        </tr>
      <?php } ?>
      <tr>
        <th><?php esc_html_e('E-mail:', 'wc-tp-payment-gateway'); ?></th>
        <td><?php esc_html_e($user_email, 'wc-tp-payment-gateway'); ?></td>
      </tr>
      <tr>
        <th><?php esc_html_e('Contact Code:', 'wc-tp-payment-gateway'); ?></th>
        <td><?php esc_html_e($contact_code, 'wc-tp-payment-gateway'); ?></td>
      </tr>
      <tr>
        <th><?php esc_html_e('Contact Number:', 'wc-tp-payment-gateway'); ?></th>
        <td><?php esc_html_e($contact_number, 'wc-tp-payment-gateway'); ?></td>
      </tr>
      <tr>
        <th><?php esc_html_e('Country:', 'wc-tp-payment-gateway'); ?></th>
        <td><?php esc_html_e($countryName, 'wc-tp-payment-gateway'); ?></td>
      </tr>
      <tr>
        <th><?php esc_html_e('Environment:', 'wc-tp-payment-gateway'); ?></th>
        <td><?php esc_html_e($environment, 'wc-tp-payment-gateway'); ?></td>
      </tr>
      <tr>
        <th><?php esc_html_e('Created At:', 'wc-tp-payment-gateway'); ?></th>
        <td><?php esc_html_e($created, 'wc-tp-payment-gateway'); ?></td>
      </tr>
    </table>
  <?php
  }
  ?>
</div>