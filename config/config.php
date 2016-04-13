<?php

return [

    /*
    |--------------------------------------------------------------------------
    | My Merchant ID
    |--------------------------------------------------------------------------
    |
    | My Merchant ID
    |
    */

    'merchant_id' => 'A1486m8YuL',

    /*
    |--------------------------------------------------------------------------
    | Api url
    |--------------------------------------------------------------------------
    |
    | Api url (defaults to testing environment)
    |
    */

    'api_url' => 'https://iapi.iplatebnibrana.csob.cz/api/v1',

    /*
    |--------------------------------------------------------------------------
    | Log
    |--------------------------------------------------------------------------
    |
    | Log operations
    |
    */

    'log' => true,

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    |
    | Environment setting ("test", "production")
    |
    */

    'environment' => 'test',

    /*
    |--------------------------------------------------------------------------
    | Allowed currencies
    |--------------------------------------------------------------------------
    |
    | Allowed currencies: CZK, EUR, USD, GBP
    |
    */

    'allowed_currencies' => ['czk' => 'CZK', 'eur' => 'EUR', 'usd' => 'USD', 'gbp' => 'GBP'],

    /*
    |--------------------------------------------------------------------------
    | Allowed languages
    |--------------------------------------------------------------------------
    |
    | Allowed languages: CZ, EN, DE, SK
    |
    */

    'allowed_languages' => ['cs' => 'CZ', 'en' => 'EN', 'de' => 'DE', 'sk' => 'SK'],

    'fallback_language' => 'EN',

    /*
    |--------------------------------------------------------------------------
    | Ignore Wrong Payment Status Error
    |--------------------------------------------------------------------------
    |
    | Ignore Wrong Payment Status Error allows some operations
    | like reverse, refund, close to work even if the payment
    | status does not return correct value.
    |
    */

    'ignoreWrongPaymentStatusError' => false,

    /*
    |--------------------------------------------------------------------------
    | CSOB API version
    |--------------------------------------------------------------------------
    |
    | Important because: CAUTION: In version v1, at least 
    | 1 item (e.g. “credit charge”) and at most 2 items must be in the 
    | cart (e.g. “purchase for my shop” and “shipment costs”). The limit 
    | is caused by the graphic layout of the payment gateway, 
    | in another version the limit will be much higher.
    | @see https://github.com/csob/paymentgateway/wiki/eAPI-1.5-EN
    |
    */

    'version' => 1.5,

];
