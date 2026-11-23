<?php

return [
    'app' => [
        'name' => 'FinalEntry',
        'debug' => false,
        'timezone' => 'Europe/Berlin',
    ],

    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'finalentry',
        'user' => 'finalentry',
        'pass' => 'change-me',
    ],

    'mail' => [
        'host' => 'smtp.example.com',
        'port' => 587,
        'secure' => 'tls',
        'user' => 'tickets@example.com',
        'pass' => 'change-me',
        'from' => 'tickets@example.com',
        'from_name' => 'FinalEntry',
    ],

    'event' => [
        'title' => 'Graduation Party',
        'venue' => 'Example Hall',
        'address' => 'Example Street 1, 12345 Example City',
        'date' => '2025-06-27',
        'doors_open' => '18:30',
        'security_dashboard_opens_at' => '2025-06-26 00:00:00',
        'leave_list_opens_at' => '2025-06-27 23:50:00',
        'minor_curfew' => '00:00',
    ],
];
