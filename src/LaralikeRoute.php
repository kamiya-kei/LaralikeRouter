<?php

namespace laralike;

use \laralike\LaralikeRouter as Route;

class LaralikeRoute
{
  public $methods;
  public $uri_ptn;
  public $callback;
  public $where;
  public $prefix;
  public $domain;
  public $middleware;
  public $type; // (string) 'routing'|'prefix'|'group'|'domain'

  public $parameters;

  public function __construct(
    ?array $methods = null,
    ?string $uri_pattern = null,
    $callback = null, // string|callable
    ?string $type = null
  ) {
    $this->methods = $methods;
    $this->uri_ptn = (substr($uri_pattern, 0, 1) === '/' ? '' : '/') . $uri_pattern;
    $this->callback = $callback;
    $this->where = [];
    $this->prefix = '';
    $this->middleware = [];
    $this->parameters = [];
    $this->type = $type;
  }

  public function getType(): string
  {
    return $this->type ?? 'routing';
  }

  public function isHit(string $uri, string $method): bool
  {
    if (!in_array($method, $this->methods)) { return false; }
    $uri_ptn = Route::$prefix . $this->prefix . $this->uri_ptn;
    // r([$uri_ptn, $this]);

    // 任意パラメータ無し
    if (false === strpos($uri_ptn, '{')) {
      return $uri === $uri_ptn;
    }

    // 任意パラメータ有り
    // まずは任意パラメータ以外の部分をチェック (パフォーマンス向上の為)
    $i = 0;
    foreach (explode('{', $uri_ptn) as $tmp) {
      foreach (explode('}', $tmp) as $string) {
        if ($i % 2 == 0 && strlen($string) !== 0 && strpos($uri, $string) === false) {
          return false;
        }
        $i++;
      }
    }

    // 任意パラメータの部分を含めたチェック
    $uri_ptn = str_replace('?}', '}?', $uri_ptn);
    foreach ($this->where as $key => $ptn) {
      $uri_ptn = str_replace('{' . $key . '}', '(?P<' . $key . '>' . $ptn . ')', $uri_ptn);
    }
    $uri_ptn = str_replace('{', '(?P<', $uri_ptn);
    $uri_ptn = str_replace('}', '>[^/]+)', $uri_ptn);
    $uri_ptn = '/^' . str_replace('/', '\\/', $uri_ptn) . '$/';

    preg_match($uri_ptn, $uri, $matches);
    // r([$uri_ptn, self::$uri, $matches]);
    if (count($matches) === 0) { return false; }

    $this->parameters = array_filter($matches, function ($key) {
      return is_numeric($key) && $key !== 0;
    }, ARRAY_FILTER_USE_KEY);
    return true;
  }

  public function isHitGroup(string $uri, string $method): bool
  {
    $uri_ptn = Route::$prefix . $this->prefix . $this->uri_ptn;
    if (false === strpos($uri_ptn, '{')) {
      // r([$uri_ptn, $uri, strpos($uri, $uri_ptn), $this]);
      return strpos($uri, $uri_ptn) === 0;
    }
    // 正規表現未実装
    return false;
  }

  public function runRoute(): void
  {
    // global middleware and group middleware
    foreach (Route::$middleware as $middleware) {
      $res_middleware = Route::runCallback($middleware, $this->parameters);
      Route::returnAction($res_middleware);
    }
    // route middleware
    foreach ($this->middleware as $middleware) {
      $res_middleware = Route::runCallback($middleware, $this->parameters);
      Route::returnAction($res_middleware);
    }
    // route callback
    $res = Route::runCallback($this->callback, $this->parameters);
    Route::returnAction($res);
  }

  public function runGroup(): void
  {
    call_user_func_array($this->callback, []);
  }

  public function redirect(string $jumpto, int $status_code): self {
    $this->type = 'routing';
    $this->callback = function () use ($jumpto, $status_code) {
      header('Location:' . Route::$prefix . $this->prefix . $jumpto, true, $status_code);
      exit(0);
    };
    return $this;
  }

  public function where($key, ?string $ptn=null): self
  {
    if (isset($ptn)) {
      assert(gettype($key) === 'string', 'where args is (string, string) or (array)');
      $this->where = [$key => $ptn];
    } else {
      assert(gettype($key) === 'array', 'where(string, string) or where(array)');
      $this->where = $key;
    }
    return $this;
  }

  public function prefix(string $prefix): self
  {
    if ($prefix === '' || $prefix === '/') { return $this; }
    $this->prefix = (substr($prefix, 0, 1) === '/' ? '' : '/') . $prefix;
    $this->type = $this->type ?? 'prefix';
    return $this;
  }

  // 未実装
  public function domain(string $domain): self
  {
    $this->domain = $domain;
    $this->type = $this->type ?? 'domain';
    return $this;
  }

  public function middleware(array $middleware): self
  {
    $this->middleware = array_merge($this->middleware, $middleware);
    $this->type = $this->type ?? 'middleware';
    return $this;
  }

  public function group($callback): self
  {
    assert(Route::getCallable($callback) !== false,  'not callable !');
    $this->callback = $callback;
    $this->type = 'group';
    return $this;
  }

}