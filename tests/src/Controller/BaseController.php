<?php

use MartynBiz\Slim\Modules\Core\Controller\BaseController;

class TestController extends BaseController
{

}

class BaseControllerTest extends PHPUnit_Framework_TestCase
{
    protected $validator;

    public function setUp()
    {
        $this->validator = new Validator();
    }

    public function testInitialization()
    {
        $controller = new TestController();

        $this->assertTrue($controller instanceof TestController);
    }
}
