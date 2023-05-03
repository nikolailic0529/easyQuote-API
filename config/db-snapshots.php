<?php

return [
    /*
     * The name of the disk on which the snapshots are stored.
     */
    'disk' => 'snapshots',

    /*
     * The connection to be used to create snapshots. Set this to null
     * to use the default configured in `config/databases.php`
     */
    'default_connection' => null,

    /*
     * The directory where temporary files will be stored.
     */
    'temporary_directory_path' => env('STORAGE_PATH', storage_path('app')).'/temp',

    /*
     * Create dump files that are gzipped
     */
    'compress' => env('SNAPSHOTS_COMPRESS', false),
];
