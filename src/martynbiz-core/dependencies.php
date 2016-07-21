<?php
// DIC configuration

$container = $app->getContainer();

MartynBiz\Mongo\Connection::getInstance()->init($settings['settings']['mongo']);

// replace request with our own
$container['request'] = function($c) {
    return App\Http\Request::createFromEnvironment($c->get('environment'));
};

// replace reponse with our own
$container['response'] = function($c) {
    $settings = $c->get('settings');
    $headers = new \Slim\Http\Headers(['Content-Type' => 'text/html; charset=UTF-8']);
    $response = new App\Http\Response(200, $headers);
    return $response->withProtocolVersion($c->get('settings')['httpVersion']);
};

// view renderer
$container['renderer'] = function ($c) {
    $settings = $c->get('settings')['renderer'];
    $engine = Foil\engine($settings);
    $engine->registerFunction('translate', new App\View\Helper\Translate($c) );
    $engine->registerFunction('pathFor', new App\View\Helper\PathFor($c) );
    $engine->registerFunction('generateSortQuery', new App\View\Helper\GenerateSortQuery($c) );
    return $engine;
};

// monolog
$container['logger'] = function ($c) {
    $settings = $c->get('settings')['Applogger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], Monolog\Logger::DEBUG));
    return $logger;
};

// locale - required by a few services, so easier to put in container
$container['locale'] = function($c) {
    $settings = $c->get('settings')['i18n'];
    $locale = $c['request']->getCookie('language', $settings['default_locale']);
    return $locale;
};

// i18n
$container['i18n'] = function($c) {
    $settings = $c->get('settings')['i18n'];
    $translator = new \Zend\I18n\Translator\Translator();
    $translator->addTranslationFilePattern($settings['type'], $settings['file_path'], '/%s.php', 'default');
    $translator->setLocale($c['locale']);
    $translator->setFallbackLocale($settings['default_locale']);
    return $translator;
};

// mail
$container['mail_manager'] = function($c) {
    $settings = $c->get('settings');

    // if not in production, we will write to file
    if (APPLICATION_ENV == 'production') {
        $transport = new Zend\Mail\Transport\Sendmail();
    } else {
        $transport = new \Zend\Mail\Transport\File();
        $options   = new \Zend\Mail\Transport\FileOptions(array(
            'path' => realpath($settings['mail']['file_path']),
            'callback' => function (\Zend\Mail\Transport\File $transport) {
                return 'Message_' . microtime(true) . '_' . mt_rand() . '.txt';
            },
        ));
        $transport->setOptions($options);
    }

    $locale = $c['locale'];
    $defaultLocale = @$settings['i18n']['default_locale'];

    return new App\Mail($transport, $c['renderer'], $c['i18n'], $locale, $defaultLocale, $c['i18n']);
};

// flash
$container['flash'] = function($c) {
    return new \MartynBiz\FlashMessage\Flash();
};

$container['csrf'] = function ($c) {
    return new \Slim\Csrf\Guard;
};

$container['session'] = function ($c) {
    $settings = $c->get('settings')['session'];

    $session_factory = new \Aura\Session\SessionFactory;
    $session = $session_factory->newInstance($_COOKIE);

    return $session->getSegment($settings['namespace']);
};

$container['cache'] = function ($c) {
    $backend = new \Predis\Client(null, array(
        'prefix' => 'martynbiz__', // TODO move this into settings
    ));
    $adapter = new \Desarrolla2\Cache\Adapter\Predis($backend);
    return new \Desarrolla2\Cache\Cache($adapter);
};
