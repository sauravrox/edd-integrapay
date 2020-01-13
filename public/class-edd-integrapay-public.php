<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://easydigitaldownloads.com
 * @since      1.0.0
 *
 * @package    EDD_IntegraPay
 * @subpackage EDD_IntegraPay/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    EDD_IntegraPay
 * @subpackage EDD_IntegraPay/public
 * @author     Easy Digital Downloads <https://easydigitaldownloads.com>
 */
class EDD_IntegraPay_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Returns gateway credentials
	 */
	function edd_integrapay_get_credentials()
  {
    global $edd_options;

    $account_details = array(
        'username' => isset( $edd_options['integrapay_username'] ) ? esc_attr( $edd_options['integrapay_username'] ): '' ,
        'password' => isset( $edd_options['integrapay_password'] ) ? esc_attr( $edd_options['integrapay_password'] ):'' ,
        'business' => isset( $edd_options['integrapay_business'] ) ? esc_attr( $edd_options['integrapay_business'] ):'' ,
    );

    return $account_details;
  }

  /**
	 * Process the payment.
	 *
	 */
  function edd_integrapay_process_payment( $purchase_data ) {

      /////////////////
      if( ! wp_verify_nonce( $purchase_data['gateway_nonce'], 'edd-gateway' ) ) {
          wp_die( __( 'Nonce verification has failed', 'edd-integrapay' ), __( 'Error', 'edd-integrapay' ), array( 'response' => 403 ) );
      }

      global $edd_options;

      $account_details = $this->edd_integrapay_get_credentials();

      // Fail if no account details set
      if (!$account_details['username'] || !$account_details['password']) {
          edd_set_error('no_credentials', __('You must enter your IntegraPay credentials in settings', 'edd-integrapay'));
      }

      if(edd_is_test_mode()){
          $url = 'https://sandbox.auth.paymentsapi.io/login';
          $template = 'Basic';
          $host = 'sandbox.auth.paymentsapi.io';
          $length = 81;            
      } else {
          $url = 'https://auth.paymentsapi.io/login';
          $template = 'HPPAPI';
          $host = 'auth.paymentsapi.io';
          $length = 82;
      }

      // check for any stored errors
      $errors = edd_get_errors();
      if (!$errors) {
          $purchase_summary = edd_get_purchase_summary($purchase_data);
          $success_page_permalink = get_permalink($edd_options['success_page']);
          $failure_page_permalink = get_permalink($edd_options['failure_page']);

          $payment = array(
            'price' => $purchase_data['price'],
            'date' => $purchase_data['date'],
            'user_email' => $purchase_data['user_email'],
            'purchase_key' => $purchase_data['purchase_key'],
            'currency' => 'USD',
            'downloads' => $purchase_data['downloads'],
            'cart_details' => $purchase_data['cart_details'],
            'user_info' => $purchase_data['user_info'],
            'status' => 'pending'
          );

          // record the pending payment
          $payment = edd_insert_payment($payment);

          $txnid = substr(hash('sha256', mt_rand() . microtime()), 0, 20); // Unique alphanumeric Transaction ID
          edd_set_payment_transaction_id( $payment, $txnid );

          $line1 = isset($purchase_data['user_info']['address']['line1']) ? $purchase_data['user_info']['address']['line1'] : '';
          $line2 = isset($purchase_data['user_info']['address']['line2']) ? $purchase_data['user_info']['address']['line2'] : '';
          $city = isset($purchase_data['user_info']['address']['city']) ? $purchase_data['user_info']['address']['city'] : '';
          $state = isset($purchase_data['user_info']['address']['state']) ? $purchase_data['user_info']['address']['state'] : '';
          $zip = isset($purchase_data['user_info']['address']['zip']) ? $purchase_data['user_info']['address']['zip'] : '';
          $country = isset($purchase_data['user_info']['address']['country']) ? $purchase_data['user_info']['address']['country'] : '';
          $price = isset($purchase_data['price']) ? $purchase_data['price'] : '';
          $first_name = isset($purchase_data['user_info']['first_name']) ? $purchase_data['user_info']['first_name'] : '';
          $last_name = isset($purchase_data['user_info']['last_name']) ? $purchase_data['user_info']['last_name'] : '';
          $user_email = isset($purchase_data['user_email']) ? $purchase_data['user_email'] : '';

          $params = array(
            // Merchant details
            'username'      => $account_details['username'],
            'surl'          => add_query_arg( array( 'payment-confirm' => 'payu', 'edd_payu_cb'=> 1, 'payment-id' => $payment ), $success_page_permalink ),
            'furl'          => add_query_arg( array( 'payment-confirm' => 'payu', 'edd_payu_cb'=> 1, 'payment-id' => $payment ), $failure_page_permalink ),
            'curl'          => add_query_arg( array( 'payment-confirm' => 'payu', 'edd_payu_cb'=> 1, 'payment-id' => $payment ), $failure_page_permalink ),

            // Customer details
            'firstname'       => $first_name,
            'lastname'        => $last_name,
            'email'           => $user_email,
            'address1'        => $line1,
            'address2'        => $line2,
            'city'            => $city,
            'state'           => $state,
            'zipcode'         => $zip,
            'country'         => $country,
            'phone'           => '',
            'service_provider'=> 'integrapay',
            // Item details
            'productinfo'     => $purchase_summary,
            'amount'          => $price,
            'txnid'           => $txnid,
            'udf1'            => $payment,
          );

          $params = apply_filters( 'edd_integrapay_form_parameters', $params, $payment );

                ///////////////////
          $tnid = 'HPP-TOKEN-'.$txnid;
          $curl = curl_init();
          curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "{\n  \"Username\": ".$account_details['username'].",\n  \"Password\": \"".$account_details['password']."\"\n}",
            CURLOPT_HTTPHEADER => array(
              "Accept: */*",
              "Accept-Encoding: gzip, deflate",
              "Cache-Control: no-cache",
              "Connection: keep-alive",
              "Content-Length: ".$length,
              "Content-Type: application/json",
              "Cookie: __cfduid=d9d797653593b1f3febbeca20b1147d031567744154",
              "Host: ".$host,
              "Postman-Token: 3e720b50-a00e-4c76-b590-9d2dbd107e43,dae71129-a9f7-4277-8c58-e2ad54dcbb4c",
              "User-Agent: PostmanRuntime/7.16.3",
              "cache-control: no-cache"
            ),
          ));
          $response = curl_exec($curl);
          $response = json_decode($response, TRUE);
          
          if(!isset($response['access_token']))
          {
            edd_record_gateway_error( __( 'Payment Error', 'edd-integrapay' ), sprintf( __( 'Payment creation failed while processing a IntegraPay payment gateway purchase. Payment data: %s', 'edd-integrapay' ), json_encode( $purchase_data ) ) );
          // if errors are present, send the user back to the purchase page so they can be corrected
            edd_send_back_to_checkout('?payment-mode=integrapay');
          }

          $access_token = $response['access_token'];
          set_transient( 'bearer_token', $access_token, 60*60*12 );

          $array['ReturnUrl'] = $success_page_permalink;
          $array['Template'] = $template;
          $array['Transaction']['Reference'] = $tnid;
          $array['Transaction']['ProcessType'] = 'complete';
          $array['Transaction']['Amount'] = $purchase_data['price'];
          $array['Payer']['GivenName'] = $purchase_data['user_info']['first_name'].' '.$purchase_data['user_info']['last_name'];
          $array['Payer']['FamilyOrBusinessName'] = '';
          $array['Payer']['Email'] = $purchase_data['user_info']['email'];
          $array['Payer']['Mobile'] = '';
          $array['Payer']['Address']['Line1'] = $purchase_data['user_info']['address']['line1'];
          $array['Payer']['Address']['Suburb'] = $purchase_data['user_info']['address']['city'];
          $array['Payer']['Address']['PostCode'] = $purchase_data['user_info']['address']['zip'];
          $array['Payer']['Address']['Country'] = $purchase_data['user_info']['address']['country'];
          $array['Payer']['Address']['State'] = $purchase_data['user_info']['address']['state'];
          $array['Payer']['Address']['Line2'] = '';
          ////////////////////////////
          curl_close($curl);

          if(edd_is_test_mode()){
            $url = 'https://sandbox.rest.paymentsapi.io/businesses/'.$account_details['business'].'/services/tokens/hpp/';
            $host = 'sandbox.rest.paymentsapi.io';            
          }
          else{
            $url = 'https://rest.paymentsapi.io/businesses/'.$account_details['business'].'/services/tokens/hpp/';
            $host = 'rest.paymentsapi.io';
          }
          $curl = curl_init();
          $array = json_encode($array);
          curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $array,
            CURLOPT_HTTPHEADER => array(
              "Accept: */*",
              "Accept-Encoding: gzip, deflate",
              "Authorization: Bearer ".get_transient('bearer_token'),
              "Cache-Control: no-cache",
              "Connection: keep-alive",
              "Content-Length: ".strlen($array),
              "Content-Type: application/json",
              "Cookie: __cfduid=d9d797653593b1f3febbeca20b1147d031567744154",
              "Host: ".$host,
              "Postman-Token: 5d48bd5b-79de-4ecd-8ef2-34037d83960e,76104046-bd36-4971-a8ff-98b06cb070f0",
              "User-Agent: PostmanRuntime/7.16.3",
              "cache-control: no-cache"
            ),
          ));
          $response = curl_exec($curl);
          $err = curl_error($curl);
          $url = json_decode($response,TRUE);
          if(!isset($url['redirectToUrl']))
          {
            edd_record_gateway_error( __( 'Payment Error', 'edd-integrapay' ), sprintf( __( 'Payment creation failed while processing a IntegraPay payment gateway purchase. Payment data: %s', 'edd-integrapay' ), json_encode( $purchase_data ) ) );
          // if errors are present, send the user back to the purchase page so they can be corrected
            edd_send_back_to_checkout('?payment-mode=integrapay');
          }
          else{
            $url = $url['redirectToUrl'];
            curl_close($curl);
            wp_redirect($url);
            exit();
          }
      }
      else{
        edd_record_gateway_error( __( 'Payment Error', 'edd-integrapay' ), sprintf( __( 'Payment creation failed while processing a IntegraPay payment gateway purchase. Payment data: %s', 'edd-integrapay' ), json_encode( $purchase_data ) ) );
          // if errors are present, send the user back to the purchase page so they can be corrected
        edd_send_back_to_checkout('?payment-mode=integrapay');
      }
  }

  /**
	 * Checks the payment status using token
	 *
	 */
  function edd_integrapay_check_callback(){
    if ( isset( $_GET['webPageToken'] ) && esc_attr( $_GET['webPageToken'] ) != '' ) {

      $account_details = $this->edd_integrapay_get_credentials();

      if(edd_is_test_mode()){
        $token_lookup = 'https://sandbox.rest.paymentsapi.io/businesses/'.$account_details['business'].'/services/tokens/';
      } else {
        $token_lookup = 'https://rest.paymentsapi.io/businesses/'.$account_details['business'].'/services/tokens/';
      }
      @ob_clean();
      $token = esc_attr( $_GET['webPageToken'] );
      $curl = curl_init();
      curl_setopt_array($curl, array(
       CURLOPT_URL => $token_lookup.$token,
       CURLOPT_RETURNTRANSFER => true,
       CURLOPT_ENCODING => "",
       CURLOPT_MAXREDIRS => 10,
       CURLOPT_TIMEOUT => 0,
       CURLOPT_FOLLOWLOCATION => false,
       CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
       CURLOPT_CUSTOMREQUEST => "GET",
       CURLOPT_HTTPHEADER => array(
         "Accept: */*",
         "Accept-Encoding: gzip, deflate",
         "Authorization: Bearer ".get_transient('bearer_token'),
       ),
      ));
      $response = curl_exec($curl);
      
      $err = curl_error($curl);
      if ($err) {
       echo "cURL Error #:" . $err;
      } 
      else {
        $response = json_decode($response,true);
        $txnid = str_replace('HPP-TOKEN-', '', $response['transaction']['reference']);
        if( $response['transaction']['statusCode']!='S' && $response['transaction']['statusCode']!='C' && $response['status']!='PROCESSED_SUCCESSFUL' )
        {
          echo '<h2>Payment Failed. Please try again.</h2>';
          return;
        }
        
        $payment_id = edd_get_purchase_id_by_transaction_id($txnid);

        if($response['transaction']['statusCode']=='S'){
            edd_update_payment_status( $payment_id, 'publish' );
            edd_insert_payment_note( $payment_id, __( 'Payment done via IntegraPay with transaction id '.$txnid, 'edd-integrapay' ) );
        }

        if($response['transaction']['statusCode']=='P'){
            edd_update_payment_status( $payment_id, 'pending' );
            edd_insert_payment_note( $payment_id, __( 'IntegraPay payment pending with transaction id '.$txnid, 'edd-integrapay' ) );
        }

        if($response['transaction']['statusCode']=='R'){
            edd_update_payment_status( $payment_id, 'failed' );
            edd_insert_payment_note( $payment_id, __( 'IntegraPay payment failed with transaction id '.$txnid, 'edd-integrapay' ) );
        }
      }
    }
  }
}