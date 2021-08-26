<div class="wrap tazapay-account-information">
<?php
global $woocommerce;
$countries_obj          = new WC_Countries();
$countries              = $countries_obj->__get('countries');

$woocommerce_tz_tazapay_settings = get_option( 'woocommerce_tz_tazapay_settings' );

$user_email         = $woocommerce_tz_tazapay_settings['seller_email'];
$user               = get_user_by( 'email', $user_email );
$user_id            = $user->ID;
$account_id         = get_user_meta( $user_id, 'account_id', true );

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

        if(empty($user_email)){
          $user_email           = !empty($_POST['email']) ? $_POST['email'] : '';
        }else{
          $user_email           = $woocommerce_tz_tazapay_settings['seller_email'];
        }

        //$countryName          = WC()->countries->countries[$country];
        $phoneCode              = $apiRequestCall->getPhoneCode($country);

        if($business_name){
            $args = array(
                "email"                 => $user_email,
                "country"               => $country,
                "contact_code"          => $phoneCode,
                "contact_number"        => $phone_number,            
                "ind_bus_type"          => $indbustype,
                "business_name"         => $business_name,
                "partners_customer_id"  => $partners_customer_id
            );
        }else{
            $args = array(
                "email"                 => $user_email,
                "first_name"            => $first_name,
                "last_name"             => $last_name,
                "contact_code"          => $phoneCode,
                "contact_number"        => $phone_number,
                "country"               => $country,
                "ind_bus_type"          => $indbustype,
                "partners_customer_id"  => $partners_customer_id
            );
        }        

        $sandboxmode = $woocommerce_tz_tazapay_settings['sandboxmode'];

        if($sandboxmode == 'yes'){
            $api_url = 'https://api-sandbox.tazapay.com';
        }else{
            $api_url = 'https://api.tazapay.com';
        }

        //$api_url  = 'https://api-sandbox.tazapay.com/v1/user';
        $api_endpoint = "/v1/user";
        $api_url  = $api_url.'/v1/user';

        $createUser = $apiRequestCall->request_apicall( $api_url, $api_endpoint, $args, '' );

        if ( $createUser->status == 'success' ) {

            $randomuser_name  = $first_name.' '.$last_name;
            $user_name  = $apiRequestCall->random_username($randomuser_name);            

            $user_id    = username_exists( $user_name );
            if ( !$user_id and email_exists($user_email) == false ) {              
              $random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );            
              $user_id = wc_create_new_customer( $user_email, $user_name, $random_password );
            } else {
              $random_password = __('User already exists.  Password inherited.');
            }
            update_user_meta( $user_id, 'account_id', $createUser->data->account_id );
            update_user_meta( $user_id, 'first_name', $first_name );
            update_user_meta( $user_id, 'last_name', $last_name );
            update_user_meta( $user_id, 'business_name', $business_name );
            update_user_meta( $user_id, 'contact_code', $phoneCode );
            update_user_meta( $user_id, 'contact_number', $phone_number );
            update_user_meta( $user_id, 'billing_country', $country );
            update_user_meta( $user_id, 'ind_bus_type', $indbustype );
            update_user_meta( $user_id, 'partners_customer_id', $partners_customer_id );
            update_user_meta( $user_id, 'created', current_time( 'mysql' ) );
            update_user_meta( $user_id, 'user_type', 'seller' );
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

$account_id = get_user_meta( $user_id, 'account_id', true );

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

$first_name         = get_user_meta( $user_id, 'first_name', true );
$last_name          = get_user_meta( $user_id, 'last_name', true );
$buyer              = get_user_meta( $user_id, 'user_type', true );
$contact_code       = get_user_meta( $user_id, 'contact_code', true );
$contact_number     = get_user_meta( $user_id, 'contact_number', true );
$country_name       = get_user_meta( $user_id, 'billing_country', true );
$ind_bus_type       = get_user_meta( $user_id, 'ind_bus_type', true );
$business_name      = get_user_meta( $user_id, 'business_name', true );
$partners_customer  = get_user_meta( $user_id, 'partners_customer_id', true );
$created            = get_user_meta( $user_id, 'created', true );

$countryName    = WC()->countries->countries[$country_name];

echo '<h2>'.__('TazaPay Account Information','wc-tp-payment-gateway'). '</h2><hr>';
?>
<table class="wp-list-table widefat fixed striped table-view-list">
  <tr>
    <th><?php echo __('TazaPay Account UUID:','wc-tp-payment-gateway'); ?></th>
    <td><?php echo $account_id; ?></td>
  </tr>
  <tr>
    <th><?php echo __('User Type:','wc-tp-payment-gateway'); ?></th>
    <td><?php echo $buyer; ?></td>
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
    <th><?php echo __('Created At:','wc-tp-payment-gateway'); ?></th>
    <td><?php echo $created; ?></td>
  </tr>
</table>
<?php    
}
?>
</div>