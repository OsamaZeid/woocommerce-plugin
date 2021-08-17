<div class="tazapay-account-information">
<?php
global $woocommerce;
$countries_obj          = new WC_Countries();
$countries              = $countries_obj->__get('countries');

if(isset($_POST['submit'])){

        $apiRequestCall         = new WC_TazaPay_Gateway();

        $indbustype             = !empty($_POST['indbustype']) ? $_POST['indbustype'] : '';
        $first_name             = !empty($_POST['first_name']) ? $_POST['first_name'] : '';
        $last_name              = !empty($_POST['last_name']) ? $_POST['last_name'] : '';
        $business_name          = !empty($_POST['business_name']) ? $_POST['business_name'] : '';
        $phone_number           = !empty($_POST['phone_number']) ? $_POST['phone_number'] : '';
        $partners_customer_id   = !empty($_POST['partners_customer_id']) ? $_POST['partners_customer_id'] : '';
        $country                = !empty($_POST['country']) ? $_POST['country'] : '';
        $user_email             = !empty($_POST['email']) ? $_POST['email'] : '';

        $countryName            = WC()->countries->countries[$country];
        $phoneCode              = $apiRequestCall->getPhoneCode($country);

        if($business_name){
            $args = array(
                "email"                 => $user_email,
                "country"               => $countryName,
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
                "country"               => $countryName,
                "ind_bus_type"          => $indbustype,
                "partners_customer_id"  => $partners_customer_id
            );
        }        

        $api_endpoint = "/v1/user";
        $api_url = 'https://api-sandbox.tazapay.com/v1/user';

        $createUser = $apiRequestCall->request_apicall( $api_url, $api_endpoint, $args );

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
            
            ?>
            <div class="notice notice-success is-dismissible">
              <p><?php _e( $createUser->message, 'wc-tp-payment-gateway' ); ?></p>
            </div>
            <?php
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
echo '<h4>'.__('Create TazaPay Account','wc-tp-payment-gateway'). '</h4><hr>';

?>
<form method="post" name="accountform" action="#" class="tazapay_form">
<div class="container">    
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
    <input type="text" placeholder="Enter Email" name="email" id="email">
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
</div>