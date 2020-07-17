<?php

namespace App\Controller;

use \laralike\LaralikeBaseController as Controller;

class TestController extends Controller
{
  public function __construct()
  {
    $this->msg = 'CONTROLLER TEST ';
  }

  public function test1()
  {
    return $this->msg.'1';
  }

  public function test2()
  {
    return $this->msg.'2';
  }

}
