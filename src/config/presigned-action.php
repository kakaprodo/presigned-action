<?php

return [
    /**
     * Whether the package should run its database migrations.
     */
    'should_run_migration' => true,

    /**
     * Number of minutes after which a generated access key expires.
     */
    'key_expires_after' => 3600,

    /**
     * Map TemporaryAccessKey attributes to request headers whose values
     * are used to validate the incoming temporary access key.
     * 
     * The mapping value should exist in the request header where presigned-action middleware 
     * was defined
     */
    'access_key_validation' => [
        'whoami' => 'X-WHOMAI',

        // the encrypted key string
        'temp_access_key' => 'X-TEMP-ACCESS-KEY'
    ],

    /**
     * Error messages returned when access key validation fails.
     */
    'validation_error_message' => 'Unauthorized - invalid temporary access key'
];
