<?php

use \kamiyakei\LaralikeRouter as Route;

require_once __DIR__ . '/../vendor/autoload.php';

ini_set('assert.exception', '1');
error_reporting(E_ALL | E_STRICT);

if (0 === strpos($_SERVER['REQUEST_URI'], '/tests/')) {
  // プレフィックス
  Route::setPrefix('/tests');
}

// 基本的なルーティング
Route::get('/', function () { echo 'ROOT'; });
Route::any('/aaa', function () { echo 'AAA'; });
Route::post('/bbb', function () { echo 'BBB'; });
Route::match(['get', 'post'], '/ccc/ddd', function () { echo 'CCCDDD'; });

// リダイレクトルート
Route::redirect('index.php', '/');
Route::get('index.html', function () { Route::redirect('/'); });

// コントローラー
Route::get('/ctrl1', '\App\Controller\TestController::test1');
Route::setNamespace('\\App\\Controller\\');
Route::get('/ctrl2', 'TestController::test2');

// 必須パラメータ
Route::get('user/{id}', function ($id) { echo 'UserId: ' . $id; });
Route::get('posts/{post}/comments/{comment}', function ($postId, $commentId) {
  echo $postId . '-' . $commentId;
});

// 任意パラメータ
Route::get('username/{name?}', function ($name = 'lala') {
  echo 'NAME: ' . $name;
});

// 正規表現制約
Route::get('userage/{age}', function ($age) {
  echo 'AGE: ' . (string)$age;
}, ['where' => ['age' => '[0-9]+']]);

// ルートグループ
Route::group('/group1', [function () { echo 'GROUP 1 '; }], function () {
  define('GROUP_VAR', 'BAR');
  Route::get('/foo', function () { echo 'FOO'; });
  Route::get('/bar', function () { echo GROUP_VAR; });
});
Route::group('group2', [], function () {
  Route::middleware([ function () { echo 'GROUP 2 '; }]);
  define('GROUP_VAR', 'BAZ');
  Route::get('/baz', function () { echo GROUP_VAR; });
  Route::group('/nest', [function () { echo 'NEST '; }], function () {
    Route::get('/hoge', function () { echo 'HOGE'; });
    Route::get('/fuga', function () { echo 'FUGA'; });
  });
});

// ルートグループ内のリダイレクトルート
Route::group('/redirect', [], function () {
  Route::get('/', function () { echo 'REDIRECT'; });
  Route::get('/index.php', function () { Route::redirect('/'); });
  Route::redirect('/index.html', '/');
});

// 404
// Route:fallback()を呼んでない時はRoute::defaultFallback()が呼ばれる
if (isset($_GET['fallback'])) {
  // カスタム404
  Route::fallback(function () { echo '404 !'; });
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
Route::group('/view', [], function () {
  Route::get('/twig1', function () { echo view('test.html.twig', ['name' => 'lala']); });
  Route::view('/twig2', 'test.html.twig', ['name' => 'milky']);
  Route::get('/json', function () { echo Route::json(['name' => 'star']); });
});

// コンフィグ
Route::config('sqlite', 'foo.db');
Route::get('/config-foo', function () { echo Route::config('sqlite'); });
Route::config('sqlite', 'bar.db');
Route::get('/config-bar', function () { echo Route::config('sqlite'); });
Route::group('/config', [
  function () { Route::config('sqlite', 'baz.db'); }
], function () {
  Route::get('/', function () { echo Route::config('sqlite'); });
});
Route::get('/config-bar2', function () { echo Route::config('sqlite'); });
