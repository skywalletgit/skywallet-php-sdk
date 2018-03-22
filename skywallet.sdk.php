<?php

class SkyWallet{
    private $publicKey = "";
    private $token = "";
    private $reqUrl = "https://app.skywallet.com/api";

    function __construct($args){
        if(!isset($args['publicKey'])){
            return self::errorMessage('publicKey is required field');
        }

        if(!isset($args['apiKey'])){
            return self::errorMessage('apiKey is required field');
        }

        if(isset($args['reqUrl'])){
            $this->reqUrl = $args['reqUrl'];
        }


        $this->publicKey = $args['publicKey'];
        $this->token = $args['apiKey'];
    }

    public function createOrder($args){

        if(!isset($args['requestedAmount'])){
            return self::errorMessage('Requested amount is required field');
        }

        if(!isset($args['invoiceNumber'])){
            return self::errorMessage('Invoice number is required field');
        }

        if(!isset($args['SKU'])){
            return self::errorMessage('Sku is required field');
        }

        $data['requestedAmount'] = $args['requestedAmount'];
        $data['invoiceNumber'] = (string) $args['invoiceNumber'];
        $data['SKU'] = (string) $args['SKU'];

        if(isset($args['language'])) $data['language'] = $args['language'];
        if(isset($args['rate'])) $data['rate'] = $args['rate'];
        if(isset($args['price'])) $data['price'] = $args['price'];
        if(isset($args['currency'])) $data['currency'] = $args['currency'];
        if(isset($args['description'])) $data['description'] = $args['description'];
        if(isset($args['backToMerchantUrl'])) $data['backToMerchantUrl'] = $args['backToMerchantUrl'];


        $requestOptions = $this->requestOptions('POST', '/order', $data);
        $request = self::request($requestOptions);
        $request->status = (int) $request->status;
        return $request;
    }

    public function getExchangeRate($args){
        if(!isset($args['base'])){
            return self::errorMessage('base is required');
        }

        if(!isset($args['quote'])){
            return self::errorMessage('quote is required');
        }
        $requestOptions = $this->requestOptions('GET', '/rate/'.$args['base'].'/'.$args['quote'], '');
        $request = self::request($requestOptions);
        $request->status = (int) $request->status;
        return $request;
    }

    public function verify($sigdata){

        if(!is_array($sigdata)) {

            if(is_object($sigdata)) {
                $sigdata = (array) $sigdata;
            }else{
                return self::errorMessage('Verification failed (Invalid data type)');
            }

        }

        $signature = $sigdata['signature'];
        unset($sigdata['signature']);

        $bodyhash = md5(json_encode($sigdata));
        $sigvalid = openssl_verify($bodyhash, hex2bin($signature), $this->publicKey, OPENSSL_ALGO_SHA256);

        if($sigvalid === 1) {
            return true;
        } else {
            return false;
        }

    }

    private function requestOptions($type, $url, $data = ''){
        $options['method'] = $type;
        $options['url'] = $this->reqUrl.$url;
        $options['headers'] = array (
            'Authorization: sky-wallet <'.$this->token.'>',
            'Content-type: application/json'
        );
        if($type == 'POST'){
            $options['body'] = $data;
        }
        $options['json'] = true;
        return $options;
    }



    static function request($options){

        $ch = curl_init($options['url']);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $options['type']);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $options['headers']);
        if($options['method'] == 'POST'){
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($options['body']));
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);

        if(self::isJson($result)){
            return json_decode($result);
        }else{
            return self::errorMessage('Cant get data from server');
        }


    }

    static function errorMessage($message = "Unknown error", $errorCode = 552){
        $result = new stdClass();
        $result->status = false;
        $result->message = $message;
        $result->code = $errorCode;
        return $result;
    }

    static function successMessage($message = "Success"){
        $result = new stdClass();
        $result->status = true;
        $result->message = $message;
        return $result;
    }

    static function isJson($string) {
     json_decode($string);
     return (json_last_error() == JSON_ERROR_NONE);
    }
}
?>