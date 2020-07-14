<?php

namespace kamiyakei;

use \kamiyakei\LaralikeRoute;

register_shutdown_function(function () {
  if (LaralikeRouter::$is_test_mode) { return; }
  $uri = $_SERVER['REQUEST_URI'];
  $method = $_SERVER['REQUEST_METHOD'];
  LaralikeRouter::routing($uri, $method);
});

class LaralikeRouter
{
  const METHODS = ['get', 'post', 'put', 'delete', 'patch', 'connect', 'head', 'options', 'trace'];

  private static $fallback;
  private static $view;

  private static $namespace = 'App\\Controller\\';

  private static $routes = [];
  public static $is_test_mode = false;

  public static $prefix = '';
  public static $where = [];
  public static $middleware = [];

  /* phpunitによるtest用 */
  public static function setTestMode($is_test_mode=true)
  {
    self::$is_test_mode = $is_test_mode;
  }

  public static function routing(string $uri, string $method = 'get', bool $is_nest=false): bool
  {
    if (!$is_nest) {
      self::$prefix = '';
      self::$where = [];
      self::$middleware = [];
    }

    $method = strtolower($method);
    assert(in_array($method, self::METHODS), 'method is incorrect');
    $uri = explode('?', $uri)[0];
    $uri = rawurldecode($uri);

    // r(self::$routes);
    // $is_found = false;
    foreach (self::$routes as $route) {
      $type = $route->getType();
      // r($route);
      // r(self::$prefix);
      switch ($type) {
      case 'routing':
        $is_hit = $route->isHit($uri, $method);
        if ($is_hit) {
          // $is_found = true;
          foreach (self::$middleware as $middleware) {
            $middleware_ = self::getCallable($middleware);
            $res_middleware = call_user_func_array($middleware_, []);
            self::returnAction($res_middleware);
          }
          $res = $route->runCallback();
          self::returnAction($res);
          return true;
        }
        break;
      case 'prefix':
        self::$prefix = $route->prefix;
        self::$where = array_merge(self::$where, $route->where ?? []);
        break;
      case 'middleware':
        self::$middleware = array_merge(self::$middleware, $route->middleware);
        break;
      case 'group':
        $is_hit_group = $route->isHitGroup($uri, $method);
        if (!$is_hit_group) { break; }
        // backup
        $prefix_ = self::$prefix;
        $where_ = self::$where;
        $middleware_ = self::$middleware;
        // set
        self::$prefix .= $route->prefix;
        self::$where = array_merge(self::$where, $route->where ?? []);
        self::$middleware = array_merge(self::$middleware, $route->middleware);
        // routing
        $routes_ = self::$routes;
        self::$routes = [];
        $res = $route->runCallback();
        $is_hit = self::routing($uri, $method, true); // 再帰
        if ($is_hit) { return true; }
        self::$routes = $routes_;
        // reset
        self::$prefix = $prefix_;
        self::$where = $where_;
        self::$middleware = $middleware_;
        break;
      default:
        assert(false, 'invalid type: ' . $type);
      }
    }
    if ($is_nest) { return false; }
    // if ($is_found) { return; }
    if (isset(self::$fallback)) {
      $res_fallback = call_user_func_array(self::$fallback, []);
    } else {
      $res_fallback = self::defaultFallback();
    }
    self::returnAction($res_fallback);
    return false;
  }

  public static function returnAction($res): void
  {
    $type = gettype($res);
    switch ($type) {
      case 'boolean':
      case 'integer':
      case 'double':
        echo (string)$res;
        break;
      case 'string':
        echo $res;
        break;
      case 'array':
        self::json($res);
        break;
      // case 'object':
      // case 'resource':
      // case 'resource (closed)':
      // case 'NULL':
      // case 'unknown type':
      default:
        break;
    }
  }

  public static function json(array $data): void
  {
    if (!self::$is_test_mode) {
      header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode($data);
  }

  public static function defaultFallback()
  {
    if (!self::$is_test_mode) { // && !headers_sent()
      header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
    }
    echo '404 Not Found';
  }

  public static function setNamespace(string $namespace)
  {
    self::$namespace = $namespace;
  }

  public static function getCallable($callback)
  {
    if ('string' === gettype($callback)) {
      $callback = str_replace('@', '::', $callback);
    }
    if (is_callable($callback)) {
      return $callback;
    }
    $callback_ = self::$namespace . $callback;
    if (is_callable($callback_)) {
      return $callback_;
    }
    return false;
  }

  public static function match(array $methods, string $uri, $callback): LaralikeRoute
  {
    assert(count(array_filter($methods, function ($val) {
      return !in_array($val, self::METHODS);
    })) === 0, 'methods is incorrect value !');
    assert(is_callable($callback) || gettype($callback) === 'string', 'callback is incurrect type !');
    assert(self::getCallable($callback) !== false, 'not callable ! `' . $uri . '`');
    $route = new LaralikeRoute($methods, $uri, $callback);
    self::$routes [] = $route;
    return $route;
  }

  public static function get(string $uri, $callback): LaralikeRoute
  {
    return self::match(['get'], $uri, $callback);
  }

  public static function post(string $uri, $callback): LaralikeRoute
  {
    return self::match(['post'], $uri, $callback);
  }

  public static function put(string $uri, $callback): LaralikeRoute
  {
    return self::match(['put'], $uri, $callback);
  }

  public static function patch(string $uri, $callback): LaralikeRoute
  {
    return self::match(['patch'], $uri, $callback);
  }

  public static function delete(string $uri, $callback): LaralikeRoute
  {
    return self::match(['delete'], $uri, $callback);
  }

  public static function options(string $uri, $callback): LaralikeRoute
  {
    return self::match(['options'], $uri, $callback);
  }

  public static function any(string $uri, $callback): LaralikeRoute
  {
    return self::match(self::METHODS, $uri, $callback);
  }

  public static function fallback($fallback)
  {
    assert(self::getCallable($fallback),  'not callable !');
    self::$fallback = $fallback;
  }

  public static function middleware(array $middleware): LaralikeRoute
  {
    assert(!in_array(false, array_map('self::getCallable' , $middleware)), 'not callable !');
    $route = new LaralikeRoute(self::METHODS);
    $route->middleware($middleware);
    self::$routes[] = $route;
    return $route;
  }

  public static function domain(string $domain): LaralikeRoute
  {
    $route = new LaralikeRoute(self::METHODS);
    $route->domain($domain);
    self::$routes[] = $route;
    return $route;
  }

  public static function prefix(string $prefix): LaralikeRoute
  {
    $route = new LaralikeRoute(self::METHODS);
    $route->prefix($prefix);
    self::$routes[] = $route;
    return $route;
  }

  public static function redirect(string $uri, string $jumpto, int $status_code=302): LaralikeRoute
  {
    assert(in_array($status_code, [300, 301, 302, 303, 304, 307, 308]), '$status_code is incorrect');
    $route = new LaralikeRoute(self::METHODS, $uri);
    $route->redirect($jumpto, $status_code);
    self::$routes[] = $route;
    return $route;
  }

  public static function permanentRedirect(string $uri, string $jumpto): LaralikeRoute
  {
    return self::redirect($uri, $jumpto, 301);
  }

  public static function runRedirect(string $jumpto, int $status_code=302)
  {
    assert(in_array($status_code, [300, 301, 302, 303, 304, 307, 308]), '$status_code is incorrect');
    header('Location:' . self::$prefix . $jumpto, true, $status_code);
    exit(0);
  }

  public static function setView($callback): void
  {
    $callback_ = self::getCallable($callback);
    assert($callback_ !== false, 'not callable !');
    self::$view = $callback_;
  }

  public static function view(string $uri, string $viewfile, array $parameters = [])
  {
    assert(isset(self::$view), 'view is not setting yet. please setView()');
    return self::match(
      $options['methods'] ?? self::METHODS,
      $uri,
      function () use ($viewfile, $parameters) {
        return call_user_func_array(self::$view, [$viewfile, $parameters]);
      }
    );
  }

}
