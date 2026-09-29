<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Upload Disk
    |--------------------------------------------------------------------------
    |
    | Filesystem disk used for user uploaded documents, attachments and
    | task transfer paperwork.
    |
    */

    'disk' => env('UPLOADS_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Upload Directory
    |--------------------------------------------------------------------------
    |
    | Directory inside the disk where uploaded files are written. When the
    | disk is "public" the files are reachable through storage:link.
    |
    */

    'directory' => env('UPLOADS_DIRECTORY', 'uploads'),

];
