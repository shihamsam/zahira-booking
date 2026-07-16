<?php

return [
    // Additional email addresses (comma separated in .env) that should receive
    // a copy of the "new booking" notification, on top of every admin user.
    'notify_extra_emails' => array_filter(array_map(
        'trim',
        explode(',', env('BOOKING_NOTIFY_EXTRA_EMAILS', ''))
    )),

    // Bank deposit details shown on the booking confirmation page.
    'bank' => [
        'bank_name' => env('BOOKING_BANK_NAME', 'Sample Bank PLC'),
        'account_name' => env('BOOKING_BANK_ACCOUNT_NAME', 'Zahira College Ground Fund'),
        'account_number' => env('BOOKING_BANK_ACCOUNT_NUMBER', '0000000000'),
        'branch' => env('BOOKING_BANK_BRANCH', 'Colombo Branch'),
    ],

    // WhatsApp number depositors should send their receipt to.
    'whatsapp_number' => env('BOOKING_WHATSAPP_NUMBER', '94770000000'),

    // How many months ahead the public calendar allows booking.
    'booking_window_months' => 3,
];
