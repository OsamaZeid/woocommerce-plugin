<?php
/*
 * Get escrow status by txn_no
 */
function tazapay_request_api_order_status($txn_no)
{

  $woocommerce_tz_tazapay_settings = get_option('woocommerce_tz_tazapay_settings');

  /*
  * generate salt value
  */
  $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789`~!@#$%^&*()-=_+';
  $l = strlen($chars) - 1;
  $salt = '';
  for ($i = 0; $i < 8; ++$i) {
    $salt .= $chars[rand(0, $l)];
  }

  $method = "GET";
  $APIEndpoint = "/v1/escrow/" . $txn_no;
  $timestamp = time();
  $apiKey     = $woocommerce_tz_tazapay_settings['sandboxmode'] ? $woocommerce_tz_tazapay_settings['sandbox_api_key'] : $woocommerce_tz_tazapay_settings['live_api_key'];
  $apiSecret  = $woocommerce_tz_tazapay_settings['sandboxmode'] ? $woocommerce_tz_tazapay_settings['sandbox_api_secret_key'] : $woocommerce_tz_tazapay_settings['live_api_secret_key'];

  if ($woocommerce_tz_tazapay_settings['sandboxmode'] == 'sandbox') {
    $api_url = 'https://api-sandbox.tazapay.com';
  } else {
    $api_url = 'https://api.tazapay.com';
  }

  /*
  * generate to_sign
  * to_sign = toUpperCase(Method) + Api-Endpoint + Salt + Timestamp + API-Key + API-Secret
  */
  $to_sign = $method . $APIEndpoint . $salt . $timestamp . $apiKey . $apiSecret;

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
      CURLOPT_URL => $api_url . $APIEndpoint,
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
        'accesskey: ' . $apiKey,
        'salt: ' . $salt,
        'signature: ' . $signature,
        'timestamp: ' . $timestamp,
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

if (!wp_next_scheduled('tazapay_order_hook')) {
  wp_schedule_event(strtotime('12:00:00'), 'daily', 'tazapay_order_hook');
}

add_action('tazapay_order_hook', 'tazapay_order_change', 10, 0);
function tazapay_order_change()
{

  global $wpdb;
  $orderList = $wpdb->get_results("SELECT pm.post_id AS order_id FROM {$wpdb->prefix}postmeta AS pm LEFT JOIN {$wpdb->prefix}posts AS p ON pm.post_id = p.ID WHERE p.post_type = 'shop_order' AND p.post_status = 'wc-on-hold' AND pm.meta_key = '_payment_method'");

  foreach ($orderList as $orderPost) {

    $order           = new WC_Order($orderPost->order_id);
    $paymentMethod   = get_post_meta($orderPost->order_id, '_payment_method', true);
    $txn_no         = get_post_meta($orderPost->order_id, 'txn_no', true);
    $getEscrowstate = tazapay_request_api_order_status($txn_no);

    if ($getEscrowstate->status == 'success' && $paymentMethod == 'tz_tazapay' && ($getEscrowstate->data->state == 'Payment_Recieved' || $getEscrowstate->data->sub_state == 'Payment_Done')) {
      $order->update_status('processing');
    }
    if ($getEscrowstate->status == 'success' && $paymentMethod == 'tz_tazapay' && $getEscrowstate->data->sub_state == 'Payment_Failed') {
      $order->update_status('cancelled');
    }
  }
}
