<div class="wrap tazapay-account-information">
<?php
global $woocommerce, $wpdb;

$countries_obj          = new WC_Countries();
$countries              = $countries_obj->__get('countries');

$woocommerce_tz_tazapay_settings    = get_option( 'woocommerce_tz_tazapay_settings' );
$sandboxmode                        = $woocommerce_tz_tazapay_settings['sandboxmode'];
$tazapay_seller_type                = $woocommerce_tz_tazapay_settings['tazapay_seller_type'];

if($sandboxmode == 'yes'){
    $api_url      = 'https://api-sandbox.tazapay.com';
    $environment  = 'sandbox';
}else{
    $api_url = 'https://api.tazapay.com';
    $environment  = 'production';
}

if ( is_user_logged_in() && $tazapay_seller_type == 'multiseller' ) {

    $seller_user    = get_userdata(get_current_user_id());
    $user_email     = $seller_user->user_email;
    //echo implode(', ', $seller_user->roles);

}else{

    $user_email     = $woocommerce_tz_tazapay_settings['seller_email'];               

}

$tablename      = $wpdb->prefix.'tazapay_user';
$seller_results = $wpdb->get_results("SELECT * FROM $tablename WHERE email = '". $user_email ."' AND environment = '". $environment ."'");
$account_id     = $seller_results[0]->account_id;

if( empty($account_id) ){    

if(isset($_POST['submit'])){

        $apiRequestCall         = new WC_TazaPay_Gateway();

        $indbustype             = !empty($_POST['indbustype']) ? $_POST['indbustype'] : '';
        $first_name             = !empty($_POST['first_name']) ? $_POST['first_name'] : '';
        $last_name              = !empty($_POST['last_name']) ? $_POST['last_name'] : '';
        $business_name          = !empty($_POST['business_name']) ? $_POST['business_name'] : '';
        $phone_number           = !empty($_POST['phone_number']) ? $_POST['phone_number'] : '';
        $partners_customer_id   = !empty($_POST['partners_customer_id']) ? $_POST['partners_customer_id'] : '';
        $country                = !empty($_POST['country']) ? $_POST['country'] : '';
        $seller_email             = $woocommerce_tz_tazapay_settings['seller_email'];

        //$countryName          = WC()->countries->countries[$country];
        $phoneCode              = $apiRequestCall->getPhoneCode($country);

        if($business_name){
            $args = array(
                "email"                 => $seller_email,
                "country"               => $country,
                "contact_code"          => $phoneCode,
                "contact_number"        => $phone_number,            
                "ind_bus_type"          => $indbustype,
                "business_name"         => $business_name,
                "partners_customer_id"  => $partners_customer_id
            );
        }else{
            $args = array(
                "email"                 => $seller_email,
                "first_name"            => $first_name,
                "last_name"             => $last_name,
                "contact_code"          => $phoneCode,
                "contact_number"        => $phone_number,
                "country"               => $country,
                "ind_bus_type"          => $indbustype,
                "partners_customer_id"  => $partners_customer_id
            );
        }        

        //$api_url  = 'https://api-sandbox.tazapay.com/v1/user';
        $api_endpoint = "/v1/user";
        $api_url  = $api_url.'/v1/user';

        $createUser = $apiRequestCall->request_apicall( $api_url, $api_endpoint, $args, '' );

        if ( $createUser->status == 'success' ) {

            $tablename  = $wpdb->prefix.'tazapay_user';
            $account_id = $createUser->data->account_id;

            $wpdb->insert( $tablename, array(
                'account_id'           => $account_id, 
                'user_type'            => "seller",
                'email'                => $seller_email, 
                'first_name'           => $first_name,
                'last_name'            => $last_name, 
                'contact_code'         => $phoneCode, 
                'contact_number'       => $phone_number,
                'country'              => $country, 
                'ind_bus_type'         => $indbustype,
                'business_name'        => $business_name, 
                'partners_customer_id' => $partners_customer_id, 
                'environment'          => $environment,
                'created'              => current_time( 'mysql' ) ),
                array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) 
            );
            
            ?>
            <div class="notice notice-success is-dismissible">
              <p><?php _e( $createUser->message, 'wc-tp-payment-gateway' ); ?></p>
            </div>
            <?php
            wp_redirect( admin_url('?page=tazapay-signup-form'), 301 );
            exit();

        }else{

          $create_user_error_msg = "";
          $create_user_error_msg = "Create TazaPay User Error: ".$createUser->message;

           foreach ($createUser->errors as $key => $error) {
                    
              if (isset($error->code)) {
                  $create_user_error_msg .= "code: ".$error->code . '<br>';
              }
              if (isset($error->message)) {
                  $create_user_error_msg .= "Message: ".$error->message. '<br>';
              }
              if (isset($error->remarks)) {
                  $create_user_error_msg .= "Remarks: ".$error->remarks. '<br>';
              }
           }
          ?>
          <div class="notice notice-error is-dismissible">
            <p><?php _e( $create_user_error_msg, 'wc-tp-payment-gateway' ); ?></p>
          </div>
          <?php          
        }
} 


echo '<h2>'.__('Create TazaPay Account','wc-tp-payment-gateway'). '</h2><hr>';

//$account_id = get_user_meta( $user_id, 'account_id', true );

?>
<form method="post" name="accountform" action="#" class="tazapay_form">
<div class="container">
    <?php if(!empty($account_id)){?>
    <div class="seller-id">
    <?php
        echo sprintf(__("<span><strong>Seller Tazapay account UUID: </strong></span><span>%s</span>", 'wc-tp-payment-gateway'), $account_id);
    ?>   
    </div><br>
    <?php } ?>
    <label for="firstname"><b><?php echo __('Ind Bus Type','wc-tp-payment-gateway'); ?></b></label>
    <select id="indbustype" name="indbustype">
        <option value=""><?php echo __('Select Type','wc-tp-payment-gateway'); ?></option>
        <option value="Individual"><?php echo __('Individual','wc-tp-payment-gateway'); ?></option>
        <option value="Business"><?php echo __('Business','wc-tp-payment-gateway'); ?></option>
    </select>
    <div id="individual">
        <label for="firstname"><b><?php echo __('First Name','wc-tp-payment-gateway'); ?></b></label>
        <input type="text" placeholder="First Name" name="first_name" id="first_name">        
        <label for="lastname"><b><?php echo __('Last Name','wc-tp-payment-gateway'); ?></b></label>
        <input type="text" placeholder="Last Name" name="last_name" id="last_name">
    </div>
    <div id="business">
        <label for="businessname"><b><?php echo __('Business Name','wc-tp-payment-gateway'); ?></b></label>
        <input type="text" placeholder="Business Name" name="business_name" id="business_name">
    </div>
    <label for="email"><b><?php echo __('E-Mail','wc-tp-payment-gateway'); ?></b></label>
    <?php 
    if($user_email){
    ?>
    <input type="text" placeholder="Enter Email" name="email" id="email" value="<?php echo $user_email; ?>" readonly disabled>
    <?php } else { ?>
    <input type="text" placeholder="Enter Email" name="email" id="email">
    <?php 
    } 
    ?>
    <label for="phonenumber"><b><?php echo __('Phone Number','wc-tp-payment-gateway'); ?></b></label>
    <input type="text" placeholder="Phone Number" name="phone_number" id="phone_number">
    <label for="partnerscustomerid"><b><?php echo __('Partners Customer ID','wc-tp-payment-gateway'); ?></b></label>
    <input type="text" placeholder="Partners Customer ID" name="partners_customer_id" id="partners_customer_id">
    <label for="country"><b><?php echo __('Country','wc-tp-payment-gateway'); ?></b></label>
    <select id="country" name="country">
        <option value=""><?php echo __('Select country','wc-tp-payment-gateway'); ?></option>
        <?php
        foreach($countries as $country_code => $country){
            ?>
            <option value="<?php echo $country_code; ?>"><?php echo $country; ?></option>
        <?php
        }
        ?>
    </select>
    <input type="submit" class="registerbtn" name="submit" value="<?php echo __('Submit','wc-tp-payment-gateway'); ?>">
  </div>  
</form>
<?php
}else{

$first_name         = $seller_results[0]->first_name;
$last_name          = $seller_results[0]->last_name;
$user_type          = $seller_results[0]->user_type;
$contact_code       = $seller_results[0]->contact_code;
$contact_number     = $seller_results[0]->contact_number;
$country_name       = $seller_results[0]->country;
$ind_bus_type       = $seller_results[0]->ind_bus_type;
$business_name      = $seller_results[0]->business_name;
$partners_customer  = $seller_results[0]->partners_customer_id;
$created            = $seller_results[0]->created;
$environment        = $seller_results[0]->environment;

$countryName        = WC()->countries->countries[$country_name];

echo '<h2>'.__('TazaPay Account Information','wc-tp-payment-gateway'). '</h2><hr>';
?>
<table class="wp-list-table widefat fixed striped table-view-list">
  <tr>
    <th><?php echo __('TazaPay Account UUID:','wc-tp-payment-gateway'); ?></th>
    <td><?php echo $account_id; ?></td>
  </tr>
  <tr>
    <th><?php echo __('User Type:','wc-tp-payment-gateway'); ?></th>
    <td><?php echo $user_type; ?></td>
  </tr>
  <tr>
    <th><?php echo __('Entity Type:','wc-tp-payment-gateway'); ?></th>
    <td><?php echo $ind_bus_type; ?></td>
  </tr>
  <?php if($business_name) { ?>
  <tr>
    <th><?php echo __('Bussiness Name:','wc-tp-payment-gateway'); ?></th>
    <td><?php echo $business_name; ?></td>
  </tr>
  <?php }else{ ?>  
  <tr>
    <th><?php echo __('First Name:','wc-tp-payment-gateway'); ?></th>
    <td><?php echo $first_name; ?></td>
  </tr>
  <tr>
    <th><?php echo __('Last Name:','wc-tp-payment-gateway'); ?></th>
    <td><?php echo $last_name; ?></td>
  </tr>
  <?php } ?>
  <tr>
    <th><?php echo __('E-mail:','wc-tp-payment-gateway'); ?></th>
    <td><?php echo $user_email; ?></td>
  </tr>
  <tr>
    <th><?php echo __('Contact Code:','wc-tp-payment-gateway'); ?></th>
    <td><?php echo $contact_code; ?></td>
  </tr>
  <tr>
    <th><?php echo __('Contact Number:','wc-tp-payment-gateway'); ?></th>
    <td><?php echo $contact_number; ?></td>
  </tr>
  <tr>
    <th><?php echo __('Country:','wc-tp-payment-gateway'); ?></th>
    <td><?php echo $countryName; ?></td>
  </tr>
  <tr>
    <th><?php echo __('Partners Customer ID:','wc-tp-payment-gateway'); ?></th>
    <td><?php echo $partners_customer; ?></td>
  </tr>
   <tr>
    <th><?php echo __('Environment:','wc-tp-payment-gateway'); ?></th>
    <td><?php echo $environment; ?></td>
  </tr>
  <tr>
    <th><?php echo __('Created At:','wc-tp-payment-gateway'); ?></th>
    <td><?php echo $created; ?></td>
  </tr>
</table>
<?php    
}
?>
</div>