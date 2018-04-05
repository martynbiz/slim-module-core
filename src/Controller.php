<?php
namespace MartynBiz\Slim\Module\Core;

use Slim\Container;

abstract class Controller
{
    /**
     * @var Slim\Container
     */
    protected $container;

    //
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Shorthand method to get dependency from container
     * @param $name
     * @return mixed
     */
    protected function getContainer()
    {
        return $this->container;
    }

    /**
     * Render the html and attach to the response
     * @param string $file Name of the template/ view to render
     * @param array $args Additional variables to pass to the view
     * @param Response?
     */
    protected function render($file, $data=array())
    {
        $container = $this->getContainer();
        $request = $container->get('request');
        $response = $container->get('response');

        if ($container->has('flash')) {
            $data['messages'] = $container->get('flash')->flushMessages();
        }

        if ($container->has('router')) {
            $data['router'] = $container->get('router');
        }

        if ($container->has('debugbar')) {
            $data['debugbar'] = $container->get('debugbar');
        }

        $data['csrf_name'] = $container->get('session')->get('csrf_name');
        $data['csrf_value'] = $container->get('session')->get('csrf_value');

        // depending on the requested format, we'll return data that way
        $format = $request->getParam('format');

        // fire any event handlers for "rendering"
        $container['events']->trigger("core:rendering", $file, $data);

        if ($format == 'json') {

            // put the json in the response object
            // this may not be sufficient for the Eloquent models as we may require
            // to add data from belongsTo or hasMany relationships. In that case,
            // define another
            return $this->renderJSON($data);

        } else {

            // generate the html
            return $this->renderHTML($file, $data);

        }
    }

    /**
     * Render the html and attach to the response
     * @param string $file Name of the template/ view to render
     * @param array $args Additional variables to pass to the view
     * @param Response?
     */
    protected function renderHTML($file, $data=array())
    {
        $container = $this->getContainer();

        // put the json in the response object
        $response = $container->get('response');
        $html = $container->get('renderer')->render($file, $data);
        $response->getBody()->write($html);

        return $response;
    }

    /**
     * Render the json and attach to the response
     * @param string $file Name of the template/ view to render
     * @param array $args Additional variables to pass to the view
     * @param Response?
     */
    protected function renderJSON($data=array())
    {
        $container = $this->getContainer();

        // put the json in the response object
        $response = $container->get('response');
        $response->getBody()->write(json_encode($data));

        return $response->withHeader('Content-type', 'application/json');
    }
}
