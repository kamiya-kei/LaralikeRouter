<?php

namespace kamiyakei;

class LaralikeRouter
{
  const METHODS = ['get', 'post', 'put', 'delete', 'patch', 'connect', 'head', 'options', 'trace'];

  private static $is_init = false;
  private static $is_found = false;
  private static $fallback;
  private static $view;

  private static $namespace = 'App\\Controller\\';
  public static $prefix = '';
  public static $uri = '';
  private static $method = '';
  private static $middleware = [];
  private static $configs = [];

  public static function init()
  {
    if (self::$is_init) { return; }
    if ('' === self::$uri) {
      self::$uri = $_SERVER['REQUEST_URI'];
      self::$method = strtolower($_SERVER['REQUEST_METHOD']);
    }
    self::$uri = explode('?', self::$uri)[0];
    self::$uri = rawurldecode(self::$uri);
    register_shutdown_function(function () {
      if (self::$is_found) { return; }
      if (isset(self::$fallback)) {
        call_user_func_array(self::$fallback, []);
      } else {
        self::defaultFallback();
      }
    });
    self::$is_init = true;
  }

  public static function testRoute(string $uri, string $method='get')
  {
    self::$uri = $uri;
    self::$method = strtolower($method);
  }

  public static function defaultFallback()
  {
    if (!headers_sent()) {
      header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
    }
    echo '404 Not Found';
  }

  public static function setNamespace(string $namespace)
  {
    self::init();
    self::$namespace = $namespace;
  }

  public static function setPrefix(string $prefix)
  {
    self::init();
    self::$prefix .= $prefix;
    if ('' === $prefix) { return; }
    if (0 !== strpos(self::$uri, $prefix)) {
      self::$uri = '';
      return;
    }
    self::$uri = substr(self::$uri, strlen($prefix));
  }

  private static function checkCallable($callback): callable
  {
    if (is_callable($callback)) {
      return $callback;
    }
    $callback2 = self::$namespace . $callback;
    if (is_callable($callback2)) {
      return $callback2;
    }
    assert(false, $callback2 . ' is not callable!');
  }

  private static function checkUri($uri, $where)
  {
    if (false === strpos($uri, '{')) {
      // var_dump([$uri, self::$uri]);
      return [$uri === self::$uri, []];
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
    preg_match($uri_ptn, self::$uri, $matches);
    // r([$uri_ptn, self::$uri, $matches]);
    foreach ($matches as $key => $val) {
      if ($key === 0) { continue; }
      if (is_numeric($key)) {
        $parameters[] = $val;
        continue;
      }
      if (false !== strpos($val, '/')) {
        return [false, []];
      }
    }
    return [count($matches) !== 0, $parameters];
  }

  public static function match(array $methods, string $uri, $callback, array $options = [])
  {
    assert(count(array_filter($methods, function ($val) {
      return !in_array($val, self::METHODS);
    })) === 0, '$methods is incorrect value !');
    self::init();
    $callback_ = self::checkCallable($callback);

    if (self::$is_found) { return; }
    if (!in_array(self::$method, $methods)) { return; }

    if ('/' !== substr($uri, 0, 1)) {
      $uri = '/' . $uri;
    }
    $res = self::checkUri($uri, $options['where'] ?? []);
    // r($res);
    if (!$res[0]) { return; }

    self::$is_found = true;
    foreach (self::$middleware as $middleware) {
      $middleware_ = self::checkCallable($middleware);
      call_user_func_array($middleware_, []);
    }
    return call_user_func_array($callback_, $res[1]);
  }

  /* $callback: string|callable */
  public static function get(string $uri, $callback, array $options = [])
  {
    return self::match(['get'], $uri, $callback, $options);
  }

  public static function post(string $uri, $callback, array $options = [])
  {
    return self::match(['post'], $uri, $callback, $options);
  }

  public static function put(string $uri, $callback, array $options = [])
  {
    return self::match(['put'], $uri, $callback, $options);
  }

  public static function patch(string $uri, $callback, array $options = [])
  {
    return self::match(['patch'], $uri, $callback, $options);
  }

  public static function delete(string $uri, $callback, array $options = [])
  {
    return self::match(['delete'], $uri, $callback, $options);
  }

  public static function options(string $uri, $callback, array $options = [])
  {
    return self::match(['options'], $uri, $callback, $options);
  }

  public static function any(string $uri, $callback, array $options = [])
  {
    return self::match(self::METHODS, $uri, $callback, $options);
  }

  public static function fallback($callback)
  {
    self::init();
    self::$fallback = self::checkCallable($callback);
  }

  public static function middleware(array $middleware)
  {
    self::init();
    self::$middleware = array_merge(self::$middleware, $middleware);
  }

  public static function group(string $prefix, array $middleware, callable $callback)
  {
    self::init();

    if ('/' !== substr($prefix, 0, 1)) {
      $prefix = '/' . $prefix;
    }

    $_namespace = self::$namespace;
    $_prefix = self::$prefix;
    $_uri = self::$uri;
    $_middleware = self::$middleware;

    self::setPrefix($prefix);
    if (self::$uri !== '') {
      self::middleware($middleware);
      $callback_ = self::checkCallable($callback);
      call_user_func_array($callback_, []);
    }

    self::$namespace = $_namespace;
    self::$prefix = $_prefix;
    self::$uri = $_uri;
    self::$middleware = $_middleware;
  }

  public static function current()
  {
    return self::$prefix . self::$uri;
  }

  /* $jumpto: string|int */
  public static function redirect(string $uri, $jumpto=301, int $status_code=301)
  {
    // 引数が1個、または、2個で2個めがintegerの時：即座にリダイレクト
    if (gettype($jumpto) === 'integer') {
      $status_code = $jumpto;
      assert(in_array($status_code, [300, 301, 302, 303, 304, 307, 308]), '$status_code is incorrect');
      header('Location:' . self::$prefix . $uri, true, $status_code);
      exit(0);
    }
    // 引数が2個で2個目がstring、または、引数が3個の時：ルーティングをチェックしてマッチすればリダイレクト
    self::any($uri, function () use ($jumpto, $status_code) {
      assert(in_array($status_code, [300, 301, 302, 303, 304, 307, 308]), '$status_code is incorrect');
      // r(self::$prefix . $jumpto);
      header('Location:' . self::$prefix . $jumpto, true, $status_code);
      exit(0);
    });
  }

  public static function setView($callback)
  {
    self::init();
    $callback_ = self::checkCallable($callback);
    self::$view = $callback_;
  }

  public static function view(string $uri, string $viewfile, array $parameters = [])
  {
    assert(isset(self::$view), 'view is not setting yet. please setView()');
    echo self::any($uri, function () use ($viewfile, $parameters) {
      return call_user_func_array(self::$view, [$viewfile, $parameters]);
    });
  }

  public static function json(array $data)
  {
    self::init();
    header('Content-Type: application/json; charset=utf-8');
    return json_encode($data);
  }

  public static function config(string $key, ?string $value=null)
  {
    self::init();
    if(is_null($value)) {
      return self::$configs[$key];
    }
    self::$configs[$key] = $value;
    return $value;
  }
}
