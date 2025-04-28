<?php
/**
 * @copyright 2022 tap.
 * @author Aamir<support@cs-cart.sg>
 * Date: 16/05/2022
 * Time: 11:28 πμ
 */

namespace Tap;

use Tygh\Enum\OrderDataTypes;

class CaptureDelayed {

    public function capture(&$order_info, $txnId) {

        $processor_data = fn_get_processor_data($order_info['payment_id']);
        $private_key = $processor_data['processor_params']['private_key'];
        if($processor_data['processor_params']['mode']=='test') {
            $private_key = $processor_data['processor_params']['test_private_key'];
        }
        \Tap\Client::setKey( $private_key );
        $currency_multiplier = \Tap\Currency::getTapCurrencyMultiplier($processor_data['processor_params']['currency']);
        $cart_amount = \Tap\Currency::toTapCurrency($order_info['total'],$currency_multiplier);
        $data = array(
            'currency'   => $processor_data['processor_params']['currency'],
            'amount'     => $cart_amount,
        );
        $capture = \Tap\Transaction::capture( $txnId, $data );
        $update = false;
        $pp_response = [];
        if ( is_array( $capture ) && ! isset( $capture['transaction'] ) ) {
            $message = implode(',', $capture);
            $pp_response['reason_text'] = $message;
            $update = true;

        } elseif ( ! empty( $capture['transaction'] ) ) {
//var_dump($capture['transaction']['currency']);exit;
            $pp_response['reason_text'] = __("captured");
            $pp_response['transaction_id'] = $txnId;
            $pp_response['kp_tap.order_time'] = kp_tap_datetime_to_human($capture['transaction']['created']);
            $pp_response['kp_tap.currency_code'] = $capture['transaction']['currency'];
            $pp_response['kp_tap.authorized_amount'] = ( $capture['transaction']['amount'] / $currency_multiplier );
            $pp_response['amount_capt'] = $capture['transaction']['capturedAmount'] / $currency_multiplier ;
            $pp_response['captured'] = 'Y';
            array_filter($pp_response);
            $update = true;
        }
        if($update) {
            fn_update_order_payment_info($order_info['order_id'], $pp_response);
            $order_info['payment_info'] = $this->reloadPaymentInfo($order_info['order_id']);
        }
    }

    public function refund(&$order_info, $txnId, $amount) {
        $processor_data = fn_get_processor_data($order_info['payment_id']);
        $private_key = $processor_data['processor_params']['private_key'];
        if($processor_data['processor_params']['mode']=='test') {
            $private_key = $processor_data['processor_params']['test_private_key'];
        }
        \Tap\Client::setKey( $private_key );
        $currency_multiplier = \Tap\Currency::getTapCurrencyMultiplier($processor_data['processor_params']['currency']);
        $cart_amount = \Tap\Currency::toTapCurrency($amount,$currency_multiplier);

       
        $data = array(
            'amount'     => $cart_amount,
            'descriptor'   => $processor_data['processor_params']['descriptor'],
        );
        $capture = \Tap\Transaction::refund( $txnId, $data );
    
            $refund_url = 'https://api.tap.company/v2/refunds';
            $refund_request['charge_id'] = $txnId;
            $refund_request['amount'] = $amount;
            $refund_request['currency'] = $order_info['secondary_currency'];
            $refund_request['description'] = "Description";
            $refund_request['reason'] = "Refund";
            $json_request = json_encode($refund_request);

            $curl = curl_init();
            curl_setopt_array($curl, array(
                    CURLOPT_URL => "https://api.tap.company/v2/refunds",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POSTFIELDS => $json_request,
                    CURLOPT_HTTPHEADER => array(
                            "authorization: Bearer ".$private_key,
                            "content-type: application/json"
                    ),
                )
            );

            $response = curl_exec($curl);
            $response = json_decode($response);

            //var_dump($order_info["total"]);exit;

        if ($response->id) {
           
         if ( $response->status == 'REFUNDED') {
            $pp_response['reason_text'] = __("refunded");
            $pp_response['transaction_id'] = $response->id;
            $pp_response['kp_tap.order_time'] = kp_tap_datetime_to_human($capture['transaction']['created']);
            $pp_response['kp_tap.currency_code'] = $order_info['secondary_currency'];
            $pp_response['amount_capt'] = $order_info["total"];
            $pp_response['amount_refu'] = $response->amount;
            $pp_response['captured'] = 'Y';
            $pp_response['refunded'] = 'Y';
            $pp_response['order_status'] = 'D';
            array_filter($pp_response);
            $update = true;
        }
        else {            
            $update = false;
        }
    }
        if($update) {
            fn_update_order_payment_info($order_info['order_id'], $pp_response);
            $order_info['payment_info'] = $this->reloadPaymentInfo($order_info['order_id']);
        }
        else {
            $pp_response['reason_text'] = __("Can't refund, check charge and refund amount");
            array_filter($pp_response);
        }
    }


    public function void(&$order_info, $txnId) {
        $amount = $order_info['total'];
        $processor_data = fn_get_processor_data($order_info['payment_id']);
        $private_key = $processor_data['processor_params']['private_key'];
        if($processor_data['processor_params']['mode']=='test') {
            $private_key = $processor_data['processor_params']['test_private_key'];
        }
        \Tap\Client::setKey( $private_key );
        $currency_multiplier = \Tap\Currency::getTapCurrencyMultiplier($processor_data['processor_params']['currency']);
        $cart_amount = \Tap\Currency::toTapCurrency($amount,$currency_multiplier);
        $data = array(
            'amount'     => $cart_amount,
        );
        $capture = \Tap\Transaction::void( $txnId, $data );
        if ( is_array( $capture ) && ! isset( $capture['transaction'] ) ) {
            $message = implode(',', $capture);
            $pp_response['reason_text'] = $message;
            $update = true;
        } elseif ( ! empty( $capture['transaction'] ) ) {
            $pp_response['reason_text'] = __("voided");
            $pp_response['transaction_id'] = $txnId;
            $pp_response['kp_tap.order_time'] = kp_tap_datetime_to_human($capture['transaction']['created']);
            $pp_response['kp_tap.currency_code'] = $capture['transaction']['currency'];
            $pp_response['voided_amount'] = $capture['transaction']['voidedAmount'] / $currency_multiplier ;
            $pp_response['captured'] = 'N';
            $pp_response['voided'] = 'Y';
            array_filter($pp_response);
            $update = true;
        }
        if($update) {
            fn_update_order_payment_info($order_info['order_id'], $pp_response);
            $order_info['payment_info'] = $this->reloadPaymentInfo($order_info['order_id']);
        }
    }

    private function reloadPaymentInfo($order_id) {
        $paymentInfo = false;
        $additional_data = db_get_hash_single_array("SELECT type, data FROM ?:order_data WHERE order_id = ?i", array('type', 'data'), $order_id);
        if (!empty($additional_data[OrderDataTypes::PAYMENT])) {
            $paymentInfo = unserialize(fn_decrypt_text($additional_data[OrderDataTypes::PAYMENT]));
        }
        return $paymentInfo;
    }
}