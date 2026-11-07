<?php
/*
 * PayHero configuration (v2 STK Push).
 * Keep this file outside the public web root. Rotate these values if they are exposed.
 */

$username = 't0H5J9nTaBB6dBMCrQXQ';
$password = 'Fu8h8vn80LhQ6YEQsLahwM5xhwWZKYb1KcQTvdf0';

return [
    // Full v2 STK endpoint
    'api_url'        => 'https://backend.payhero.co.ke/api/v2/payments',
    // Base URL for other API calls (transaction status)
    'base_url'       => 'https://backend.payhero.co.ke',
    // VERIFY the status path in PayHero's docs
    'status_path'    => '/api/v2/transactions/{ref}',

    // Basic auth built from the username and password
    'auth_header'    => 'Basic ' . base64_encode($username . ':' . $password),

    'channel_id'     => 13568,

    // Public HTTPS callback, no query string. PayHeroClient appends ?token=... itself.
    'callback_url'   => 'https://unlevelled-choric-serafina.ngrok-free.dev/pos/public/payhero-callback.php',
    'callback_token' => '5c833efe24ebc968d28d1daec8c43d31b53055a8b7de580a673dd5f717ab0d99',

    'timeout'        => 20,

    // Used by the cashier to report configuration problems.
    'missing'        => [],
    'configured'     => true,
];