<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use \laralike\LaralikeRouter as Route;
use \PHPUnit\Framework\TestCase;

Route::setTestMode();
include_once __DIR__ . '/index.php';

class LaralikeRouterTest extends TestCase
{
  protected const MSG_404 = '404 Not Found';

  protected static function request(string $uri, string $method='GET', array $parameters = [])
  {
    ob_start();
    Route::routing($uri, $method);
    $body = ob_get_clean();
    return $body;
  }

  public function testRoot()
  {
    $this->assertEquals('ROOT', self::request('/'));

  }

  public function testSimplePage()
  {
    $this->assertEquals(json_encode(['AAA']), self::request('/aaa'));
    $this->assertEquals('BBB', self::request('/bbb', 'POST'));
    $this->assertEquals(self::MSG_404, self::request('/bbb'));
    $this->assertEquals('CCCDDD', self::request('/ccc/ddd'));
  }

}
