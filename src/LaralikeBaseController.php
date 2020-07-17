<?php

namespace kamiyakei;

class LaralikeBaseController
{
  private static $singleton;

  public static function getInstance()
  {
    if (!isset(self::$singleton)) {
      self::$singleton = new self();
    }
    return self::$singleton;
  }
}