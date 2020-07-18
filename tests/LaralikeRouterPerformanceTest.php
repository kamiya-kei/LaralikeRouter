<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use \laralike\LaralikeRouter as Route;
use \PHPUnit\Framework\TestCase;
use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;

class LaralikeRouterPerformanceTest extends TestCase
{
  protected const MSG_404 = '404 Not Found';
  protected static $logger;

  /**
   * @before
   */
  public static function setTestMode()
  {
    ini_set('zend.assertions', '0');
    self::$logger = new Logger('mylogger');
    $stream = new StreamHandler(__DIR__.'/../logs/test.log', Logger::DEBUG);
    self::$logger->pushHandler($stream);
    Route::setTestMode();
  }

  public function testPerformance()
  {
    ob_start();
    $t_start = microtime(true);
    for ($i = 1;  $i <= 10000; $i++) {
      Route::get('/pf/'.(string)$i, function () use ($i) { return $i; });
    }
    $t_mid = microtime(true);
    Route::routing('/pf/10000');
    $t_end = microtime(true);
    $t_routing = $t_end - $t_mid;
    $t_define = $t_mid - $t_start;
    $body = ob_get_clean();
    $this->assertSame('10000', $body);
    $this->assertLessThan(0.02, $t_routing); // 0.009274
    $this->assertLessThan(0.02, $t_define);  // 0.014
    self::$logger->info('testPerformance1', [
      'routing' => $t_routing,
      'define' => $t_define,
    ]);
  }

  public function testPerformance2()
  {
    ob_start();
    $t_start = microtime(true);
    for ($i = 1;  $i <= 10000; $i++) {
      Route::get('/pf/{id}/'.(string)$i, function ($id) use ($i) { return $id.(string)$i; });
    }
    $t_mid = microtime(true);
    Route::routing('/pf/foo/10000');
    $t_end = microtime(true);
    $t_routing = $t_end - $t_mid;
    $t_define = $t_mid - $t_start;
    $body = ob_get_clean();
    $this->assertSame('foo10000', $body);
    $this->assertLessThan(0.05, $t_routing); // 0.031
    $this->assertLessThan(0.02, $t_define);  // 0.013
    self::$logger->info('testPerformance2', [
      'routing' => $t_routing,
      'define' => $t_define,
    ]);
  }

}
