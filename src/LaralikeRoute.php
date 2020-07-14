<?php

namespace kamiyakei;

use \kamiyakei\LaralikeRouter as Route;

class LaralikeRoute
{
  public $methods;
  public $uri_ptn;
  public $callback;
  public $where;
  public $prefix; // (string) 'routing'|'prefix'|'group'
  public $domain;
  public $middleware;
  public $type;

  public $parameters;

  public function __construct(?array $methods = null, ?string $uri_pattern = null, $callback = null)
  {
    $this->methods = $methods;
    $this->uri_ptn = (substr($uri_pattern, 0, 1) === '/' ? '' : '/') . $uri_pattern;
    $this->callback = $callback;
    $this->where = [];
    $this->prefix = '';
    $this->middleware = [];
    $this->parameters = [];
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
    if (false === strpos($uri_ptn, '{')) {
      return $uri === $uri_ptn;
    }

    foreach ($this->where as $key => $ptn) {
      $uri_ptn = str_replace('{' . $key . '?}', '(?P<' . $key . '>' . $ptn . ')?', $uri_ptn);
      $uri_ptn = str_replace('{' . $key . '}', '(?P<' . $key . '>' . $ptn . ')', $uri_ptn);
    }
    $uri_ptn = str_replace('{', '(?P<', $uri_ptn);
    $uri_ptn = str_replace('?}', '>[^/]+?)?', $uri_ptn);
    $uri_ptn = str_replace('}', '>[^/]+?)', $uri_ptn);
    $uri_ptn = '/^' . str_replace('/', '\\/', $uri_ptn) . '$/';

    $parameters = [];
    preg_match($uri_ptn, $uri, $matches);
    // r([$uri_ptn, self::$uri, $matches]);
    foreach ($matches as $key => $val) {
      if ($key === 0) { continue; }
      if (is_numeric($key)) {
        $parameters[] = $val;
        continue;
      }
    }
    $this->parameters = $parameters;
    return count($matches) !== 0;
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

  public function runCallback()
  {
    $callback = Route::getCallable($this->callback);
    assert($callback !== false,  'not callable !');
    return call_user_func_array($callback, $this->parameters);
  }

  public function redirect(string $jumpto, int $status_code) {
    $this->callback = function () use ($jumpto, $status_code) {
      header('Location:' . Route::$prefix . $this->prefix . $jumpto, true, $status_code);
      exit(0);
    };
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
    $this->type = 'prefix';
    return $this;
  }

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