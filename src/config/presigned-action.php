<?php

return [
    /**
     * Whether the package should run its database migrations.
     */
    'should_run_migration' => true,

    /**
     * Number of minutes after which a generated access key expires.
     */
    'key_expires_after' => 30,

    /**
     * Map TemporaryAccessKey attributes to request headers whose values
     * are used to validate the incoming temporary access key.
     * 
     * The mapping value should exist in the request header where presigned-action middleware 
     * was defined
     */
    'access_key_validation' => [
        'whoami' => 'X-WHOAMI',

        // the encrypted key string
        'temp_access_key' => 'X-TEMP-ACCESS-KEY'
    ],

    /**
     * Error messages returned when access key validation fails.
     */
    'validation_error_message' => 'Unauthorized - invalid temporary access key',

    'temp_auth_code' => [
        /** Database table used for temporary authentication codes. */
        'table' => 'temp_auth_codes',
        'expires_after' => 20,
        'route' => [
            'enabled' => true,
            'prefix' => 'api/presigned-action',
            'generate_uri' => 'temp-auth-codes',
            'fetch_uri' => 'temp-auth-codes/{state_key}/{uuid}',
            'middleware' => [],
        ],
        'rate_limit' => [
            'enabled' => true,
            'max_attempts' => 20,
            'window_seconds' => 60,
            'key_resolver' => null,
        ],
    ],
];
