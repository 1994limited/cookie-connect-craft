<?php

/**
 * Cookie Connect: copy this file to config/cookie-connect.php to set things
 * from code. Anything set here can't be changed in the control panel.
 *
 * Multi-environment keys ('*', 'dev', 'production'…) work as in any Craft
 * config file.
 */

return [
    // Where the consent platform loads (CRAFT_ENVIRONMENT values), or '*'.
    // 'environments' => ['production'],

    // Elsewhere: 'block' content that needs consent, 'run' it, or 'omit' it.
    // 'whenOff' => 'block',

    // Add Cookie Connect to every page by itself.
    // 'autoInsert' => true,

    // A function returning this request's CSP nonce, if your site uses one.
    // 'nonce' => fn () => \modules\Csp::nonce(),

    // Per-site settings, by site handle or '*' for all sites. Values like
    // '$COOKIEBOT_ID' are read from the environment.
    // 'sites' => [
    //     '*' => [
    //         'provider' => 'cookiebot',
    //         'values' => ['cbid' => '$COOKIEBOT_ID'],
    //         'google' => ['gtmId' => '$GTM_ID'],
    //     ],
    // ],
];
