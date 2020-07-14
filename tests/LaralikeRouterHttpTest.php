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
    $status = $res->getStatusCode();
    $body = $res->getBody();
    // $this->assertEquals('200', $status);
    return $body;
  }

  /**
   * @dataProvider provider
   */
  public function testRoot($uri)
  {
    $this->assertEquals('ROOT', self::request($uri . '/'));
  }

  /**
   * @dataProvider provider
   */
  public function testSimplePage($uri)
  {
    $this->assertEquals(json_encode(['AAA']), self::request($uri . '/aaa'));
    $this->assertEquals('BBB', self::request($uri . '/bbb', 'POST'));
    $this->assertEquals(self::MSG_404, self::request($uri . '/bbb'));
    $this->assertEquals('CCCDDD', self::request($uri . '/ccc/ddd'));
  }

  /**
   * @dataProvider provider
   */
  public function testController($uri)
  {
    $this->assertEquals('CONTROLLER TEST 1', self::request($uri . '/ctrl1'));
    $this->assertEquals('CONTROLLER TEST 2', self::request($uri . '/ctrl2'));

  }

  /**
   * @dataProvider provider
   */
  public function testParameter($uri)
  {
    $this->assertEquals('UserId: 123', self::request($uri . '/user/123'));
    $this->assertEquals(self::MSG_404, self::request($uri . '/user/'));
    $this->assertEquals('456-789', self::request($uri . '/posts/456/comments/789'));
    $this->assertEquals('NAME: lala', self::request($uri . '/username/'));
    $this->assertEquals('NAME: milky', self::request($uri . '/username/milky'));
  }

  /**
   * @dataProvider provider
   */
  public function testWhere($uri)
  {
    $this->assertEquals('AGE: 13', self::request($uri . '/userage/13'));
    $this->assertEquals(self::MSG_404, self::request($uri . '/userage/foo'));
  }

  /**
   * @dataProvider provider
   */
  public function testGroup($uri)
  {
    $this->assertEquals('GROUP 1 FOO', self::request($uri . '/group1/foo'));
    $this->assertEquals('GROUP 1 BAR', self::request($uri . '/group1/bar'));
    $this->assertEquals('GROUP 2 BAZ', self::request($uri . '/group2/baz'));
    $this->assertEquals('GROUP 2 NEST HOGE', self::request($uri . '/group2/nest/hoge'));
    $this->assertEquals('GROUP 2 NEST FUGA', self::request($uri . '/group2/nest/fuga'));
  }

  /**
   * @dataProvider provider
   */
  public function testRedirect($uri)
  {
    $this->assertEquals('ROOT', self::request($uri . '/index.php'));
    $this->assertEquals('ROOT', self::request($uri . '/index.html'));
    $this->assertEquals('REDIRECT', self::request($uri . '/redirect/index.php'));
    $this->assertEquals('REDIRECT', self::request($uri . '/redirect/index.html'));
  }

  /**
   * @dataProvider provider
   */
  public function testCustomFallback($uri)
  {
    $this->assertEquals('404 !', self::request($uri . '/fallback?fallback'));
  }

  /**
   * @dataProvider provider
   */
  public function testView($uri)
  {
    $this->assertEquals('Hello lala', self::request($uri . '/view/twig1'));
    $this->assertEquals('Hello milky', self::request($uri . '/view/twig2'));
    $this->assertEquals('{"name":"star"}', self::request($uri . '/view/json'));
  }

}
