<?php

use laralike\LaralikeRouter as Route;

require_once __DIR__ . '/../vendor/autoload.php';

ini_set('assert.exception', '1');
error_reporting(E_ALL | E_STRICT);
// $whoops = new Whoops\Run;
// $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
// $whoops->register();

if (0 === strpos($_SERVER['REQUEST_URI'] ?? '', '/tests/')) {
  // プレフィックス
  Route::prefix('/tests');
}

// 基本的なルーティング
Route::get('/', function () { return 'ROOT'; });
Route::any('/aaa', function () { return ['AAA']; });
Route::post('/bbb', function () { return 'BBB'; });
Route::match(['get', 'post'], '/ccc/ddd', function () { return 'CCCDDD'; });

// リダイレクトルート
Route::redirect('index.php', '/');
Route::get('index.html', function () { Route::runRedirect('/'); });

// コントローラー
Route::get('/ctrl1', '\App\Controller\TestController@test1');
Route::setNamespace('\\App\\Controller\\');
Route::get('/ctrl2', 'TestController@test2');

// 必須パラメータ
Route::get('user/{id}', function ($id) { return 'UserId: ' . $id; });
Route::get('posts/{post}/comments/{comment}', function ($postId, $commentId) {
  return $postId . '-' . $commentId;
});

// 任意パラメータ
Route::get('username/{name?}', function ($name = 'lala') {
  return 'NAME: ' . $name;
});

// 正規表現制約
Route::get('userage/{age}', function ($age) {
  return 'AGE: ' . (string)$age;
})->where(['age' => '[0-9]+']);

// ルートグループ
Route::prefix('group1')
  ->middleware([function () { echo 'GROUP 1 '; }])
  ->group(function () {
    define('GROUP_VAR', 'BAR');
    Route::get('/foo', function () { echo 'FOO'; });
    Route::get('/bar', function () { return GROUP_VAR; });
  });

Route::middleware([function () { return 'GROUP 2 '; }])
  ->prefix('/group2')
  ->group(function () {
    define('GROUP_VAR', 'BAZ');
    Route::get('/baz', function () { return GROUP_VAR; });
    Route::prefix('/nest')
      ->middleware([function () { return 'NEST '; }])
      ->group(function () {
        Route::get('/hoge', function () { return 'HOGE'; });
        Route::get('/fuga', function () { echo 'FUGA'; });
      });
      Route::prefix('/nest2')
      ->middleware([function () { return 'NEST '; }])
      ->group(function () {
        Route::get('/hoge', function () { return 'HOGE'; });
        Route::get('/fuga', function () { echo 'FUGA'; });
      });


  });

// ルートグループ内のリダイレクトルート
Route::prefix('/redirect')->group(function () {
  Route::get('/', function () { return 'REDIRECT'; });
  Route::get('/index.php', function () { Route::runRedirect('/'); });
  Route::redirect('/index.html', '/');
});

// 404
// Route:fallback()を呼んでない時はRoute::defaultFallback()が呼ばれる
if (isset($_GET['fallback'])) {
  // カスタム404
  Route::fallback(function () { return '404 !'; });
}

// ビュー
function view ($viewfile, $parameters) {
  $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/');
  $twig = new \Twig\Environment($loader, [
    'debug' => true
  ]);
  return $twig->render($viewfile, $parameters);
}
Route::setView('view');
Route::prefix('/view')->group(function () {
  Route::get('/twig1', function () { return view('test.html.twig', ['name' => 'lala']); });
  Route::view('/twig2', 'test.html.twig', ['name' => 'milky']);
  Route::get('/json', function () { return ['name' => 'star']; });
});

// パフォーマンステスト
$pf = $_GET['performance'] ?? false;
if ($pf === '1') {
  define('LARALIKE_START', microtime(true));
  for ($i = 1;  $i <= 10000; $i++) {
    Route::get('/pf/' . (string)$i, function () use ($i) {  return $i . '...' . (string)(microtime(true) - LARALIKE_START); });
  }
} else if ($pf === '2') {
  define('LARALIKE_START', microtime(true));
  for ($i = 1;  $i <= 10000; $i++) {
    Route::get('/pf/{id}/' . (string)$i, function ($id) use ($i) {  return $id . $i . '...' . (string)(microtime(true) - LARALIKE_START); });
  }
}
