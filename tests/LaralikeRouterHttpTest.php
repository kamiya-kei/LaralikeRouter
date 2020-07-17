<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use \PHPUnit\Framework\TestCase;

/**
 * @requires PHP 7.1
 */
class LaralikeRouterHttpTest extends TestCase
{
  protected const MSG_404 = '404 Not Found';

  public function provider()
  {
    return [
      'root_dir' => ['http://localhost:8080'],
      'sub_dir' => ['http://localhost:8080/tests'],
    ];
  }

  protected static function request(string $uri, string $method='GET', array $parameters = [])
  {
    $client = new \GuzzleHttp\Client();
    $res = $client->request(
      $method,
      $uri,
      [
        'http_errors' => false,
        'form_params' => $parameters,
      ]
    );
    // $status = $res->getStatusCode();
    $body = $res->getBody();
    // $this->assertSame('200', $status);
    return $body->__toString();
  }

  /**
   * @dataProvider provider
   */
  public function testRoot($uri)
  {
    $this->assertSame('ROOT', self::request($uri . '/'));
  }

  /**
   * @dataProvider provider
   */
  public function testSimplePage($uri)
  {
    $this->assertSame(json_encode(['AAA']), self::request($uri . '/aaa'));
    $this->assertSame('BBB', self::request($uri . '/bbb', 'POST'));
    $this->assertSame(self::MSG_404, self::request($uri . '/bbb'));
    $this->assertSame('CCCDDD', self::request($uri . '/ccc/ddd'));
  }

  /**
   * @dataProvider provider
   */
  public function testController($uri)
  {
    $this->assertSame('CONTROLLER TEST 1', self::request($uri . '/ctrl1'));
    $this->assertSame('CONTROLLER TEST 2', self::request($uri . '/ctrl2'));

  }

  /**
   * @dataProvider provider
   */
  public function testParameter($uri)
  {
    $this->assertSame('UserId: 123', self::request($uri . '/user/123'));
    $this->assertSame(self::MSG_404, self::request($uri . '/user/'));
    $this->assertSame('456-789', self::request($uri . '/posts/456/comments/789'));
    $this->assertSame('NAME: lala', self::request($uri . '/username/'));
    $this->assertSame('NAME: milky', self::request($uri . '/username/milky'));
  }

  /**
   * @dataProvider provider
   */
  public function testWhere($uri)
  {
    $this->assertSame('AGE: 13', self::request($uri . '/userage/13'));
    $this->assertSame(self::MSG_404, self::request($uri . '/userage/foo'));
  }

  /**
   * @dataProvider provider
   */
  public function testGroup($uri)
  {
    $this->assertSame('GROUP 1 FOO', self::request($uri . '/group1/foo'));
    $this->assertSame('GROUP 1 BAR', self::request($uri . '/group1/bar'));
    $this->assertSame('GROUP 2 BAZ', self::request($uri . '/group2/baz'));
    $this->assertSame('GROUP 2 NEST HOGE', self::request($uri . '/group2/nest/hoge'));
    $this->assertSame('GROUP 2 NEST FUGA', self::request($uri . '/group2/nest/fuga'));
  }

  /**
   * @dataProvider provider
   */
  public function testRedirect($uri)
  {
    $this->assertSame('ROOT', self::request($uri . '/index.php'));
    $this->assertSame('ROOT', self::request($uri . '/index.html'));
    $this->assertSame('REDIRECT', self::request($uri . '/redirect/index.php'));
    $this->assertSame('REDIRECT', self::request($uri . '/redirect/index.html'));
  }

  /**
   * @dataProvider provider
   */
  public function testCustomFallback($uri)
  {
    $this->assertSame('404 !', self::request($uri . '/fallback?fallback'));
  }

  /**
   * @dataProvider provider
   */
  public function testView($uri)
  {
    $this->assertSame('Hello lala', self::request($uri . '/view/twig1'));
    $this->assertSame('Hello milky', self::request($uri . '/view/twig2'));
    $this->assertSame('{"name":"star"}', self::request($uri . '/view/json'));
  }

  /**
   * @dataProvider provider
   */
  public function testMiddleware($uri)
  {
    $this->assertSame('GLOVAL_MW FOO', self::request($uri . '/mw?mw'));
    $this->assertSame('GLOVAL_MW GROUP_MW BAR', self::request($uri . '/mwg/?mw'));
    $this->assertSame('GLOVAL_MW GROUP_MW ROUTE_MW BAZ', self::request($uri . '/mwg/baz?mw'));
  }

  /**
   * @dataProvider provider
   */
  public function testPerformance($uri)
  {
    // パフォーマンスが著しく落ちてないかテストする用(環境依存)
    // 任意パラメータなし * 10000
    $NUM = 10;
    $sum1 = 0;
    for ($i = 0; $i < $NUM; $i++) {
      $res1 = self::request($uri . '/pf/10000?performance=1');
      $arr1 = explode('...', $res1);
      $sum1 += (float)end($arr1);
    }
    $avg1 = $sum1 / $NUM;
    $this->assertLessThan(0.05, $avg1);

    // 任意パラメータあり * 10000
    $sum2 = 0;
    for ($i = 0; $i < $NUM; $i++) {
      $res2 = self::request($uri . '/pf/foo/10000?performance=2');
      $arr2 = explode('...', $res2);
      $sum2 += (float)end($arr2);
    }
    $avg2 = $sum2 / $NUM;
    $this->assertLessThan(0.05, $avg2);
  }

}
