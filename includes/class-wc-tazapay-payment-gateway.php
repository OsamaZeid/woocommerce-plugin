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
        $this->title                        = $this->get_option( 'title' );
        $this->description                  = $this->get_option( 'description' );
        $this->seller_name                  = $this->get_option( 'seller_name' );
        $this->seller_email                 = $this->get_option( 'seller_email' );
        $this->tazapay_seller_type          = $this->get_option( 'tazapay_seller_type' );
        $this->tazapay_multi_seller_plugin  = $this->get_option( 'tazapay_multi_seller_plugin' );
        $this->seller_country               = $this->get_option( 'seller_country' );
        $this->txn_type_escrow              = $this->get_option( 'txn_type_escrow' );
        $this->seller_id                    = $this->get_option( 'seller_id' );
        $this->release_mechanism            = $this->get_option( 'release_mechanism' );
        $this->fee_paid_by                  = $this->get_option( 'fee_paid_by' );
        $this->fee_percentage               = $this->get_option( 'fee_percentage' );
        $this->enabled                      = $this->get_option( 'enabled' );
        $this->sandboxmode                  = 'yes' === $this->get_option( 'sandboxmode' );
        $this->live_api_key                 = $this->sandboxmode ? $this->get_option( 'sandbox_api_key' ) : $this->get_option( 'live_api_key' );
        $this->live_api_secret_key          = $this->sandboxmode ? $this->get_option( 'sandbox_api_secret_key' ) : $this->get_option( 'live_api_secret_key' );

        if($this->sandboxmode == 'yes'){
            $this->base_api_url = 'https://api-sandbox.tazapay.com';
            $this->environment  = 'sandbox';
        }else{
            $this->base_api_url = 'https://api.tazapay.com';
            $this->environment  = 'production';
        }
        
        // This action hook saves the settings
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

        //add_filter( 'woocommerce_my_account_my_orders_columns', array( $this, 'tazapay_add_payment_column_to_myaccount' ) );
        //add_action( 'woocommerce_my_account_my_orders_column_pay-order', array( $this,'tazapay_add_pay_for_order_to_payment_column_myaccount' ) );

        add_action( 'woocommerce_view_order', array( $this, 'tazapay_view_order_and_thankyou_page' ), 20 );
        add_action( 'woocommerce_thankyou', array( $this, 'tazapay_view_order_and_thankyou_page' ), 20 );

        //add_filter( 'woocommerce_gateway_icon', array( $this, 'tazapay_woocommerce_icons'), 10, 2 );
        add_filter( 'woocommerce_available_payment_gateways', array( $this, 'tazapay_woocommerce_available_payment_gateways' ) );
        add_action( 'woocommerce_admin_order_data_after_order_details', array( $this, 'tazapay_order_meta_general' ) );

        add_action( 'wp_ajax_order_status_refresh', array( $this, 'tazapay_order_status_refresh' ) ); 
    }

    /*
    * Plugin options
    */
    public function init_form_fields(){

        global $woocommerce;
        $countries_obj  = new WC_Countries();
        $countries      = $countries_obj->__get('countries');
        $text1          = __( 'Place the payment gateway in sandbox mode using sandbox API keys', 'wc-tp-payment-gateway' );
        $text2          = __( 'Request credentials', 'wc-tp-payment-gateway' );
        $text3          = __( 'Request credentials for accepting payments via TazaPay', 'wc-tp-payment-gateway' );
        $text4          = __( 'Get Seller ID', 'wc-tp-payment-gateway' );
        $text5          = __( 'Before you redirect to sign up form you should save configuration.', 'wc-tp-payment-gateway' );

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
                'default'     => 'Pay Now, Release Later',
            ),
            'description' => array(
                'title'       => __('Transaction Description', 'wc-tp-payment-gateway' ),
                'type'        => 'textarea',
                'description' => __('A short synopsis of the type of goods/service', 'wc-tp-payment-gateway' ),
                'default'     => 'Pay securely with buyer protection',
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
                'description' => __('TazaPay Live API Secret Key', 'wc-tp-payment-gateway' )
            ),
            'seller_email' => array(
                'title'       => __('Email', 'wc-tp-payment-gateway' ),
                'type'        => 'text',
                'description' => __('Seller\'s Email', 'wc-tp-payment-gateway' ),
                'class'     => 'tazapay-singleseller'
            ),            
            'seller_id' => array(
                'title'       => __('Seller ID', 'wc-tp-payment-gateway' ),
                'type'        => 'text',
                'description' => __('Tazapay account UUID <br><br><a href="?page=tazapay-signup-form" class="button-primary" target="_blank" title="Click Here">'.$text4.'</a><br>'.$text5, 'wc-tp-payment-gateway' ),
                'class'     => 'tazapay-singleseller'
            ),
            'tazapay_seller_type' => array(
                'title'       => __('Seller Type', 'wc-tp-payment-gateway' ),
                'type'        => 'select',
                'options'     => array(
                    'singleseller' => __('Single seller', 'wc-tp-payment-gateway' ),
                    'multiseller'  => __('Multi Seller', 'wc-tp-payment-gateway' )
                ),
                'description' => __('Single seller or Multi Seller', 'wc-tp-payment-gateway' ),
                'class'     => 'tazapay-seller-type'
            ),
            'tazapay_multi_seller_plugin' => array(
                'title'       => __('Multi Seller Marketplace Plugin', 'wc-tp-payment-gateway' ),
                'type'        => 'select',
                'options'     => array(
                    'dokan'            => __('Dokan', 'wc-tp-payment-gateway' ),
                    'wcfm-marketplace' => __('WCFM Marketplace', 'wc-tp-payment-gateway' )                    
                ),
                'description' => __('Multi Seller Marketplace Plugin', 'wc-tp-payment-gateway' ),
                'class'     => 'tazapay-multiseller'
            ),
            
            // 'seller_name' => array(
            //     'title'       => __('Name', 'wc-tp-payment-gateway' ),
            //     'type'        => 'text',
            //     'description' => __('Seller\'s Name', 'wc-tp-payment-gateway' ),
            //     'required'    => true,
            // ),
            // 'seller_email' => array(
            //     'title'       => __('Email', 'wc-tp-payment-gateway' ),
            //     'type'        => 'text',
            //     'description' => __('Seller\'s Email', 'wc-tp-payment-gateway' ),
            //     'required'    => true,
            // ),
            // 'seller_type' => array(
            //     'title'       => __('Entity Type', 'wc-tp-payment-gateway' ),
            //     'type'        => 'select',
            //     'options'     => array(
            //         'Individual' => __('Individual', 'wc-tp-payment-gateway' ),
            //         'Business' => __('Business', 'wc-tp-payment-gateway' )
            //     ),
            //     'description'=> __('User\'s entity type', 'wc-tp-payment-gateway' )
            // ),
            // 'seller_country' => array(
            //     'title'       => __('Country', 'wc-tp-payment-gateway' ),
            //     'type'        => 'select',
            //     'options'     => $countries,
            //     'description' => __('User\'s country', 'wc-tp-payment-gateway' )
            // ),
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
            
            $tazapay_logo_url = TAZAPAY_PUBLIC_ASSETS_DIR . "images/logo-dark.svg";
            $payment_methods  = TAZAPAY_PUBLIC_ASSETS_DIR . "images/payment_methods.png";
            ?>
            <div class="power-method-logos">
                <div class="left-text"><p><?php echo __('Powered by', 'wc-tp-payment-gateway'); ?></p></div>         
                <div class="right-logo"><img src="<?php echo $tazapay_logo_url; ?>" alt="tazapay" /></div>
            </div>
            <div class="payment-method-logos">
            <?php
            echo wpautop( wp_kses_post( $this->description ) );
            echo '<img src=' .$payment_methods. ' alt="tazapay" class="tazapay-payment-method"/>';
            ?>
            </div>
            <?php
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
        $filename = $upload_dir['basedir'].'/tazapay_payment_log.txt';
        $responsetxt = 'Oder Id:'.$order_id.'-'.$response."\n";

        if (file_exists($filename)) {

           $handle = fopen($filename, 'a') or die('Cannot open file:  '.$filename);
           fwrite($handle, $responsetxt);

        } else {

            $handle = fopen($filename, "w") or die("Unable to open file!");
            fwrite($handle, $responsetxt);
        }
        fclose($handle);

        $api_array = json_decode( $response );        
        curl_close($curl);
        
        return $api_array;
    }

    /*
    * Get escrow status by txn_no
    */
    public function request_api_order_status($txn_no){

      /*
      * generate salt value
      */
      $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789`~!@#$%^&*()-=_+';
      $l = strlen($chars) - 1;
      $salt = '';
      for ($i = 0; $i < 8; ++$i) {
      $salt .= $chars[rand(0, $l)];
      }

      $method= "GET";
      $APIEndpoint = "/v1/escrow/".$txn_no;
      $timestamp = time();
      $apiKey      = $this->live_api_key;
      $apiSecret   = $this->live_api_secret_key;
      $api_url = $this->base_api_url;

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
      $signature = base64_encode($hmacSHA256);

      $curl = curl_init();
      curl_setopt_array(
      $curl,
      [
        CURLOPT_URL => $api_url.$APIEndpoint,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLINFO_HEADER_OUT => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => [
          'accesskey: '.$apiKey,
          'salt: '.$salt,
          'signature: '.$signature,
          'timestamp: '.$timestamp,
          'Content-Type: application/json'
        ],
      ]
      );
      $response = curl_exec($curl);

      $info = curl_getinfo($curl);
      $header_info = curl_getinfo($curl, CURLINFO_HEADER_OUT);

      $api_array = json_decode($response);
      curl_close($curl);

      return $api_array;
    }

    /*
    * We're processing the payments here
    */
    public function process_payment( $order_id ) {

        global $woocommerce, $wpdb;

        // we need it to get any order detailes
        $order      = wc_get_order( $order_id );

        $account_id = "";
        $user_email = $order->get_billing_email();
        // $user       = get_user_by( 'email', $user_email );
        // $userId     = $user->ID;
        $phoneCode  = $this->getPhoneCode($order->get_billing_country());

        global $wpdb;                
        $tablename      = $wpdb->prefix.'tazapay_user';
        $user_results   = $wpdb->get_results("SELECT account_id FROM $tablename WHERE email = '". $user_email ."' AND environment = '". $this->environment ."'");
        $account_id     = $user_results[0]->account_id;

        if(!empty($account_id)){

            $account_id = $user_results[0]->account_id;            

        }else{

            // Create tazapay user
            $args = array(
                "email"                 => $order->get_billing_email(),
                "first_name"            => $order->get_billing_first_name(),
                "last_name"             => $order->get_billing_last_name(),
                "contact_code"          => $phoneCode,
                "contact_number"        => $order->get_billing_phone(),
                "country"               => $order->get_billing_country(),
                "ind_bus_type"          => "Individual"
                //"partners_customer_id"  => "1232131"
            );

            $api_endpoint = "/v1/user";
            $api_url      = $this->base_api_url.'/v1/user';

            $result   = $this->request_apicall( $api_url, $api_endpoint, $args, $order_id );

            if ( $result->status == 'error' ) {

                $create_user_error_msg  = "";
                $create_user_error_msg  = $result->message;
                $create_user_error_msg .= ", TazaPay Email : ".$order->get_billing_email();

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

                return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url( $order )
                ); 
            }
            if ( $result->status == 'success' ) {

                $tablename  = $wpdb->prefix.'tazapay_user';
                $account_id = $result->data->account_id;

                $wpdb->insert( $tablename, array(
                    'account_id'           => $account_id, 
                    'user_type'            => "buyer",
                    'email'                => $order->get_billing_email(), 
                    'first_name'           => $order->get_billing_first_name(),
                    'last_name'            => $order->get_billing_last_name(), 
                    'contact_code'         => $phoneCode, 
                    'contact_number'       => $order->get_billing_phone(),
                    'country'              => $order->get_billing_country(), 
                    'ind_bus_type'         => "Individual",
                    'business_name'        => "", 
                    'partners_customer_id' => "", 
                    'environment'          => $this->environment,
                    'created'              => current_time( 'mysql' ) ),
                    array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) 
                );
            } 
        }

        if (!empty($account_id )) {
            $order->add_order_note( "Tazapay Acccount UUID: ".$account_id."", true );

            foreach ( WC()->cart->get_cart() as $cart_item ){
                $item_name = $cart_item['data']->get_title();
                $quantity  = $cart_item['quantity'];
                $items[]   = $quantity.' x '.$item_name;
            }
            $listofitems   = implode(', ', $items);
            $description   = get_bloginfo( 'name' ).' : '.$listofitems;

            // for singleseller
            if( !empty($this->seller_id) ) {
                $seller_id = $this->seller_id;
            }
            // for multiseller
            foreach ( WC()->cart->get_cart() as $cart_item ){
                $item_name          = $cart_item['data']->get_title();            
                $product_id         = $cart_item['data']->get_id();
                $vendor_id          = get_post_field( 'post_author', $product_id );
                $vendor             = get_userdata( $vendor_id );
                $seller_email[]     = $vendor->user_email;            
            }

            $selleremail = array_unique($seller_email);
            $sellercount = count($selleremail);

            if( $sellercount == 1 && $this->tazapay_seller_type == 'multiseller' && $this->tazapay_multi_seller_plugin == 'dokan' ){

                $tablename      = $wpdb->prefix.'tazapay_user';
                $seller_results = $wpdb->get_results("SELECT * FROM $tablename WHERE email = '". $selleremail[0] ."' AND environment = '". $this->environment ."'");
                $seller_id     = $seller_results[0]->account_id;

                if( !empty($seller_id) ) {
                    $seller_id     = $seller_results[0]->account_id;
                }else{
                    $seller_id = $this->seller_id;
                }
            }

            $argsEscrow = array(
                "txn_type"              => $this->txn_type_escrow,
                "release_mechanism"     => $this->release_mechanism,
                "initiated_by"          => $account_id,
                "buyer_id"              => $account_id,
                "seller_id"             => $seller_id,
                "txn_description"       => $description,
                "invoice_currency"      => get_option('woocommerce_currency'),
                "invoice_amount"        => (int) $order->get_total()
            );

            update_post_meta( $order_id, 'account_id', $account_id );

            update_user_meta( $userId, 'account_id', $account_id );
            update_user_meta( $userId, 'first_name', $order->get_billing_first_name() );
            update_user_meta( $userId, 'last_name', $order->get_billing_last_name() );
            update_user_meta( $userId, 'contact_code', $phoneCode );
            update_user_meta( $userId, 'contact_number', $order->get_billing_phone() );
            update_user_meta( $userId, 'ind_bus_type', 'Individual' );                
            update_user_meta( $userId, 'created', current_time( 'mysql' ) );
            update_user_meta( $userId, 'environment', $this->environment );

            $escrow_api_endpoint = "/v1/escrow";
            $api_url             = $this->base_api_url.'/v1/escrow';

            $result_escrow = $this->request_apicall( $api_url, $escrow_api_endpoint, $argsEscrow, $order_id );

            if( $result_escrow->status == 'error' ){

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

                return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url( $order )
                ); 
            }

            if ( $result_escrow->status == 'success' ) {

                update_post_meta( $order_id, 'txn_no', $result_escrow->data->txn_no );

                $order->add_order_note( $result_escrow->message, true );

                $argsPayment = array(
                    "txn_no"         => $result_escrow->data->txn_no,
                    "percentage"     => 0,
                    "complete_url"   => $this->get_return_url( $order ),
                    "error_url"      => $this->get_return_url( $order ),
                    "callback_url"   => ""
                );
                
                $payment_api_endpoint = "/v1/session/payment";
                $api_url              = $this->base_api_url.'/v1/session/payment';
                $result_payment       = $this->request_apicall( $api_url, $payment_api_endpoint, $argsPayment, $order_id );

                if( $result_payment->status == 'error' ){
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

                    return array(
                        'result' => 'success',
                        'redirect' => $this->get_return_url( $order )
                    ); 
                }

                if ( $result_payment->status == 'success' ) {

                    $redirect_url = $result_payment->data->redirect_url;                            
                    
                    $order->update_status( 'wc-on-hold', __( 'Awaiting offline payment', 'wc-tp-payment-gateway' ) );                  
                    
                    $order->reduce_order_stock();
                    
                    $order->add_order_note( $result_payment->message, true );

                    // Empty cart
                    $woocommerce->cart->empty_cart();
                    
                    update_post_meta( $order_id, 'redirect_url', $redirect_url );
                    
                    // Redirect to the thank you page
                    return array(
                        'result' => 'success',
                        'redirect' => $redirect_url
                    );                            
                }   
            }

        } else {
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url( $order )
            ); 
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
                printf( '<a class="woocommerce-button button pay" href="%s">%s</a>', $payment_url, __("Pay With Escrow", "wc-tp-payment-gateway" ) );
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
            $redirect_url   = get_post_meta( $order_id, 'redirect_url', true );

            global $wpdb;                
            $tablename      = $wpdb->prefix.'tazapay_user';
            $user_results   = $wpdb->get_results("SELECT account_id FROM $tablename WHERE email = '". $user_email ."' AND environment = '". $this->environment ."'");
            $account_id     = $user_results[0]->account_id;

            ?>
            <h2><?php echo __('Tazapay Information', 'wc-tp-payment-gateway'); ?></h2>
            <p><?php echo __('Pay With Escrow', 'wc-tp-payment-gateway'); ?></p>
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
                    <tr>
                        <th scope="row"><?php echo __('Payment status', 'wc-tp-payment-gateway'); ?></th>
                        <td>
                            <?php 
                            $getEscrowstate = $this->request_api_order_status($txn_no);

                            if( isset($_POST['order-status']) ){

                                echo '<p><strong>Escrow state:</strong> ' .$getEscrowstate->data->state. '</p>';
                                echo '<p><strong>Escrow sub_state:</strong> ' .$getEscrowstate->data->sub_state. '</p>';
                            }                            
                            ?>
                            <form method="post" name="tazapay-order-status" action="">
                                <input type="submit" name="order-status" value="Refresh Status">
                            </form>    
                            <?php

                            if( $getEscrowstate->status == 'success' && ( $getEscrowstate->data->state == 'Payment_Received' || $getEscrowstate->data->sub_state == 'Payment_Done' ) )
                            {
                                /*
                                * Order status change on-hold to processing
                                */
                                $order->update_status('processing');

                                if( $getEscrowstate->data->state == 'Payment_Received' ){
                                    echo $getEscrowstate->data->state;                                
                                }

                                if( $getEscrowstate->data->sub_state == 'Payment_Done' ){
                                    echo $getEscrowstate->data->sub_state;                                
                                }

                            }else{

                                printf( '<a class="woocommerce-button button pay" href="%s">%s</a>', $redirect_url, __("Pay With Escrow", "wc-tp-payment-gateway" ) );

                            }
                            ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
            <?php 
            
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
        global $woocommerce, $wpdb;

        if (! is_checkout() ) return $available_gateways;  // stop doing anything if we're not on checkout page.
        if (array_key_exists('tz_tazapay',$available_gateways)) {
             $available_gateways['tz_tazapay']->order_button_text = __( 'Place Order and Pay', 'wc-tp-payment-gateway' );
        }

        // for singleseller

        if( empty($this->seller_id) ) {
            unset($available_gateways['tz_tazapay']);
        }

        // for multiseller

        foreach ( WC()->cart->get_cart() as $cart_item ){

            $item_name          = $cart_item['data']->get_title();            
            $product_id         = $cart_item['data']->get_id();
            $vendor_id          = get_post_field( 'post_author', $product_id );
            $vendor             = get_userdata( $vendor_id );
            $seller_email[]     = $vendor->user_email;            
        }

        $selleremail = array_unique($seller_email);
        $blogusers = get_users('role=administrator');
        foreach ($blogusers as $user) {
            $admin_email = $user->user_email;
        }

        $sellercount = count($selleremail);
        if($sellercount > 1 && $this->tazapay_seller_type != 'singleseller'){

            unset($available_gateways['tz_tazapay']);

        }else{

            if($selleremail[0] == $admin_email){
                // no code needed
            }
            else
            {
                $tablename      = $wpdb->prefix.'tazapay_user';
                $seller_results = $wpdb->get_results("SELECT * FROM $tablename WHERE email = '". $selleremail[0] ."' AND environment = '". $this->environment ."'");
                $account_id     = $seller_results[0]->account_id;

                if( $this->tazapay_seller_type == 'multiseller' && $this->tazapay_multi_seller_plugin == 'dokan' && empty($account_id) ) {
                    unset($available_gateways['tz_tazapay']);
                }
            }
        }

        
        return $available_gateways;
    }

    public function tazapay_order_meta_general( $order ){

        $account_id = get_post_meta( $order->get_id(), 'account_id', true );
        $redirect_url = get_post_meta( $order->get_id(), 'redirect_url', true );
        $txn_no = get_post_meta( $order->get_id(), 'txn_no', true );
    
        if(isset($account_id) && !empty($account_id)){
        ?>
        <br class="clear" />
        <h3><?php echo __( 'TazaPay Information', 'wc-tp-payment-gateway' ); ?></h3>

        <div class="address">
            <p><strong><?php echo __( 'TazaPay Account UUID:', 'wc-tp-payment-gateway' ); ?></strong> <?php echo $account_id ?></p>
            <p><strong><?php echo __( 'Txn no:', 'wc-tp-payment-gateway' ); ?></strong> <?php echo $txn_no ?></p>
            <?php
            $getEscrowstate = $this->request_api_order_status($txn_no);

            if( isset($_GET['order-status']) ){

                echo '<p><strong>Escrow state:</strong> ' .$getEscrowstate->data->state. '</p>';
                echo '<p><strong>Escrow sub_state:</strong> ' .$getEscrowstate->data->sub_state. '</p>';
            }
            ?>
            <a href="<?php echo $order->get_edit_order_url(); ?>&order-status=true" class="order-status-response button button-primary"><?php echo __( 'Refresh Status', 'wc-tp-payment-gateway' ); ?></a>                              
        </div>
        <?php 
        }
    }
}

add_action( 'add_meta_boxes', 'remove_shop_order_meta_boxe', 90 );
function remove_shop_order_meta_boxe() {
    remove_meta_box( 'postcustom', 'shop_order', 'normal' );
}

add_filter( 'manage_edit-shop_order_columns', 'custom_shop_order_column', 20 );
function custom_shop_order_column($columns)
{
    $reordered_columns = array();

    // Inserting columns to a specific location
    foreach( $columns as $key => $column){
        $reordered_columns[$key] = $column;
        if( $key ==  'order_status' ){
            // Inserting after "Status" column
            $reordered_columns['tazapay-status'] = __( 'Payment Status','wc-tp-payment-gateway');
        }
    }
    return $reordered_columns;
}

// Adding custom fields meta data for each new column (example)
add_action( 'manage_shop_order_posts_custom_column' , 'custom_orders_list_column_content', 20, 2 );
function custom_orders_list_column_content( $column, $post_id )
{
    switch ( $column )
    {
        case 'tazapay-status' :

            // Get custom post meta data
            $txn_no         = get_post_meta( $post_id, 'txn_no', true );
            $paymentMethod  = get_post_meta( $post_id, '_payment_method', true );

            if( !empty($txn_no) && $paymentMethod == 'tz_tazapay' ){

            $request_api_order_status = new WC_TazaPay_Gateway();
            $getEscrowstate = $request_api_order_status->request_api_order_status($txn_no);

            //print_r($getEscrowstate);
            echo '<small><em><b>Escrow state:</b> ' .$getEscrowstate->data->state. '</em></small><br>';
            echo '<small><em><b>Escrow sub_state:</b> ' .$getEscrowstate->data->sub_state. '</em></small>';

            }else{
                echo 'Other Payment Method';
            }

            break;

    }
}