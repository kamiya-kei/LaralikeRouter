# LaralikeRouter

単体で動作する[Laravel](http://laravel.jp/)ライクなルーティングライブラリを自作しました。

フレームワークを使う程では無い極小規模な開発で、あまり速度を重視しない時に使用することを想定してます。

composerを使って導入できます。PHPのバージョンは7.1以上が必要です。

GitHub:  [kamiya-kei/LaralikeRouter](https://github.com/kamiya-kei/LaralikeRouter)

## 導入方法

### 方法1：composerを利用する方法

```powershell
composer require kamiya-kei/laralike-router
```

```php
<?php

use \kamiyakei\LaralikeRouter as Route;

require_once 'vendor/autoload.php';

Route::get('/', function () { echo 'hello milky'; });
```



```php
<?php

require_once 'LaralikeRouter.php';

class_alias(\kamiyakei\LaralikeRouter::class, 'Route');

Route::get('/', function () { echo 'hello milky'; });
```

`.htaccess`等を利用し、全てのアクセスをルーティングを書いてるphpに飛ばす様にしておく。

```.htaccess
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ index.php [L]
```

## 使い方

ほとんど[Laravelのルーティング](https://readouble.com/laravel/7.x/ja/routing.html)と同じ使い方です。

### 基本的なルーティング

第1引数は、`/`から始めても、`/`から始めなくても、同じ様に動作します。

```php
Route::get('/', function () { return 'hello milky'; });

Route::get('/foo', function () { return 'FOO'; });
Route::get('bar', function () { return 'BAR'; });

// returnされた値がarray型の場合は、
// `Content-Type: application/json`ヘッダを出力して、戻り値をjson_encodeして出力します。
Route::get('baz', function () { return ['BAZ']; });
```

コントローラーを利用する場合は以下の様にします。メソッドの前は`@`ではなく`::`でもOKです。

```php
Route::get('/', '\App\Controller\TestController@index');
```

省略して書く場合は以下の様にコントローラーの`namespace`を設定します。

```php
Route::setNamespace('\\App\\Controller\\');
Route::get('/', 'TestController@index');
```

Laravelと同じ方法でHTTPメソッドを定義できます。

```php
Route::get($uri, $callback);
Route::post($uri, $callback);
Route::put($uri, $callback);
Route::patch($uri, $callback);
Route::delete($uri, $callback);
Route::options($uri, $callback);

Route::match(['get', 'post'], '/', $callback);
Route::any('/', $callback);
```

### リダイレクトルート

Laravelと同じ方法でリダイレクトルートを定義できます。

```php
Route::redirect('/here', '/there'); // 302
Route::redirect('/here', '/there', 301);
Route::permanentRedirect('/here', '/there'); // 301
```

Laravelには無い記法ですが、以下の様にも使用できます。

```php
Route::get('/here', function () { Route::runRedirect('/there'); }); // 302
Route::get('/here', function () { Route::runRedirect('/there', 301); });
```

ルートグループ内で使った場合は以下の様に動作します。

```php
Route::prefix('/foo')->group(function () {
	Route::redirect('/here', '/there'); // `/foo/here` => `/foo/there`
});
```

### ビュールート

ビュールートを定義する場合は、ご自分で好きなテンプレートエンジンライブラリを入れ、以下の様な感じで設定してください。(以下はtwigの設定例)

```php
function view ($viewfile, $parameters) {
  $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/');
  $twig = new \Twig\Environment($loader, [
    'debug' => true
  ]);
  return $twig->render($viewfile, $parameters);
}
Route::setView('view');
Route::prefix('/view')->group(function () {
  // 以下の様に使用するなら`Route::setView`は不要です。
  Route::get('/twig1', function () { echo view('test.html.twig', ['name' => 'lala']); });
  // 以下の様ビュールートを使用する場合は`Route::setView`で予め設定する必要がある。
  Route::view('/twig2', 'test.html.twig', ['name' => 'milky']);
});
```

### ルートパラメーター

Laravelと同じ方法でルートパラメーターを定義できます。

デフォルトでは`/`を除く全ての文字を許可しています。

#### 必須パラメーター

```php
Route::get('user/{id}', function ($id) {
    return 'User '.$id;
});
```

```php
Route::get('posts/{post}/comments/{comment}', function ($postId, $commentId) {
    //
});
```

#### 任意パラメーター

```php
Route::get('user/{name?}', function ($name = null) {
    return $name;
});

Route::get('user/{name?}', function ($name = 'lala') {
    return $name;
});
```

#### 正規表現制約

正規表現制約はLaravelと違って第3引数で指定します。

また、Laravelの様に`/`を許可することはできません。

```php
Route::get('user/{id}', function ($id) {
    //
})->where(['id' => '[0-9]+']);

Route::get('user/{id}/{name}', function ($id) {
    //
})->where(['where' => ['id' => '[0-9]+', 'name' => '[a-z]+']);
```



### ルートグループ

ルートグループは以下の様に定義します。

ネストにも対応しています。

```php
Route::prefix('/foo')->group(function () {
    // `/foo/bar`で`FOOBAR`と出力される
	Route::get('/bar', function () { echo 'FOOBAR' });
});

Route::middleware([function () { echo 'Hello '; }])
  ->prefix('/hello')
  ->group(function () {
    // `hello/lala`で`Hello lala`と出力される
    Route::get('/lala', function () { echo 'lala'; });
    // `hello/hikaru`で`Hello hikaru`と出力される
    Route::get('/hikaru', function () { echo 'hikaru'; });
});

//　ネスト
Route::prefix('/hello')
  ->middleware([function () { echo 'Hello '; }])
  ->group(function () {
    Route::prefix('/cure')
      ->middleware([function () { echo 'Cure '; }])
      ->group(function () {
          // `hello/cure/milky`で`Hello Cure Milky`と出力される
          Route::get('/milky', function () { echo 'Milky'; });
      });
  });

```

### フォールバックルート (404 Not Found)

Laravelと同じ方法で全てのルートに一致しない場合の処理を定義できます。

```php
Route::fallback(function () {
  // 404
});
```

定義しなかった場合、自動で`Route::defaultFallback()`が呼ばれます。

`defaultFallback`の中身は以下の様な感じになっており、`register_shutdown_function`を使って呼んでいます。

```php
  public static function defaultFallback()
  {
    header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
    echo '404 Not Found';
  }
```
