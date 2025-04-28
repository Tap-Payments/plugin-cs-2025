<?php
namespace Tap;
/**
 * Class Adapter
 * @package Tap
 * The adapter class taking care of the calls to the api.
 *
 * The purpose of this is to abstract the requests
 * so that this can be changed depending on the environment.
 *
 * @version    1.0.0
 */
if ( ! class_exists( 'Tap\\Adapter' ) ) {
    class Adapter {

        
        private $apiKey;

        /**
         * Adapter constructor.
         *
         * @param $privateApiKey
         */
        public function __construct( $privateApiKey ) {
            if ( $privateApiKey ) {
                $this->setApiKey( $privateApiKey );
            } else {
                trigger_error( 'Private Key is missing!', E_USER_ERROR );

                return null;
            }
        }

        /**
         * @param $key
         * set the api key.
         */
        public function setApiKey( $key ) {
            $this->apiKey = $key;
        }

        /**
         * @param $url this is required, do not use the full url,
         * only prepend the params eg: transactions/' . $transactionId . '/captures'
         * @param $data this is optional
         * Actual call to the api via curl.
         *
         * @return bool|mixed
         */
        public function request( $url, $data = null, $httpVerb = 'post' ) {

            $payment_mode = $_GET['tap_id'];
            $payment_mode = mb_substr($payment_mode, 0, 5);

            if ( $payment_mode == "auth_" ) {
                $url = "https://api.tap.company/v2/authorize/";
            } 
            else{
                $url = "https://api.tap.company/v2/charges/";
            }
            $curl = curl_init();
                curl_setopt_array($curl, array(
                CURLOPT_URL => $url.$_GET['tap_id'],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "GET",
                    CURLOPT_HTTPHEADER => array(
                        "authorization: Bearer ".$this->apiKey,
                        "content-type: application/json"
                    ),
                ));
                $result  = curl_exec($curl);
                //var_dump($this->apiKey);exit;
               // $response = json_decode($response);
            
            //$result   = curl_exec( $ch );
            //$httpCode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
           // curl_close( $ch );
            $output = json_decode( $result, true );
            if ( $httpCode >= 200 || $httpCode <= 299 ) {
                return $output;
            } else {
                return false;
            }
        }

    }
}
