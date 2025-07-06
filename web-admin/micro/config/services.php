<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'golang_api' => [
        'url' => env('GOLANG_API_URL'),
    ],

    'huggingface' => [
        'token' => env('HF_TOKEN'),
        'endpoint' => env('HF_ENDPOINT'),
        'model' => env('HF_MODEL'),
    ],
    'imagekit' => [
        'private_key' => env('IMAGEKIT_PRIVATE_KEY'),
        'upload_url' => env('IMAGEKIT_UPLOAD_URL'),
    ],


];
