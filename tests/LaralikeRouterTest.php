<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use \PHPUnit\Framework\TestCase;

class LaralikeRouterTest extends TestCase
{
  // URI　両方テストする
  protected const URI = 'http://localhost:8080';
  // protected const URI = 'http://localhost:8080/tests';
  protected const MSG_404 = '404 Not Found';

  protected static function request(string $uri, string $method='GET', array $parameters = [])
  {
    $client = new \GuzzleHttp\Client();
    $res = $client->request(
      $method,
      self::URI . $uri,
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

  public function testRoot()
  {
    $this->assertEquals('ROOT', self::request('/'));
  }

  public function testSimplePage()
  {
    $this->assertEquals('AAA', self::request('/aaa'));
    $this->assertEquals('BBB', self::request('/bbb', 'POST'));
    $this->assertEquals(self::MSG_404, self::request('/bbb'));
    $this->assertEquals('CCCDDD', self::request('/ccc/ddd'));
  }

  public function testController()
  {
    $this->assertEquals('CONTROLLER TEST 1', self::request('/ctrl1'));
    $this->assertEquals('CONTROLLER TEST 2', self::request('/ctrl2'));

  }

  public function testParameter()
  {
    $this->assertEquals('UserId: 123', self::request('/user/123'));
    $this->assertEquals(self::MSG_404, self::request('/user/'));
    $this->assertEquals('456-789', self::request('/posts/456/comments/789'));
    $this->assertEquals('NAME: lala', self::request('/username/'));
    $this->assertEquals('NAME: milky', self::request('/username/milky'));
  }

  public function testWhere()
  {
    $this->assertEquals('AGE: 13', self::request('/userage/13'));
    $this->assertEquals(self::MSG_404, self::request('/userage/foo'));
  }

  public function testGroup()
  {
    $this->assertEquals('GROUP 1 FOO', self::request('/group1/foo'));
    $this->assertEquals('GROUP 1 BAR', self::request('/group1/bar'));
    $this->assertEquals('GROUP 2 BAZ', self::request('/group2/baz'));
    $this->assertEquals('GROUP 2 NEST HOGE', self::request('/group2/nest/hoge'));
    $this->assertEquals('GROUP 2 NEST FUGA', self::request('/group2/nest/fuga'));
  }

  public function testRedirect()
  {
    $this->assertEquals('ROOT', self::request('/index.php'));
    $this->assertEquals('ROOT', self::request('/index.html'));
    $this->assertEquals('REDIRECT', self::request('/redirect/index.php'));
    $this->assertEquals('REDIRECT', self::request('/redirect/index.html'));
  }

  public function testCustomFallback()
  {
    $this->assertEquals('404 !', self::request('/fallback?fallback'));
  }

  public function testView()
  {
    $this->assertEquals('Hello lala', self::request('/view/twig1'));
    $this->assertEquals('Hello milky', self::request('/view/twig2'));
    $this->assertEquals('{"name":"star"}', self::request('/view/json'));
  }

  public function testConfig()
  {
    $this->assertEquals('foo.db', self::request('/config-foo'));
    $this->assertEquals('bar.db', self::request('/config-bar'));
    $this->assertEquals('baz.db', self::request('/config/'));
    $this->assertEquals('bar.db', self::request('/config-bar2'));
  }

}
