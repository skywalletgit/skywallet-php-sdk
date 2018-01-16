## Usage

To be able to use SkyWallet sdk need to initialize it first. For that purpose need to get corresponding token and publicKey from your merchant account on https://skywallet.com. For testing purposes need to add additional property to config:

``` php
$skywallet_data = array(
    'publicKey'  => 'publicKey',
    'apiKey'     => 'apiKey'
);

$order = new SkyWallet($skywallet_data);
```

## Create new order

Once Customer is trying to pay for some item by X12 the following call should be made. Need to provide corresponding amount in X12 that customer need to pay. Invoice number on merchant side and item SKU. This call registers the order request in our system and provides corresponding integrated address from SkyWallet main wallet. Integrated address should be provided to customer to make required payment to that address. Once above mentioned called is successfully made it should register corresponding order in SkyWallet system and information about that will be available in corresponding section on skywallet.com.

``` php
$create_data = array(
    'requestedAmount'   => 5.02,
    'invoiceNumber'     => 'TESTINVOICENUMBER',
    'SKU'               => 'SKUTEST',
    'language'          => 'en',
    'rate'              => $exchange_rate->result->rate,
    'price'             => 150,
    'currency'          => 'USD',
    'description'       => 'TEST DESCRIPTION',
    'backToMerchantUrl' => 'http://skywallet.com/webhook'
);

$createorder = $order->createOrder($create_data);

print_r($createorder);
```

## Get exchange rate

``` php
$exchange_data = array(
    'base'  => 'X12',
    'quote' => 'EUR'
);

$exchange_rate = $order->getExchangeRate($exchange_data);
print_r($exchange_rate);    
```


## Webhook

Webhook is defined to notify merchant about order status changes. It should be called in following cases:

 - Order status changed to “fulfilled” and has transactions with less than 10 blocks behind. Considering orders still unverified.
 - Order status is “fulfilled” and transactions have more than 10 blocks behind. Considering order as verified.
 - Order is “expired” but has transactions with 10 blocks behind. Considering order as verified.
 - Order status is “expired” but no transaction received for it. When calling provided webhook making a POST request to given url from merchant and sending the following information:


``` json
{
    "transactionStatus": "unverified",
    "orderStatus": "fulfilled",
    "paymentId": "3a1a12cfcf01de09",
    "supportId": "TLIH2JXD",
    "invoiceNumber": "code_finalflow_po",
    "SKU": "98987ABC879798",
    "Signature": "asdf23qafds9j29ajfas9fj29fajsa9fj29fwajfao9j"
}
```

Once all the transfers related to given order are verified (more than 10 blocks behind) the transactionStatus field will be equal to “verified”. Signature should be used to verify the JSON data in response with public key provided to merchant. So far webhook is getting called for each order maximum two times. If webhook is not available for some reasons retrying to call it 20 times. In case of failure order status is switching to “failed”. And email will be send to merchant requesting some action. The following response from webhook is expected: Http status code: 200

``` json
{
    "status": true
}
```

Marking order as “failed” in case of getting following response: Http status code: 200

``` json
{
    "status": false
}
```

Need to use following call to verify that the data received on webhook is originally from SkyWallet:

``` php
$verify = $order->verify($sigdata);
print_r($verify);    
```