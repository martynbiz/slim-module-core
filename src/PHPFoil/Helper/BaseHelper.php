<?php
namespace MartynBiz\Slim\Module\Core\PHPFoil\Helper;

class BaseHelper
{
    /**
     * Slim\Container
     */
    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
    }
}
