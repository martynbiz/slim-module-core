<?php
return [
    'settings' => [
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        // Renderer settings
        'renderer' => [
            'folders' => [
                APPLICATION_PATH . '/templates',
            ],
            'ext' => 'phtml',
        ],

        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => __DIR__ . '/../logs/app.log',
        ],

        'i18n' => [

            // when the target locale is missing a translation/ template this the
            // fallback locale to use (probably "en")
            'default_locale' => 'en',

            // this is the type of the translation files using by zend-i18n
            'type' => 'phparray',

            // where the translation files are stored
            'file_path' => APPLICATION_PATH . '/assets/language/',
        ],

        'mail' => [

            // directory where suppressed email files are written to in non-prod env
            'file_path' => APPLICATION_PATH . '/data/mail/',
        ],

        'session' => [
            'namespace' => 'slim3__',
        ],
    ],
];
