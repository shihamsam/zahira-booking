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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google_calendar' => [
        // The Google Calendar ID to sync confirmed bookings into.
        // Found under "Integrate calendar" in Google Calendar settings.
        'calendar_id' => env('GOOGLE_CALENDAR_ID'),

        // Absolute path to the service account credentials JSON file.
        // Download from Google Cloud Console → IAM → Service Accounts → Keys.
        // Store at e.g. storage/app/private/google-credentials.json
        'credentials_path' => env('GOOGLE_SERVICE_ACCOUNT_JSON'),

        // IANA timezone for event timestamps (should match the facility location).
        'timezone' => env('GOOGLE_CALENDAR_TIMEZONE', 'Asia/Colombo'),
    ],

];
