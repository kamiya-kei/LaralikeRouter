<?php

namespace laralike;

use \laralike\LaralikeRoute;

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

  private static $namespace = 'App\\Http\\Controllers\\';

  private static $routes = [];
  public static $is_test_mode = false;

  public static $prefix = '';
  public static $where = [];
  public static $middleware = [];
  public static $instances = [];

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
    foreach (self::$routes as $route) {
      $type = $route->getType();
      // var_dump([$route, self::$prefix]);
      switch ($type) {
      case 'routing':
        $is_hit = $route->isHit($uri, $method);
        if ($is_hit) {
          $route->runRoute();
          // r(self::$instances);
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
        $routes_ = self::$routes;
        // set
        self::$prefix .= $route->prefix;
        self::$where = array_merge(self::$where, $route->where ?? []);
        self::$middleware = array_merge(self::$middleware, $route->middleware);
        self::$routes = [];
        // routing
        $route->runGroup();
        $is_hit = self::routing($uri, $method, true); // 再帰
        if ($is_hit) { return true; }
        // reset
        self::$prefix = $prefix_;
        self::$where = $where_;
        self::$middleware = $middleware_;
        self::$routes = $routes_;
        break;
      default:
        assert(false, 'invalid type: ' . $type);
      }
    }
    if ($is_nest) { return false; }
    if (isset(self::$fallback)) {
      $res_fallback = self::runCallback(self::$fallback, []);
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
    if ('string' === gettype($callback) && false !== strpos($callback, '@')) {
      [$class, $method] = explode('@', $callback);
      if (method_exists($class, $method)) {
        return [$class, $method];
      }
      $class_ = self::$namespace . $class;
      if (method_exists($class_, $method)) {
        return [$class_, $method];
      }
      return false;
    }
    if (is_callable($callback)) {
      return $callback;
    }
    return false;
  }

  public static function runCallback($callback, $parameters)
  {
    $callback = self::getCallable($callback);
    assert($callback !== false,  'not callable !');
    if ('array' === gettype($callback)) {
      [$class, $method] = $callback;
      $instance = self::getInstance($class);
      return call_user_func_array([$instance, $method], $parameters);
    }
    return call_user_func_array($callback, $parameters);
  }

  public static function getInstance(string $class)
  {
    foreach (self::$instances as $instance) {
      if ($instance instanceof $class) {
        return $instance;
      }
    }
    $instance_ = new $class;
    self::$instances[] = $instance_;
    return $instance_;
  }

  public static function match(array $methods, string $uri, $callback): LaralikeRoute
  {
    assert(count(array_filter($methods, function ($val) {
      return !in_array($val, self::METHODS);
    })) === 0, 'methods is incorrect value !');
    assert(is_callable($callback) || 'string' === gettype($callback), 'callback is invalid type !');
    assert(false !== self::getCallable($callback), 'not callable ! `' . $uri . '`');
    $route = new LaralikeRoute($methods, $uri, $callback, 'routing');
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

  // 未実装
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
