<?php
namespace MartynBiz\Slim\Module\Core;

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Environment;

use Symfony\Component\DomCrawler\Crawler;

/**
 * This is an example class that shows how you could set up a method that
 * runs the application. Note that it doesn't cover all use-cases and is
 * tuned to the specifics of this skeleton app, so if your needs are
 * different, you'll need to change it.
 */
class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * Useful to have $app here so we can access during tests
     *
     * @var Slim\App
     */
    protected $app = null;

    /**
     * We wanna also build $app so that we can gain access to container
     */
    public function setUp()
    {
        // App settings
        $appSettings = require APPLICATION_PATH . '/modules/settings.php';

        // Module settings (autoload)
        $moduleSettings = [];
        foreach (array_keys($appSettings['settings']['modules']) as $dir) {
            if ($path = realpath($appSettings['settings']['modules_dir'] . $dir . '/settings.php')) {
                $moduleSettings = array_merge_recursive($moduleSettings, require $path);
            }
        }

        // Environment settings
        $envSettings = [];
        if ($path = realpath(APPLICATION_PATH . '/modules/settings-' . APPLICATION_ENV . '.php')) {
            $envSettings = require $path;
        }

        // Instantiate the app
        $settings = array_merge_recursive($moduleSettings, $appSettings, $envSettings);
        $app = new \Slim\App($settings);

        // initialize all modules in settings > modules > autoload [...]
        $moduleInitializer = new \MartynBiz\Slim\Module\Initializer($settings['settings']['modules']);
        $moduleInitializer->initModules($app);

        $this->app = $app;

        $container = $app->getContainer();


        // Auth stuff, hook?

        // In some cases, where services have become "frozen", we need to define
        // mocks before they are loaded

        // session service
        $container['session'] = $this->getMockBuilder('Aura\\Session\\Segment')
            ->disableOriginalConstructor()
            ->getMock();

        // register events for php-events
        $container['events']->trigger("martynbiz-core:tests:setup", $app, $this);
    }

    public function tearDown()
    {
        $container = $this->app->getContainer();

        // register events for php-events
        $container['events']->trigger("martynbiz-core:tests:teardown", $this->app);
    }

    public function forceTruncateTables($connection, $callback)
    {
        $container = $this->app->getContainer();
        $settings = $container->get('settings');

        // as we have foreign key constraints on meta, we cannot use
        // truncate (even if the table is empty). so we need to temporarily
        // turn off FOREIGN_KEY_CHECKS

        // in vagrant, we have an sqlite db. we may still want to run tests there too
        // to ensure the installation is working ok. so we need to disable foreign keys
        // different from mysql
        switch($settings['eloquent']['driver']) {
            case 'sqlite':
                $connection->statement('PRAGMA foreign_keys = OFF;');
                break;
            case 'mysql':
            default:
                $connection->statement('SET FOREIGN_KEY_CHECKS=0;');
        }

        // clear tables
        $callback();

        // turn foreign key checks back on
        switch($settings['eloquent']['driver']) {
            case 'sqlite':
                $connection->statement('PRAGMA foreign_keys = ON;');
                break;
            case 'mysql':
            default:
                $connection->statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    /**
     * Process the application given a request method and URI
     *
     * @param string $requestMethod the request method (e.g. GET, POST, etc.)
     * @param string $requestUri the request URI
     * @param array|object|null $requestData the request data
     * @return \Slim\Http\Response
     */
    public function runApp($requestMethod, $requestUri, $requestData = null, $headerData = null)
    {
        // Create a mock environment for testing with
        $environment = Environment::mock(
            [
                'REQUEST_METHOD' => $requestMethod,
                'REQUEST_URI' => $requestUri
            ]
        );

        // Set up a request object based on the environment
        $request = Request::createFromEnvironment($environment);

        // Add request data, if it exists
        if (!empty($requestData)) {
            $request = $request->withParsedBody($requestData);
        }

        // Add header data, if it exists
        if (is_array($headerData)) {
            foreach ($headerData as $name => $value) {
                $request = $request->withHeader($name, $value);
            }
        }

        // Set up a response object
        $response = new Response();

        // Process the application
        $response = $this->app->process($request, $response);

        // Return the response
        return $response;
    }

    public function login($user)
    {
        $container = $this->app->getContainer();

        // return an identity (eg. email)
        $container->get('martynbiz-auth.auth')
            ->method('getAttributes')
            ->willReturn( $user->toArray() );

        // by defaut, we'll make isAuthenticated return a false
        $container->get('martynbiz-auth.auth')
            ->method('isAuthenticated')
            ->willReturn(true);
    }

    /**
     * Will crawl html string for a given query (e.g. form#register)
     *
     * @param $query string
     * @param $html string
     * @return boolean
     */
    public function assertQuery($query, $html)
    {
        $crawler = new Crawler($html);
        return $this->assertEquals(1, $crawler->filter($query)->count());
    }

    /**
     * Will crawl html string for a given query (e.g. form#register)
     *
     * @param $query string
     * @param $html string
     * @return boolean
     */
    public function assertQueryCount($query, $count, $html)
    {
        $crawler = new Crawler($html);
        $this->assertEquals($count, $crawler->filter($query)->count());
    }
}
