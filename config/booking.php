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

    // Support / enquiry phone number shown to the public.
    'support_phone' => env('BOOKING_SUPPORT_PHONE', ''),

    // How many months ahead the public calendar allows booking.
    'booking_window_months' => 3,

    // Per-resource pricing definitions keyed by resource slug.
    // Each slot entry has a 'type' ('flat' or 'hourly') and a 'rate' in LKR.
    // Flat slots: rate applies once per selected date.
    // Hourly slots: rate * hours per selected date.
    'pricing' => [
        'zahira-green-ground' => [
            'daytime'       => ['type' => 'flat',   'rate' => 6000,  'label' => 'Daytime (8:30 AM – 6:30 PM)',  'default_start' => '08:30', 'default_end' => '18:30'],
            'night_4lights' => ['type' => 'hourly', 'rate' => 3500,  'label' => 'Night — 4 Lights (6:30 PM – 6:30 AM)', 'default_start' => '18:30', 'default_end' => '06:30'],
            'night_2lights' => ['type' => 'hourly', 'rate' => 2000,  'label' => 'Night — 2 Lights (6:30 PM – 6:30 AM)', 'default_start' => '18:30', 'default_end' => '06:30'],
        ],
        'azwar-hall' => [
            'full_day' => ['type' => 'flat', 'rate' => 10000, 'label' => 'Full Day'],
        ],
    ],

    // Azwar Hall per-item add-on rates.
    'azwar_hall_chair_rate' => 10,
];
