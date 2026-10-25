<?php

return [

    'encryption' => [

        'default' => 'aes',

        'aes' => [
            'cipher' => 'aes-256-gcm',
            'key' => env('STUDENTVAULT_AES_KEY'),
        ],

        'des' => [
            'cipher' => 'des-cbc',
            'key' => env('STUDENTVAULT_DES_KEY'),
        ],

        'rc4' => [
            'key' => env('STUDENTVAULT_RC4_KEY'),
        ],
    ],

];
