<?php
namespace MartynBiz\Slim\Module\Core;

use Slim\App;
use Slim\Container;
use Slim\Http\Headers;
use MartynBiz\Mongo\Connection;
use MartynBiz\Slim\Module\Core\Http\Request;
use MartynBiz\Slim\Module\Core\Http\Response;
use MartynBiz\Slim\Module\ModuleInterface;
use MartynBiz\Slim\Module\Core;

class Module implements ModuleInterface
{
    /**
     * Get config array for this module
     * @return array
     */
    public function initDependencies(Container $container)
    {
        $settings = $container->get('settings');

        Connection::getInstance()->init($settings['mongo']);

        // replace request with our own
        $container['request'] = function($c) {
            return Request::createFromEnvironment($c->get('environment'));
        };

        // replace reponse with our own
        $container['response'] = function($c) {
            $settings = $c->get('settings');
            $headers = new Headers(['Content-Type' => 'text/html; charset=UTF-8']);
            $response = new Response(200, $headers);
            return $response->withProtocolVersion($c->get('settings')['httpVersion']);
        };

        // view renderer
        $container['renderer'] = function ($c) {
            $settings = $c->get('settings')['renderer'];
            $engine = \Foil\engine($settings);
            $engine->registerFunction('translate', new Core\View\Helper\Translate($c) );
            $engine->registerFunction('pathFor', new Core\View\Helper\PathFor($c) );
            $engine->registerFunction('generateSortQuery', new Core\View\Helper\GenerateSortQuery($c) );
            return $engine;
        };

        // monolog
        $container['logger'] = function ($c) {
            $settings = $c->get('settings')['Applogger'];
            $logger = new \Monolog\Logger($settings['name']);
            $logger->pushProcessor(new \Monolog\Processor\UidProcessor());
            $logger->pushHandler(new \Monolog\Handler\StreamHandler($settings['path'], Monolog\Logger::DEBUG));
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
                $transport = new \Zend\Mail\Transport\Sendmail();
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

            return new Core\Mail($transport, $c['renderer'], $c['i18n'], $locale, $defaultLocale, $c['i18n']);
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
    }

    /**
     * Initiate app middleware (route middleware should go in initRoutes)
     * @param App $app
     * @return void
     */
    public function initMiddleware(App $app)
    {

    }

    /**
     * Load is run last, when config, dependencies, etc have been initiated
     * Routes ought to go here
     * @param App $app
     * @return void
     */
    public function initRoutes(App $app)
    {
        $app->get('/helloworld', function ($request, $response, $args) {
            $response->getBody()->write('Hello world!');
            return $response;
        });
    }
}
