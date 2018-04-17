<?php
namespace MartynBiz\Slim\Module\Core\PHPFoil\Helper;

class GenerateQueryString extends BaseHelper
{
    function __invoke($query)
    {
        $query = array_merge($_GET, $query);

        return http_build_query($query);
    }
}
