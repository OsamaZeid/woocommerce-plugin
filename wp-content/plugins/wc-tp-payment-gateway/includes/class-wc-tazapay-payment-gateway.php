<?php
class WC_TazaPay_Gateway extends WC_Payment_Gateway {

    /**
     * Class constructor
     */
    public function __construct() {

        $this->id = 'tz_tazapay'; // payment gateway plugin ID
        $this->icon = ''; // URL of the icon that will be displayed on checkout page near your gateway name
        $this->has_fields = true; // in case you need a custom form
        $this->method_title = 'TazaPay Gateway';
        $this->method_description = __('Collect payments from buyers, hold it until the seller/service provider fulfills their obligations before releasing the payment to them.', 'wc-tp-payment-gateway' ); // will be displayed on the options page
    
        // gateways can support subscriptions, refunds, saved payment methods,
        // but in this gateway we begin with simple payments
        $this->supports = array(
            'products'
        );
    
        // Method with all the options fields
        $this->init_form_fields();
    
        // Load the settings.
        $this->init_settings();
        $this->title = $this->get_option( 'title' );
        $this->description = $this->get_option( 'description' );
        $this->seller_name = $this->get_option( 'seller_name' );
        $this->seller_email = $this->get_option( 'seller_email' );
        $this->seller_type = $this->get_option( 'seller_type' );
        $this->seller_country = $this->get_option( 'seller_country' );
        $this->txn_type_escrow = $this->get_option( 'txn_type_escrow' );
        $this->release_mechanism = $this->get_option( 'release_mechanism' );
        $this->fee_paid_by = $this->get_option( 'fee_paid_by' );
        $this->fee_percentage = $this->get_option( 'fee_percentage' );
        $this->enabled = $this->get_option( 'enabled' );
        $this->sandboxmode = 'yes' === $this->get_option( 'sandboxmode' );
        $this->live_api_key = $this->sandboxmode ? $this->get_option( 'sandbox_api_key' ) : $this->get_option( 'live_api_key' );
        $this->live_api_secret_key = $this->sandboxmode ? $this->get_option( 'sandbox_api_secret_key' ) : $this->get_option( 'live_api_secret_key' );
    
        // This action hook saves the settings
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

        // add_filter( 'woocommerce_my_account_my_orders_columns', array( $this, 'tazapay_add_payment_column_to_myaccount' ) );
        // add_action( 'woocommerce_my_account_my_orders_column_pay-order', array( $this,'tazapay_add_pay_for_order_to_payment_column_myaccount' ) );

        //add_action( 'woocommerce_view_order', array( $this, 'tazapay_view_order_and_thankyou_page' ), 20 );
        add_action( 'woocommerce_thankyou', array( $this, 'tazapay_view_order_and_thankyou_page' ), 20 );
        add_filter( 'woocommerce_gateway_icon', array( $this, 'tazapay_woocommerce_icons'), 10, 2 );
        add_filter( 'woocommerce_available_payment_gateways', array( $this, 'tazapay_woocommerce_available_payment_gateways' ) );

        add_action( 'woocommerce_admin_order_data_after_order_details', array( $this, 'tazapay_order_meta_general' ) );
    }

    /*
    * Plugin options
    */
    public function init_form_fields(){

        global $woocommerce;
        $countries_obj   = new WC_Countries();
        $countries       = $countries_obj->__get('countries');


        $text1 = __( 'Place the payment gateway in sandbox mode using sandbox API keys', 'wc-tp-payment-gateway' );
        $text2 = __( 'Request credentials', 'wc-tp-payment-gateway' );
        $text3 = __( 'Request credentials for accepting payments via TazaPay', 'wc-tp-payment-gateway' );

        $this->form_fields = array(
            'enabled' => array(
                'title'       => __('Enable/Disable', 'wc-tp-payment-gateway' ),
                'label'       => __('Enable TazaPay Gateway', 'wc-tp-payment-gateway' ),
                'type'        => 'checkbox',
                'description' => __('Enable TazaPay payment method', 'wc-tp-payment-gateway' ),
                'default'     => 'no'
            ),
            'title' => array(
                'title'       => __('Title', 'wc-tp-payment-gateway' ),
                'type'        => 'text',
                'description' => __('Backend payment method title', 'wc-tp-payment-gateway' ),
                'default'     => 'TazaPay - Escrow',
            ),
            'description' => array(
                'title'       => __('Transaction Description', 'wc-tp-payment-gateway' ),
                'type'        => 'textarea',
                'description' => __('A short synopsis of the type of goods/service', 'wc-tp-payment-gateway' ),
                'default'     => 'Pay with your TazaPay via our super-cool payment gateway.',
            ),            
            'sandboxmode' => array(
                'title'       => __('Sandbox mode', 'wc-tp-payment-gateway' ),
                'label'       => __('Enable Sandbox Mode', 'wc-tp-payment-gateway' ),
                'type'        => 'checkbox',
                'description' => __( $text1.'<br><br><a href="https://share.hsforms.com/1RcEF-LvgQv-6fArLsYSRwA4qumh" class="button-primary" target="_blank" title="Request credentials for accepting payments via Tazapay">'.$text2.'</a><p>'.$text3.'</p>', 'wc-tp-payment-gateway' ),
                'default'     => 'yes'
            ),
            'sandbox_api_key' => array(
                'title'       => __('Sandbox API Key', 'wc-tp-payment-gateway' ),
                'type'        => 'password',
                'description' => __('TazaPay sandbox API Key', 'wc-tp-payment-gateway' )
            ),
            'sandbox_api_secret_key' => array(
                'title'       => __('Sandbox API Secret Key', 'wc-tp-payment-gateway' ),
                'type'        => 'password',
                'description' => __('TazaPay sandbox API Secret Key', 'wc-tp-payment-gateway' )
            ),
            'live_api_key' => array(
                'title'       => __('Live API Key', 'wc-tp-payment-gateway' ),
                'type'        => 'password',
                'description' => __('TazaPay Live API Key', 'wc-tp-payment-gateway' )
            ),
            'live_api_secret_key' => array(
                'title'       => __('Live API Secret Key', 'wc-tp-payment-gateway' ),
                'type'        => 'password',
                'description' => 'TazaPay Live API Secret Key'
            ),
            'seller_name' => array(
                'title'       => __('Name', 'wc-tp-payment-gateway' ),
                'type'        => 'text',
                'description' => __('Seller\'s Name', 'wc-tp-payment-gateway' ),
                'required'    => true,
            ),
            'seller_email' => array(
                'title'       => __('Email', 'wc-tp-payment-gateway' ),
                'type'        => 'text',
                'description' => __('Seller\'s Email', 'wc-tp-payment-gateway' ),
                'required'    => true,
            ),
            'seller_type' => array(
                'title'       => __('Entity Type', 'wc-tp-payment-gateway' ),
                'type'        => 'select',
                'options'     => array(
                    'Individual' => __('Individual', 'wc-tp-payment-gateway' ),
                    'Business' => __('Business', 'wc-tp-payment-gateway' )
                ),
                'description'=> __('User\'s entity type', 'wc-tp-payment-gateway' )
            ),
            'seller_country' => array(
                'title'       => __('Country', 'wc-tp-payment-gateway' ),
                'type'        => 'select',
                'options'     => $countries,
                'description' => __('User\'s country', 'wc-tp-payment-gateway' )
            ),
            'txn_type_escrow' => array(
                'title'       => __('Transaction Type', 'wc-tp-payment-gateway' ),
                'type'        => 'select',
                'options'     => array(
                    'service' => __('Service', 'wc-tp-payment-gateway' ),
                    'goods' => __('Goods', 'wc-tp-payment-gateway' )
                ),
                'description' => __('Type of underlying trade', 'wc-tp-payment-gateway' )
            ),
            'release_mechanism' => array(
                'title'       => __('Release Mechanism', 'wc-tp-payment-gateway' ),
                'type'        => 'select',
                'options'     => array(
                    'tazapay' => __('Tazapay', 'wc-tp-payment-gateway' ),
                    'marketplace' => __('Marketplace', 'wc-tp-payment-gateway' )
                ),
                'description' => __('Specify who control release verification', 'wc-tp-payment-gateway' )
            ),
            'fee_paid_by' => array(
                'title'       => __('Fee Paid By', 'wc-tp-payment-gateway' ),
                'type'        => 'select',
                'options'     => array(
                    'seller' => __('Seller', 'wc-tp-payment-gateway' ),
                    'buyer' => __('Buyer', 'wc-tp-payment-gateway' ),
                ),
                'description' => __('Tazapay account uuid. If empty; contracted value will get applied', 'wc-tp-payment-gateway' )
            ),
            // 'fee_percentage' => array(
            //     'title'       => 'Fee Percentage',
            //     'type'        => 'text',
            //     'required'    => true,
            //     'description' => 'Fee percentage between 0 to 100'
            // ),
        );  
    }

    /*
    * Get phone code
    * @return string
    */
    public function getPhoneCode($countryCode)
    {
        $countryCodeArray = [
            'AD'=>'376',
            'AE'=>'971',
            'AF'=>'93',
            'AG'=>'1268',
            'AI'=>'1264',
            'AL'=>'355',
            'AM'=>'374',
            'AN'=>'599',
            'AO'=>'244',
            'AQ'=>'672',
            'AR'=>'54',
            'AS'=>'1684',
            'AT'=>'43',
            'AU'=>'61',
            'AW'=>'297',
            'AZ'=>'994',
            'BA'=>'387',
            'BB'=>'1246',
            'BD'=>'880',
            'BE'=>'32',
            'BF'=>'226',
            'BG'=>'359',
            'BH'=>'973',
            'BI'=>'257',
            'BJ'=>'229',
            'BL'=>'590',
            'BM'=>'1441',
            'BN'=>'673',
            'BO'=>'591',
            'BR'=>'55',
            'BS'=>'1242',
            'BT'=>'975',
            'BW'=>'267',
            'BY'=>'375',
            'BZ'=>'501',
            'CA'=>'1',
            'CC'=>'61',
            'CD'=>'243',
            'CF'=>'236',
            'CG'=>'242',
            'CH'=>'41',
            'CI'=>'225',
            'CK'=>'682',
            'CL'=>'56',
            'CM'=>'237',
            'CN'=>'86',
            'CO'=>'57',
            'CR'=>'506',
            'CU'=>'53',
            'CV'=>'238',
            'CX'=>'61',
            'CY'=>'357',
            'CZ'=>'420',
            'DE'=>'49',
            'DJ'=>'253',
            'DK'=>'45',
            'DM'=>'1767',
            'DO'=>'1809',
            'DZ'=>'213',
            'EC'=>'593',
            'EE'=>'372',
            'EG'=>'20',
            'ER'=>'291',
            'ES'=>'34',
            'ET'=>'251',
            'FI'=>'358',
            'FJ'=>'679',
            'FK'=>'500',
            'FM'=>'691',
            'FO'=>'298',
            'FR'=>'33',
            'GA'=>'241',
            'GB'=>'44',
            'GD'=>'1473',
            'GE'=>'995',
            'GH'=>'233',
            'GI'=>'350',
            'GL'=>'299',
            'GM'=>'220',
            'GN'=>'224',
            'GQ'=>'240',
            'GR'=>'30',
            'GT'=>'502',
            'GU'=>'1671',
            'GW'=>'245',
            'GY'=>'592',
            'HK'=>'852',
            'HN'=>'504',
            'HR'=>'385',
            'HT'=>'509',
            'HU'=>'36',
            'ID'=>'62',
            'IE'=>'353',
            'IL'=>'972',
            'IM'=>'44',
            'IN'=>'91',
            'IQ'=>'964',
            'IR'=>'98',
            'IS'=>'354',
            'IT'=>'39',
            'JM'=>'1876',
            'JO'=>'962',
            'JP'=>'81',
            'KE'=>'254',
            'KG'=>'996',
            'KH'=>'855',
            'KI'=>'686',
            'KM'=>'269',
            'KN'=>'1869',
            'KP'=>'850',
            'KR'=>'82',
            'KW'=>'965',
            'KY'=>'1345',
            'KZ'=>'7',
            'LA'=>'856',
            'LB'=>'961',
            'LC'=>'1758',
            'LI'=>'423',
            'LK'=>'94',
            'LR'=>'231',
            'LS'=>'266',
            'LT'=>'370',
            'LU'=>'352',
            'LV'=>'371',
            'LY'=>'218',
            'MA'=>'212',
            'MC'=>'377',
            'MD'=>'373',
            'ME'=>'382',
            'MF'=>'1599',
            'MG'=>'261',
            'MH'=>'692',
            'MK'=>'389',
            'ML'=>'223',
            'MM'=>'95',
            'MN'=>'976',
            'MO'=>'853',
            'MP'=>'1670',
            'MR'=>'222',
            'MS'=>'1664',
            'MT'=>'356',
            'MU'=>'230',
            'MV'=>'960',
            'MW'=>'265',
            'MX'=>'52',
            'MY'=>'60',
            'MZ'=>'258',
            'NA'=>'264',
            'NC'=>'687',
            'NE'=>'227',
            'NG'=>'234',
            'NI'=>'505',
            'NL'=>'31',
            'NO'=>'47',
            'NP'=>'977',
            'NR'=>'674',
            'NU'=>'683',
            'NZ'=>'64',
            'OM'=>'968',
            'PA'=>'507',
            'PE'=>'51',
            'PF'=>'689',
            'PG'=>'675',
            'PH'=>'63',
            'PK'=>'92',
            'PL'=>'48',
            'PM'=>'508',
            'PN'=>'870',
            'PR'=>'1',
            'PT'=>'351',
            'PW'=>'680',
            'PY'=>'595',
            'QA'=>'974',
            'RO'=>'40',
            'RS'=>'381',
            'RU'=>'7',
            'RW'=>'250',
            'SA'=>'966',
            'SB'=>'677',
            'SC'=>'248',
            'SD'=>'249',
            'SE'=>'46',
            'SG'=>'65',
            'SH'=>'290',
            'SI'=>'386',
            'SK'=>'421',
            'SL'=>'232',
            'SM'=>'378',
            'SN'=>'221',
            'SO'=>'252',
            'SR'=>'597',
            'ST'=>'239',
            'SV'=>'503',
            'SY'=>'963',
            'SZ'=>'268',
            'TC'=>'1649',
            'TD'=>'235',
            'TG'=>'228',
            'TH'=>'66',
            'TJ'=>'992',
            'TK'=>'690',
            'TL'=>'670',
            'TM'=>'993',
            'TN'=>'216',
            'TO'=>'676',
            'TR'=>'90',
            'TT'=>'1868',
            'TV'=>'688',
            'TW'=>'886',
            'TZ'=>'255',
            'UA'=>'380',
            'UG'=>'256',
            'US'=>'1',
            'UY'=>'598',
            'UZ'=>'998',
            'VA'=>'39',
            'VC'=>'1784',
            'VE'=>'58',
            'VG'=>'1284',
            'VI'=>'1340',
            'VN'=>'84',
            'VU'=>'678',
            'WF'=>'681',
            'WS'=>'685',
            'XK'=>'381',
            'YE'=>'967',
            'YT'=>'262',
            'ZA'=>'27',
            'ZM'=>'260',
            'ZW'=>'263'
        ];
        $phoneCode = $countryCodeArray[$countryCode];
        return $phoneCode;
    }

    /**
     * You will need it if you want your custom form
     */
    public function payment_fields() {

        if ( $this->description ) {
            // you can instructions for test mode.
            if ( $this->sandboxmode ) {
                //$this->description = 'Don\'t have TazaPay account yet ? <a href="javascript:void(0);" onclick="tazapaySignupnow()" class="tazapay-signupnow">Sign up now</a>';
                $this->description  = trim( $this->description );
            }
            echo wpautop( wp_kses_post( $this->description ) );

            $payment_methods = TAZAPAY_PUBLIC_ASSETS_DIR . "images/payment_methods.svg";
            echo '<img src=' .$payment_methods. ' alt="tazapay" style="max-height: inherit;float: none;margin-left: auto; width: 100%;height: auto;margin-top: 20px;"/>';
        }

        /*echo '<fieldset id="wc-' . esc_attr( $this->id ) . '-email-form" class="wc-email-form wc-payment-form" style="background:transparent;">';    
        do_action( 'woocommerce_payment_form_start', $this->id );
        ?>
        <div class="form-row form-row-wide">
            <label><?php echo __('TazaPay E-mail', 'wc-tp-payment-gateway'); ?><span class="required">*</span></label>
            <input id="email" type="text" name="tazapay_email" placeholder="Enter your TazaPay E-mail" autocomplete="off">
        </div>
        <div class="clear"></div>  
        <?php      
        do_action( 'woocommerce_payment_form_end', $this->id );    
        echo '<div class="clear"></div></fieldset>';*/
            
    }

    /*
    * Fields validation
    */
    public function validate_fields() {

        // if( empty( $_POST[ 'tazapay_email' ] ) ) {
        //     wc_add_notice( 'TazaPay E-mail is required!', 'error' );
        //     return false;
        // }        
        return true;

    }

    /*
    * Api call
    */
    public function request_apicall( $api_url, $api_endpoint, $args, $order_id ) {

        /*
        * generate salt value
        */
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789`~!@#$%^&*()-=_+';
        $l     = strlen($chars) - 1;
        $salt  = '';

        for ($i = 0; $i < 8; ++$i) {
            $salt .= $chars[rand(0, $l)];
        }

        $method      = "POST";
        $APIEndpoint = $api_endpoint;
        $timestamp   = time();
        $apiKey      = $this->live_api_key;
        $apiSecret   = $this->live_api_secret_key;

        /*
        * generate to_sign
        * to_sign = toUpperCase(Method) + Api-Endpoint + Salt + Timestamp + API-Key + API-Secret
        */
        $to_sign = $method.$APIEndpoint.$salt.$timestamp.$apiKey.$apiSecret;

        /*
        * generate signature
        * $hmacSHA256 is generate hmacSHA256
        * $signature is convert hmacSHA256 into base64 encode
        * in document: signature = Base64(hmacSHA256(to_sign, API-Secret))
        */
        $hmacSHA256 = hash_hmac('sha256', $to_sign, $apiSecret);
        $signature  = base64_encode($hmacSHA256);

        $json = json_encode($args);
        $curl = curl_init();

        curl_setopt_array(
            $curl,
            [
            CURLOPT_URL => $api_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLINFO_HEADER_OUT => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => [
                'accesskey: '.$apiKey,
                'salt: '.$salt,
                'signature: '.$signature,
                'timestamp: '.$timestamp,
                'Content-Type: application/json'
            ],
            ]
        );
        $response   = curl_exec($curl);

        $upload_dir = wp_upload_dir();

        $filename = $upload_dir['basedir'].'/response.txt';
        $responsetxt = 'Oder Id:'.$order_id.'-'.$response."\n";

        if (file_exists($filename)) {

           $handle = fopen($filename, 'a') or die('Cannot open file:  '.$filename);
           fwrite($handle, $responsetxt);

        } else {

            $log_file = "activity/".$user.".txt";
            $handle = fopen($filename, "w") or die("Unable to open file!");
            fwrite($handle, $responsetxt);
        }
        fclose($handle);

        $api_array = json_decode( $response );        
        curl_close($curl);
        
        return $api_array;
    }

    /*
    * Random username
    */
    public function random_username($string) {
        $pattern    = " ";
        $firstPart  = strstr(strtolower($string), $pattern, true);
        $secondPart = substr(strstr(strtolower($string), $pattern, false), 0,3);
        $nrRand     = rand(0, 100);        
        $username   = trim($firstPart).trim($secondPart).trim($nrRand);
        return $username;
    }

    /*
    * We're processing the payments here
    */
    public function process_payment( $order_id ) {

        global $woocommerce;

        // we need it to get any order detailes
        $order          = wc_get_order( $order_id );
        //$countryName    = WC()->countries->countries[$order->get_billing_country()];
        $phoneCode      = $this->getPhoneCode($order->get_billing_country());

        $args = array(
            "email"                 => $order->get_billing_email(),
            "first_name"            => $order->get_billing_first_name(),
            "last_name"             => $order->get_billing_last_name(),
            "contact_code"          => $phoneCode,
            "contact_number"        => $order->get_billing_phone(),
            "country"               => $order->get_billing_country(),
            "ind_bus_type"          => $this->seller_type,
            "partners_customer_id"  => "1232131"
        );

        $userid              = get_current_user_id();
        $user_info           = get_userdata( $userid );
        $register_user_email = $user_info->user_email;

        $user_name    = $order->get_billing_first_name().' '.$order->get_billing_last_name();
        $user_name    = $this->random_username($user_name);
        $user_email   = $order->get_billing_email();
        $user_id      = username_exists( $user_name );

        $api_endpoint = "/v1/user";
        $api_url      = 'https://api-sandbox.tazapay.com/v1/user';


        if($register_user_email){

            $result   = $this->request_apicall( $api_url, $api_endpoint, $args, $order_id );

            update_user_meta( $userid, 'account_id', $result->data->account_id );
            update_user_meta( $userid, 'first_name', $order->get_billing_first_name() );
            update_user_meta( $userid, 'last_name', $order->get_billing_last_name() );
            update_user_meta( $userid, 'contact_code', $phoneCode );
            update_user_meta( $userid, 'contact_number', $order->get_billing_phone() );
            update_user_meta( $userid, 'billing_country', $order->get_billing_country() );
            update_user_meta( $userid, 'ind_bus_type', $this->seller_type );                
            update_user_meta( $userid, 'created', current_time( 'mysql' ) );
            update_user_meta( $userid, "billing_first_name", $order->get_billing_first_name() );
            update_user_meta( $userid, "billing_last_name", $order->get_billing_last_name() );
            update_user_meta( $userid, "billing_email", $order->get_billing_email() );
            update_post_meta( $order_id, '_customer_user', $userid );
        }
        else
        {

            $result   = $this->request_apicall( $api_url, $api_endpoint, $args, $order_id );
        }

        $create_user_error_msg  = "";
        $create_user_error_msg  = $result->message;
        $create_user_error_msg .= ", TazaPay Email : ".$order->get_billing_email();
        $create_user_error_msg .= ", TazaPay Account UUID : ". $result->data->account_id;

        foreach ($result->errors as $key => $error) {
            if (isset($error->code)) {
                $create_user_error_msg .= ", code: ".$error->code;
            }
            if (isset($error->message)) {
                $create_user_error_msg .= ", Message: ".$error->message;
            }
            if (isset($error->remarks)) {
                $create_user_error_msg .= ", Remarks: ".$error->remarks;
            }
        }
        $order->add_order_note( $create_user_error_msg, true );

        if ( $result->status == 'success' ) {

            if ( !$user_id and email_exists($user_email) == false ) {
            
                $random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );            
                $user_id         = wc_create_new_customer( $user_email, $user_name, $random_password ); 

                update_user_meta( $user_id, 'account_id', $result->data->account_id );
                update_user_meta( $user_id, 'first_name', $order->get_billing_first_name() );
                update_user_meta( $user_id, 'last_name', $order->get_billing_last_name() );
                update_user_meta( $user_id, 'contact_code', $phoneCode );
                update_user_meta( $user_id, 'contact_number', $order->get_billing_phone() );
                update_user_meta( $user_id, 'billing_country', $order->get_billing_country() );
                update_user_meta( $user_id, 'ind_bus_type', $this->seller_type );                
                update_user_meta( $user_id, 'created', current_time( 'mysql' ) );
                update_user_meta( $user_id, "billing_first_name", $order->get_billing_first_name() );
                update_user_meta( $user_id, "billing_last_name", $order->get_billing_last_name() );
                update_user_meta( $user_id, "billing_email", $order->get_billing_email() );

                update_post_meta( $order_id, '_customer_user', $user_id );

            } else {
                $random_password = __('User already exists.  Password inherited.');
            }
        }

        if( $result->status == 'error' ){

            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url( $order )
            ); 
        }

        $user   = get_user_by( 'email', $user_email );
        $userId = $user->ID;

        if(!empty($userId)){
            $result->status = 'success';
            $account_id = get_user_meta( $userId, 'account_id', true );
        }else{
            $account_id = $result->data->account_id;
        }

        if( !is_wp_error( $result ) ) {

            if ( $result->status == 'success' ) {
                    
                        $argsEscrow = array(
                            "txn_type"              => $this->txn_type_escrow,
                            "release_mechanism"     => $this->release_mechanism,
                            "initiated_by"          => $account_id,
                            "buyer_id"              => $account_id,
                            "seller"                => array(
                                                        "country"      => $this->seller_country,
                                                        "email"        => $this->seller_email,
                                                        "ind_bus_type" => $this->seller_type,
                                                        "contact_name" => $this->seller_name
                            ),
                            "txn_description"       => trim( $this->description ),
                            "invoice_currency"      => get_option('woocommerce_currency'),
                            "invoice_amount"        => (int) $order->get_total()
                        );

                        update_post_meta( $order_id, 'account_id', $account_id );                  
     
                        $escrow_api_endpoint = "/v1/escrow";
                        $api_url             = 'https://api-sandbox.tazapay.com/v1/escrow';

                        $result_escrow = $this->request_apicall( $api_url, $escrow_api_endpoint, $argsEscrow, $order_id);

                        $create_escrow_msg = "";
                        $create_escrow_msg = $result_escrow->message;
                        foreach ($result_escrow->errors as $key => $error) {

                            if (isset($error->code)) {
                                $create_escrow_msg .= ", code: ".$error->code;
                            }
                            if (isset($error->message)) {
                                $create_escrow_msg .= ", Message: ".$error->message;
                            }
                            if (isset($error->remarks)) {
                                $create_escrow_msg .= ", Remarks: ".$error->remarks;
                            }
                        }

                        $order->add_order_note( $create_escrow_msg, true );

                        if( $result_escrow->status == 'error' ){

                            return array(
                                'result' => 'success',
                                'redirect' => $this->get_return_url( $order )
                            ); 
                        }
                    
                    if ( $result_escrow->status == 'success' ) {

                        $argsPayment = array(
                            "txn_no"         => $result_escrow->data->txn_no,
                            "percentage"     => 0,
                            "complete_url"   => $this->get_return_url( $order ),
                            "error_url"      => $this->get_return_url( $order ),
                            "callback_url"   => ""
                        );
                        
                        update_post_meta( $order_id, 'txn_no', $result_escrow->data->txn_no );

                        $payment_api_endpoint = "/v1/session/payment";
                        $api_url = 'https://api-sandbox.tazapay.com/v1/session/payment';

                        $result_payment = $this->request_apicall( $api_url, $payment_api_endpoint, $argsPayment, $order_id);

                        $payment_msg = "";
                        $payment_msg = $result_payment->message;
                        foreach ($result_payment->errors as $key => $error) {

                            if (isset($error->code)) {
                                $payment_msg .= ", code: ".$error->code;
                            }
                            if (isset($error->message)) {
                                $payment_msg .= ", Message: ".$error->message;
                            }
                            if (isset($error->remarks)) {
                                $payment_msg .= ", Remarks: ".$error->remarks;
                            }
                        }
                        $order->add_order_note( $payment_msg, true );

                        if ( $result_payment->status == 'success' ) {

                            $redirect_url = $result_payment->data->redirect_url;                            

                            // Mark as on-hold (we're awaiting the payment)
                            $order->update_status( 'on-hold', __( 'Awaiting offline payment', 'wc-tp-payment-gateway' ) );
                          
                            $order->reduce_order_stock();
                            
                            //$order->add_order_note( $result_payment->message, true );

                            // Empty cart
                            $woocommerce->cart->empty_cart();
                            
                            update_post_meta( $order_id, 'redirect_url', $redirect_url );
                            
                            // Redirect to the thank you page
                            return array(
                                'result' => 'success',
                                'redirect' => $redirect_url
                                //'redirect' => $this->get_return_url( $order )
                            );                            
                        }   
                    } 

            } else {
                wc_add_notice(  'Please try again.', 'error' );
                return;
            }
        } else {
            wc_add_notice(  'Connection error.', 'error' );
            return;
        }
    }

    public function tazapay_add_payment_column_to_myaccount( $columns ) {
        $new_columns = [];
    
        foreach ($columns as $key => $name){
            $new_columns[$key] = $name;
    
            if ('order-actions' === $key){
                $new_columns['pay-order'] = __('Payment', 'wc-tp-payment-gateway');
            }
        }
        return $new_columns;
    }

    public function tazapay_add_pay_for_order_to_payment_column_myaccount( $order ) {
        if( in_array( $order->get_status(), array( 'pending', 'on-hold' ) ) ) {
            
            $payment_url = get_post_meta( $order->get_id(), 'redirect_url', true );
            
            if( isset($payment_url) && !empty($payment_url) ){
                printf( '<a class="woocommerce-button button pay" href="%s">%s</a>', $payment_url, __("Pay now", "wc-tp-payment-gateway" ) );
            }
        }
    }

    public function get_private_order_notes( $order_id ){
        global $wpdb;

        $table_perfixed = $wpdb->prefix . 'comments';
        $results = $wpdb->get_results("SELECT * FROM $table_perfixed WHERE  `comment_post_ID` = $order_id AND  `comment_type` LIKE  'order_note'");

        foreach($results as $note){
            $order_note[]  = array(
                'note_id'      => $note->comment_ID,
                'note_date'    => $note->comment_date,
                'note_author'  => $note->comment_author,
                'note_content' => $note->comment_content,
            );
        }
        return $order_note;
    }

    public function tazapay_view_order_and_thankyou_page( $order_id ){  
        $order              = wc_get_order( $order_id );        
        $paymentMethod      = get_post_meta( $order_id, '_payment_method', true );

        if($paymentMethod == 'tz_tazapay'){
            
            $user_email     = $order->get_billing_email();
            $txn_no         = get_post_meta( $order_id, 'txn_no', true );

            $user = get_user_by( 'email', $user_email );
            $userId = $user->ID;

            if(!empty($userId)){
                $account_id = get_user_meta( $userId, 'account_id', true );
            }else{
                $account_id = get_post_meta( $order_id, 'account_id', true );
            }
            ?>
            <h2><?php echo __('Tazapay Information', 'wc-tp-payment-gateway'); ?></h2>
            <p><?php echo __('TazaPay Escrow', 'wc-tp-payment-gateway'); ?></p>
            <table class="woocommerce-table shop_table gift_info">
                <tfoot>
                    <tr>
                        <th scope="row"><?php echo __('Tazapay Account UUID', 'wc-tp-payment-gateway'); ?></th>
                        <td><?php echo $account_id; ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo __('Tazapay Payer E-Mail', 'wc-tp-payment-gateway'); ?></th>
                        <td><?php echo $user_email; ?></td>
                    </tr>
                    <?php if($txn_no){ ?>
                    <tr>
                        <th scope="row"><?php echo __('Escrow txn_no', 'wc-tp-payment-gateway'); ?></th>
                        <td><?php echo $txn_no; ?></td>
                    </tr>
                    <?php } ?>
                </tfoot>
            </table>
            <?php 
            // if( in_array( $order->get_status(), array( 'pending', 'on-hold' ) ) ) {
            //     $payment_url = get_post_meta( $order_id, 'redirect_url', true );            
            //     if( isset($payment_url) && !empty($payment_url) ){
            //         printf( '<a class="woocommerce-button button pay" href="%s">%s</a>', $payment_url, __("Pay By TazaPay", "woocommerce" ) );
            //     }
            // }

            $order_notes = $this->get_private_order_notes( $order_id );
            foreach($order_notes as $note){
                $note_id = $note['note_id'];
                $note_date = $note['note_date'];
                $note_author = $note['note_author'];
                $note_content = $note['note_content'];
                
                // Outputting each note content for the order
                echo '<p><strong>'.date('F j, Y h:i A', strtotime($note_date)).'</strong> '.$note_content.'</p>';
            }
        }    
    }

    // Add custom tazapay icons to WooCommerce Checkout Page 
    public function tazapay_woocommerce_icons($icon, $id) {

     if ( $id === 'tz_tazapay' ) {

        $logo_url = TAZAPAY_PUBLIC_ASSETS_DIR . "images/logo-dark.svg";
        return $icon  = '<img src=' .$logo_url. ' alt="tazapay" />';

     } else {
        return $icon;
     }

    }  

    public function tazapay_woocommerce_available_payment_gateways( $available_gateways ) {
        if (! is_checkout() ) return $available_gateways;  // stop doing anything if we're not on checkout page.
        if (array_key_exists('tz_tazapay',$available_gateways)) {
             $available_gateways['tz_tazapay']->order_button_text = __( 'Place Order and Pay', 'wc-tp-payment-gateway' );
        }
        return $available_gateways;
    }

    public function tazapay_order_meta_general( $order ){

    $account_id = get_post_meta( $order->get_id(), 'account_id', true );
    $redirect_url = get_post_meta( $order->get_id(), 'redirect_url', true );
    $txn_no = get_post_meta( $order->get_id(), 'txn_no', true );
    
        if(isset($account_id)){
        ?>
        <br class="clear" />
        <h3><?php echo __( 'TazaPay Information', 'wc-tp-payment-gateway' ); ?></h3>

        <div class="address">
            <p><strong><?php echo __( 'TazaPay Account UUID:', 'wc-tp-payment-gateway' ); ?></strong> <?php echo $account_id ?></p>
            <p><strong><?php echo __( 'Txn no:', 'wc-tp-payment-gateway' ); ?></strong> <?php echo $txn_no ?></p>
            <p><strong><?php echo __( 'Redirect url:', 'wc-tp-payment-gateway' ); ?></strong> <?php echo $redirect_url ?></p>            
        </div>
        <?php 
        }
    }
}

add_action( 'add_meta_boxes', 'remove_shop_order_meta_boxe', 90 );
function remove_shop_order_meta_boxe() {
    remove_meta_box( 'postcustom', 'shop_order', 'normal' );
}