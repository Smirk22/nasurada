<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Backups Kept
    |--------------------------------------------------------------------------
    |
    | How many timestamped backup archives to keep. Older archives are pruned
    | automatically after a new backup is created. Raise this if you want a
    | longer history; each backup is a full snapshot.
    |
    */

    'keep' => (int) env('BACKUP_KEEP', 14),

    /*
    |--------------------------------------------------------------------------
    | Include Student Photos
    |--------------------------------------------------------------------------
    |
    | When enabled, the contents of storage/app/public (student photos and any
    | other uploads) are added to each backup archive. The database only stores
    | the photo paths, so restoring without them leaves records pointing at
    | missing files.
    |
    */

    'include_photos' => (bool) env('BACKUP_INCLUDE_PHOTOS', true),

    /*
    |--------------------------------------------------------------------------
    | Include Environment File
    |--------------------------------------------------------------------------
    |
    | The .env file holds APP_KEY, which is required to decrypt two-factor
    | secrets stored in the database. It is included inside the archive under
    | env/.env so a full recovery is possible. Keep backup archives private.
    |
    */

    'include_env' => (bool) env('BACKUP_INCLUDE_ENV', true),

];
