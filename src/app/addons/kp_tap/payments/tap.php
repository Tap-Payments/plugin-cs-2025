<?php
/**
 * @copyright 2022 Tap.
 * @author Aamir <support@cs-cart.sg>
 * Date: 16/05/2022
 * Time: 9:48 πμ
 */

use \Tygh\Registry;
use Tap\Transaction;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if (defined('PAYMENT_NOTIFICATION')) {
    $payment_mode = $_GET['tap_id'];
    $payment_mode = mb_substr($payment_mode, 0, 5);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $raw_body = file_get_contents('php://input');
        preg_match('/\["id"\]=>\s+string\(\d+\)\s+"([^"]+)"/', $raw_body, $matches);
        if (isset($matches[1])) {
            $tap_id = $matches[1];
        } 
    }
    else {
        $tap_id = $_GET['tap_id'];
    }
    $pp_response = array();
    $pp_response['order_status'] = 'F';
    $pp_response['reason_text'] = 'Transaction Failed';
    $order_id = !empty($_REQUEST['order_id']) ? (int)$_REQUEST['order_id'] : 0;
  
    $order_info = fn_get_order_info($order_id);
    $order_total = $order_info['total'];
    $order_currency = $order_info['secondary_currency'];
    $processor_data = fn_get_processor_data($order_info['payment_id']);
    $transaction_mode = $processor_data['processor_params']['payment_mode'];
    $private_key = $processor_data['processor_params']['private_key'];
    if ($processor_data['processor_params']['mode']=='test') {
        $private_key = $processor_data['processor_params']['test_private_key'];
    }

   

    if ( $payment_mode == "auth_" ) {
        $url = "https://api.tap.company/v2/authorize/";
    } 
    else {
        $url = "https://api.tap.company/v2/charges/";
    }
    $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url.$tap_id,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array(
                        "authorization: Bearer ".$private_key,
                        "content-type: application/json"
                ),
        ));
    $result  = curl_exec($curl);
    $response = json_decode($result, true);
    //echo '<pre>';var_dump($response);exit;
    $response_currency = $response['currency'];
    $response_amount = $response['amount'];
    if ($response['status'] !='CAPTURED') {
        $pp_response['reason_text'] = 'Transaction Failed';
    }
        if (($response['status'] == 'CAPTURED' && $transaction_mode == 'charge') || ($response['status'] == 'AUTHORIZED' && $transaction_mode == 'authorize')) {

            if (($order_total == $response_amount) && ($order_currency == $response_currency)) {
                $order_info = fn_get_order_info($order_id);
                $txnId = $tap_id;
                if (empty($processor_data)) {
                    $processor_data = fn_get_processor_data($order_info['payment_id']);
                }
                $currency_multiplier = \Tap\Currency::getTapCurrencyMultiplier($processor_data['processor_params']['currency']);
                $private_key = $processor_data['processor_params']['private_key'];
                if($processor_data['processor_params']['mode']=='test') {
                    $private_key = $processor_data['processor_params']['test_private_key'];
                }
                if($order_info && !empty($tap_id)) {
                    $cart_amount = $order_info['total']*$currency_multiplier;
                    $payment_mode = $processor_data['processor_params']['payment_mode'];
                    if ( $transaction_mode == 'authorize' ) {
                        //$capture = Tap\Transaction::capture( $txnId, $data);
                        //fn_print_die($capture);exit;
                        if ( $response['status'] !== 'AUTHORIZED' ) {
                            $message = implode(',', ('Tap payment failed').("<br>").('ID').(':'). ($tap_id.("<br>").('Payment Type :') . ($capture['source']['payment_method']).("<br>").('Payment Ref:'). ($capture['reference']['payment'])));
                            $pp_response['transaction_id'] = $txnId;
                            $pp_response['order_status'] = 'F';
                            $pp_response['reason_text'] = $message;
                            $transaction_failed = true;
                        } elseif ($response['status'] == 'AUTHORIZED') {
                            $pp_response['order_status'] = 'O';
                            $pp_response['reason_text'] = __("delayed");
                            $pp_response['transaction_id'] = $tap_id;
                            $pp_response['kp_tap.order_time'] = kp_tap_datetime_to_human($capture['transaction']['created']);
                            $pp_response['kp_tap.currency_code'] = $order_info['secondary_currency'];
                            $pp_response['amount_auth'] = $order_info["total"];
                            $pp_response['captured'] = 'N';
                            array_filter($pp_response);
                        } else {
                            $transaction_failed = true;
                        }
                        
                        
                    } else {

                        $data = array(
                            'currency'   => $processor_data['processor_params']['currency'],
                            'amount'     => $cart_amount,
                        );
                      
                        if ( $response['status'] !== 'CAPTURED' && $response['status'] !== 'AUTHORIZED') {
                            $message = 
                                "Tap payment failed<br>" .
                                "ID: " . $tap_id . "<br>" .
                                "Payment Type: " . $capture['source']['payment_method'] . "<br>" .
                                "Payment Ref: " . $capture['reference']['payment'];
                            $pp_response['transaction_id'] = $txnId;
                            $pp_response['order_status'] = 'F';
                            $pp_response['reason_text'] = $message;

                        } elseif ( $response['status'] == 'CAPTURED') {
                            $pp_response['order_status'] = 'P';
                            $pp_response['reason_text'] = __("captured");
                            $pp_response['transaction_id'] = $txnId;
                            $pp_response['kp_tap.order_time'] = kp_tap_datetime_to_human($capture['transaction']['created']);
                            $pp_response['kp_tap.currency_code'] = $order_info['secondary_currency'];
                            $pp_response['amount_capt'] = $order_info["total"];
                            $pp_response['captured'] = 'Y';
                            fn_change_order_status($order_id, 'P', '', fn_get_notification_rules(true));
             
                            array_filter($pp_response);
                        } else {
                            $transaction_failed = true;
                        }
                    }

                }
            }
            else {
                    if ($transaction_mode == 'charge') {
                        $refund_url = "https://api.tap.company/v2/refunds/";
                        $response_currency = 'USD';
                        $refund_object["charge_id"] = $tap_id;
                        $refund_object["amount"]   = $response_amount;
                        $refund_object["currency"]  = $response_currency;
                        $refund_object["reason"]           = "Order currency and response currency mismatch(fraudulent)";
                        $refund_object["post_url"] = ""; 
                        $curl = curl_init();
                            curl_setopt_array($curl, array(
                                CURLOPT_URL => $refund_url,
                                    CURLOPT_RETURNTRANSFER => true,
                                    CURLOPT_ENCODING => "",
                                    CURLOPT_MAXREDIRS => 10,
                                    CURLOPT_TIMEOUT => 30,
                                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                    CURLOPT_CUSTOMREQUEST => "POST",
                                    CURLOPT_POSTFIELDS => json_encode($refund_object),
                                    CURLOPT_HTTPHEADER => array(
                                        "authorization: Bearer ".$private_key,
                                        "content-type: application/json"
                                        ),
                                )
                            );

                        $refund_response = curl_exec($curl);
                        $refund_response = json_decode($refund_response);

                        $err = curl_error($curl);
                        curl_close($curl);
                        $pp_response['order_status'] = 'D';
                        $pp_response['reason_text'] = 'Transaction Failed';
                        $pp_response['transaction_id'] = $refund_response->id;
                        $pp_response['kp_tap.currency_code'] = $order_info['secondary_currency'];
                        $pp_response['amount_capt'] = $order_info["total"];
                        $pp_response['captured'] = 'N';
                        //array_filter($pp_response);
                    }
                    else {
                        $void_url = 'https://api.tap.company/v2/authorize/'.$tap_id.'/void';
                        $curl = curl_init();
                            curl_setopt_array($curl, array(
                                CURLOPT_URL => $void_url,
                                    CURLOPT_RETURNTRANSFER => true,
                                    CURLOPT_ENCODING => "",
                                    CURLOPT_MAXREDIRS => 10,
                                    CURLOPT_TIMEOUT => 30,
                                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                    CURLOPT_CUSTOMREQUEST => "POST",
                                    //CURLOPT_POSTFIELDS => {},
                                    CURLOPT_HTTPHEADER => array(
                                        "authorization: Bearer ".$private_key,
                                        "content-type: application/json"
                                        ),
                                )
                            );
                        $void_response = curl_exec($curl);
                        $void_response = json_decode($void_response);
                        $err = curl_error($curl);
                        curl_close($curl);
                        $pp_response['order_status'] = 'D';
                        $pp_response['reason_text'] = 'Transaction Failed';
                        $pp_response['transaction_id'] = $void_response->id;
                        $pp_response['kp_tap.currency_code'] = $order_info['secondary_currency'];
                        $pp_response['amount_capt'] = $order_info["total"];
                        $pp_response['captured'] = 'N';
                        array_filter($pp_response);
                    }
                }

           
        }

      
    if (fn_check_payment_script('tap.php', $order_id)) {
        fn_finish_payment($order_id, $pp_response);
        fn_order_placement_routines('route', $order_id);
    }
}

else {
    $public_key = $processor_data['processor_params']['public_key'];
    if ($processor_data['processor_params']['mode']=='test') {
        $public_key = $processor_data['processor_params']['test_public_key'];
    }
    $order_id = $order_info['order_id'];
    $payment_id = db_get_field("SELECT ?:orders.payment_id FROM ?:orders WHERE ?:orders.order_id = ?i", $order_id);
    $processor_data = fn_get_payment_method_data($payment_id);
    $mode = $processor_data['processor_params']['mode'];
    $language = $processor_data['processor_params']['language'];
    $merchant_id = $processor_data['processor_params']['merchant_id'];
    $CurrencyCode = $order_info['secondary_currency'];
    $total = $order_info['total'];
    $uimode = $processor_data['processor_params']['uimode'];
    $payment_mode = $processor_data['processor_params']['payment_mode'];
    $save_card = $processor_data['processor_params']['save_card'];

    if ($save_card == 'yes') {
       $save_card = 'true';
    }
    else {
        $save_card = 'false';
    }
    
    if ($language == 'english') {
       $language = 'en';
    }
    else {
        $language = 'ar';
    }
    $return_url = fn_url("payment_notification.notify?payment=tap&order_id=$order_id&secretkey=$activesk", AREA, 'current');
    $payed_url =  fn_url("payment_notification.payed?payment=tap&order_id=$order_id", AREA, 'current');
    $backgrounimg = fn_url('checkout');
    $ref = '';
    $private_key = $processor_data['processor_params']['private_key'];

    if ($processor_data['processor_params']['mode']=='test') {
        $private_key = $processor_data['processor_params']['test_private_key'];
    }

    $hashstring = 'x_publickey'.$public_key.'x_amount'.$total.'x_currency'.$CurrencyCode.'x_transaction'.$ref.'x_post'.$payed_url;

    $hash = hash_hmac('sha256', $hashstring, $private_key);

    $checkout_url = fn_url('checkout.checkout');

    $payment_mode = $processor_data['processor_params']['payment_mode'];
    if ($payment_mode == 'charge') {
        $charge_url = 'https://api.tap.company/v2/charges';
    }
    else {
        $charge_url = "https://api.tap.company/v2/authorize/";
    }
    $order_it = [];
    $shipping_carrier = [];
    $shippings = $order_info['shipping'];
    //var_dump($shippings);exit;
    foreach($shippings as $shipping) {
        $shipping_carrier['amount'] = $shipping['rate'];
        $shipping_carrier['currency'] = $CurrencyCode;
        $shipping_carrier['description'] = $shipping['description'];
        $shipping_carrier['provider'] = $shipping['shipping'];
        $shipping_carrier['service'] = $shipping['shipping'];
    }
    $json_string_shipping = json_encode($shipping_carrier);
    $clean_string_shipping = stripcslashes($json_string_shipping);
    $shipping_array = json_decode($clean_string_shipping);
    $order_items = $order_info['products'];
    $total_quantity = 0;
    foreach($order_items as $order_item) {
        $order_it['name'] = $order_item['product'];
        $order_it['description'] = $order_item['product'];
        $order_it['quantity'] = intval($order_item['amount']);
        $order_it['currency'] = $CurrencyCode;
        $order_it['amount'] = floatval($order_item['price']);
    }
    $json_string = json_encode($order_it);
    $clean_string = stripslashes($json_string);
    $final_array = json_decode($clean_string, true);
    $phone = $order_info['phone'];
    $country_code = preg_match('/^\+(\d{1,3})/', $phone, $matches);

    $country_code = $matches[1] ?? null;
    $local_number = preg_replace('/^\+'.$country_code.'/', '', $phone);
    $lenght = 5;
    $randomString = md5(uniqid(rand(), true));
    $randomString = substr($randomString, 0, $length);
    $requsetId = 'cs-cart_'.$randomString;


}
    
    if ( $uimode == 'redirect') {
                $source_id = 'src_all';
                $charge_url = $charge_url;
                $trans_object["amount"]                   = $total;
                $trans_object["currency"]                 = $CurrencyCode;
                $trans_object["threeDsecure"]             = true;
                $trans_object["save_card"]                = false;
                $trans_object["description"]              = $order_id;
                $trans_object["statement_descriptor"]     = 'Sample';
                $trans_object["metadata"]["udf1"]         = 'test';
                $trans_object["metadata"]["udf2"]         = 'test';
                $trans_object["reference"]["transaction"] = $order_id;
                $trans_object["hashstring"]                     = $hash;
                $trans_object["reference"]["order"]       = $orderid;
                $trans_object["receipt"]["email"]         = false;
                $trans_object["receipt"]["sms"]           = true;
                $trans_object["customer"]["first_name"]   = $order_info['firstname'];
                $trans_object["customer"]["last_name"]    = $order_info['lastname'];
                $trans_object["customer"]["email"]        = $order_info['email'];
                $trans_object["customer"]["phone"]["country_code"]  = $country_code;
                $trans_object["customer"]["phone"]["number"] = $local_number;
                $trans_object["source"]["id"] = $source_id;
                $trans_object["post"]["url"]  = $payed_url;
                $trans_object["redirect"]["url"] = $return_url;
                $frequest = json_encode($trans_object);
                $frequest = stripslashes($frequest);
                
                
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => $charge_url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POSTFIELDS => $frequest,
                    CURLOPT_HTTPHEADER => array(
                        "authorization: Bearer ".$private_key,
                        "content-type: application/json",
                        "accepts: application/json",
                        "lang_code: " . $language
                    ),
                ));

                $response = curl_exec($curl);
                //var_dump($response);exit;
                $err = curl_error($curl);
                $obj = json_decode($response);
                $charge_id   = $obj->id;
                $redirct_Url = $obj->transaction->url;
            
                        fn_redirect($obj->transaction->url, true);
    }
    ?>  
  <!--   <div id="tap-checkout-element">
        <button id="open-checkout">Open Checkout</button>
    </div> -->
    <link rel="shortcut icon" href="//goSellJSLib.b-cdn.net/v1.6.1/imgs/tap-favicon.ico" />
    <link href="//goSellJSLib.b-cdn.net/v1.6.1/css/gosell.css" rel="stylesheet" />
    <script type="text/javascript" src="//goSellJSLib.b-cdn.net/v1.6.1/js/gosell.js"></script>
    <script src="https://tap-sdks.b-cdn.net/checkout/1.2.0-beta/index.js"></script>
    <script src="https://code.jquery.com/jquery-1.9.1.min.js"></script>
    <script>
        var Total = 0 ;
       var returnUrl = <?php echo json_encode($return_url); ?>;
       var order_id = <?php echo json_encode($order_id); ?>;
       var CstFName = <?php echo json_encode($order_info['firstname']); ?>;
       var CstLName = <?php echo json_encode($order_info['firstname']); ?>;
       var CstEmail = <?php echo json_encode($order_info['email']); ?>;
       var CountryCode = <?php echo json_encode($country_code);?>;
       var CstMobile = <?php echo json_encode($local_number); ?>;
       var CurrencyCode = <?php echo json_encode($CurrencyCode); ?>;
       Total = <?php echo json_encode($total); ?>;
       var activepk = <?php echo json_encode($public_key); ?>;
       var MerchantID = <?php echo json_encode($merchant_id)?>;
       var language = <?php echo json_encode($language); ?>;
       var payment_mode = <?php echo json_encode($payment_mode); ?>;
       var uimode = <?php echo json_encode($uimode); ?>;
       var save_card = <?php echo json_encode($save_card); ?>;
       var payed_url = <?php echo json_encode($payed_url); ?>;
       var bckimg    = <?php echo json_encode($backgrounimg); ?>;
       var return_url = <?php echo json_encode($return_url);?>;
       var final_array = <?php echo json_encode($final_array);?>;
       var shipping_array = <?php echo json_encode($shipping_array);?>;
       var checkout_url = <?php echo json_encode(fn_url('checkout.checkout'));?>;
       var requestId = <?php echo json_encode($requestId);?>;
       var hash = <?php echo json_encode($hash);?>;
       

    </script>
    <script>
        $( document ).ready(function() {
            const { renderCheckoutElement } = window.TapSDKs;
            let unmount = null;
            const stopCheckout = () => { unmount && unmount(); };
            const startCheckout = () => {
                
                const checkoutElement = renderCheckoutElement("checkout-element",
                    {
                        open: true,
                        onClose: () => { 
                            stopCheckout(); 
                        },
                        onSuccess: (res) => { 
                            console.log(payed_url);
                            console.log(res.chargeId); 
                            // console.log(payed_url+tap_id);
                            // return;
                            window.location = `${payed_url}&tap_id=${res.chargeId}`;
                        },
                        onError: (error) => { console.log({ error }); },
                        
                        "checkoutMode": "popup",
                        "language": "en",
                        "themeMode": "dark",
                        "supportedCurrencies": "ALL",
                        "supportedRegions": [
                            // "GLOBAL"
                        ],
                        // "supportedCountries": [
                        //     "ALL"
                        // ],
                        "supportedPaymentTypes": [
                            // "BNPL",
                            // "CARD"
                        ],
                       "supportedPaymentMethods": "ALL",
                        "supportedSchemes": [],
                        "cardOptions": {
                            "showBrands": true,
                            "showLoadingState": true,
                            "collectHolderName": true,
                            "cardNameEditable": true,
                            "cardFundingSource": "all",
                            "saveCardOption": "none",
                            "forceLtr": true
                        },
                        "selectedCurrency": CurrencyCode,
                        "paymentType": "ALL",
                        "gateway": {
                            "merchantId": MerchantID,
                            "publicKey": activepk
                        },
                        "hashString": hash,
                        "customer": {
                            "firstName": CstFName,
                            "lastName": CstLName,
                            "phone": {
                                "countryCode": CountryCode,
                                "number": CstMobile
                            },
                            "email": CstEmail
                        },
                        "transaction": {
                            "mode": "charge",
                            "charge": {
                                "saveCard": false,
                                "threeDSecure": true,
                                "description": "",
                                "statement_descriptor": "",
                                "reference": {
                                    "transaction": "quote_6",
                                    "order": ""
                                },
                                "redirect": {
                                    "url": return_url
                                },
                                "post": payed_url,
                                "metadata": {
                                    "requestId": requestId
                                },
                                "platform": {
                                    "id": "commerce_platform_E1vz12259218mMT23hI3J371"
                                }
                            }
                        },
                        "amount": Total,
                        "order": {
                            "amount": Total,
                            "currency": CurrencyCode,
                            "items": [
                                final_array
                            ],
                            "shipping" : shipping_array
                        }
                    });
                unmount = checkoutElement.unmount;
            };
            console.log(final_array);
            startCheckout();
        });
    </script>
<?php exit(); ?>
