<?php

namespace kamiyakei;

class LaralikeRouter
{
  private static $methods = ['GET', 'POST', 'PUT', 'DELETE'];
  private static $is_found = false;
  private static $fallback;

  private $namespace;
  private $prefix;
  private $uri;
  private $is_group;
  private $middleware;

  public function setNamespace(string $namespace)
  {
    $this->namespace = $namespace;
  }

  public function setPrefix(string $prefix, string $uri)
  {
    $this->prefix = $prefix;
    if (strpos($uri, $prefix) !== 0) {
      $this->uri = false;
    }
    $uri = substr($uri, strlen($prefix));
    $this->uri = $uri;
  }

  public function __construct(
    string $namespace = 'App\\Controller\\',
    string $prefix = '',
    $uri = false, // string|false
    bool $is_group = false,
    array $middleware = []
  ) {
    $this->setNamespace($namespace);
    if ($uri === false) {
      $uri = $_SERVER['REQUEST_URI'];
      $uri = explode('?', $uri)[0];
      $uri = rawurldecode($uri);
    }
    $this->setPrefix($prefix, $uri);
    // r($this->uri);
    $this->is_group = $is_group;
    $this->middleware = $middleware;
  }

  public function __destruct()
  {
    if ($this->is_group) {
      return;
    }
    if (self::$is_found) {
      return;
    }
    if (isset(self::$fallback)) {
      call_user_func_array(self::$fallback, []);
      return;
    }
    // header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
    echo '<h1>404 Not Found</h1>';
  }

  // public function __call($method, $parameters)
  // {
  //   r(['__call', $method, $parameters, $_SERVER['REQUEST_METHOD']]);
  //   return false;
  // }

  private function checkCallable($callback)
  {
    if (is_callable($callback)) {
      return $callback;
    }
    $callback2 = $this->namespace . $callback;
    if (is_callable($callback2)) {
      return $callback2;
    }
    return false;
  }

  private function checkUri($uri, $where)
  {
    if (strpos($uri, '{') === false) {
      return [$uri === $this->uri, []];
    }

    $uri_ptn = $uri;
    foreach ($where as $key => $val) {
      $uri_ptn = str_replace('{' . $key . '?}', '(?P<' . $key . '>' . $val . ')?', $uri_ptn);
      $uri_ptn = str_replace('{' . $key . '}', '(?P<' . $key . '>' . $val . ')', $uri_ptn);
    }
    $uri_ptn = str_replace('{', '(?P<', $uri_ptn);
    $uri_ptn = str_replace('?}', '>.+?)?', $uri_ptn);
    $uri_ptn = str_replace('}', '>.+?)', $uri_ptn);
    $uri_ptn = '/^' . str_replace('/', '\\/', $uri_ptn) . '$/';

    $parameters = [];
    preg_match($uri_ptn, $this->uri, $matches);
    // r([$uri_ptn, $this->uri, $matches]);
    foreach ($matches as $key => $val) {
      if (is_numeric($key)) {
        continue;
      }
      if (strpos($val, '/') !== false) {
        return [false, []];
      }
      $parameters[$key] = $val;
    }
    return [count($matches) !== 0, $parameters];
  }

  /* $callback: string|callable */
  public function get(string $uri, $callback, $options = [])
  {
    $callback_ = $this->checkCallable($callback);
    assert($callback_ !== false, 'not callable!');

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
      return;
    }

    $res = $this->checkUri($uri, $options['where'] ?? []);
    // r($res);
    if (!$res[0]) {
      return;
    }

    self::$is_found = true;
    foreach ($this->middleware as $middleware) {
      $middleware_ = $this->checkCallable($middleware);
      assert($middleware_ !== false, 'not callable!');
      call_user_func_array($middleware_, []);
    }
    call_user_func_array($callback_, $res[1]);
    exit(0);
  }

  public function fallback($callback)
  {
    $callback_ = $this->checkCallable($callback);
    assert($callback_ !== false, 'not callable!');
    self::$fallback = $callback_;
  }

  // public function redirect($uri, $jumpto, $status_code)
  // {
  //   // header('Location:' );
  // }

  public function middleware(array $middleware)
  {
    array_merge($this->middleware, $middleware);
  }

  public function group(string $prefix, array $middleware, callable $callback)
  {
    $Route = new MyRouter(
      $this->namespace,
      $prefix,
      $uri = $this->uri,
      true,
      $this->middleware
    );
    if ($Route->uri === false) {
      return;
    }
    $Route->middleware($middleware);
    $callback_ = $this->checkCallable($callback);
    assert($callback_ !== false, 'not callable!');
    call_user_func_array($callback_, [$Route]);
  }
}
