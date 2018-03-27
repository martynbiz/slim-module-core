<?php
namespace MartynBiz\Slim\Module\Core\PHPFoil\Helper;

class Translate
{
    /**
     * Slim\Container
     */
    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    function __invoke($str)
    {
        return $this->container['i18n']->translate($str);
    }
}
