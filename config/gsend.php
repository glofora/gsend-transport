<?php

return [

    /*
    |--------------------------------------------------------------------------
    | gSend API Key
    |--------------------------------------------------------------------------
    |
    | Your gSend REST API authentication key provided by Glofora.
    | Leave empty or unset in local/testing environments to enable simulation mode.
    |
    */

    'api_key' => env('GSEND_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | gSend API Endpoint
    |--------------------------------------------------------------------------
    |
    | The full URL endpoint to the gSend mail delivery service API.
    |
    */

    'endpoint' => env('GSEND_ENDPOINT', 'https://gsend.glofora.com/api/v1/send-mail'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | The HTTP request timeout in seconds when communicating with gSend API.
    |
    */

    'timeout' => (int) env('GSEND_TIMEOUT', 15),

    /*
    |--------------------------------------------------------------------------
    | Default Sender Name
    |--------------------------------------------------------------------------
    |
    | Default "From" name to attach to outgoing mail requests if not specified
    | explicitly on the mail message. Defaults to your APP_NAME.
    |
    */

    'from_name' => env('GSEND_FROM_NAME', env('APP_NAME')),

];
