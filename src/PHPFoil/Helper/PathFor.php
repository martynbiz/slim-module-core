<?php
namespace MartynBiz\Slim\Module\Core\PHPFoil\Helper;

class PathFor extends BaseHelper
{
    function __invoke($routeName, $args=[])
    {
        return $this->container['router']->pathFor($routeName, $args);
    }
}
