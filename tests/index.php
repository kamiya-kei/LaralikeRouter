<?php

use laralike\LaralikeRouter as Route;

require_once __DIR__ . '/../vendor/autoload.php';

error_reporting(E_ALL); // 全てのエラーを出力
ini_set('zend.assertions', '1'); // assertを有効化
ini_set('assert.exception', '1'); // assertで失敗した時にエラーになるようにする
// エラー画面を見やすくする - filp/whoops
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
Route::get('/ctrl1', '\App\Http\Controllers\TestController@test1');
Route::setNamespace('\\App\\Http\\Controllers\\');
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
    Route::get('/foo', function () { echo 'FOO'; });
    Route::get('/bar', function () { return 'BAR'; });
  });

Route::middleware([function () { return 'GROUP 2 '; }])
  ->prefix('/group2')
  ->group(function () {
    Route::get('/baz', function () { return 'BAZ'; });
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

// ミドルウェアのみのグループ
Route::middleware([function () { return 'ONLY MW '; }])
  ->group(function () {
    Route::get('/onlymw', function () { return 'OK'; });
    Route::get('/onlymw2', function () { return 'OK2'; });
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

// ミドルウェア
if (isset($_GET['mw'])) {
  Route::middleware([function () { return 'GLOVAL_MW '; }]);
  Route::get('/mw', function () { return 'FOO'; });
  Route::prefix('/mwg')
    ->middleware([function () { return 'GROUP_MW '; }])
    ->group(function () {
      Route::get('/', function () { return 'BAR'; });
      Route::get('/baz', function () { return 'BAZ'; })
        ->middleware([function () { return 'ROUTE_MW '; }]);
    });
}
