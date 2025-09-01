<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

//Route::get('/', function () {
//    return view('welcome');
//});

Route::get('/browser/{any}', function () {
    $path = public_path('/browser/index.html');
    abort_unless(file_exists($path), 400, 'Page is not Found!');
    return file_get_contents($path);
})->name('welcomePage');


Route::get('/wechat/auth', [App\Http\Controllers\Api\WechatController::class, 'auth']);
Route::get('/wechat/callback', [App\Http\Controllers\Api\WechatController::class, 'callback']);


// 微信开发者工具页面
// Route::get('/wechat/dev-tool', [App\Http\Controllers\Api\WechatController::class, 'devToolGetOpenid']);

// // 微信开发者工具API（临时使用web路由）
// Route::post('/wechat/dev-tool-api', [App\Http\Controllers\Api\WechatController::class, 'devToolGetOpenidApi'])
//     ->middleware('web');

    

// // 微信授权相关路由
// Route::prefix('wechat')->group(function () {
//     Route::get('/auth', [App\Http\Controllers\Api\WechatController::class, 'auth']);
//     Route::get('/callback', [App\Http\Controllers\Api\WechatController::class, 'callback']);
// });
// Route::get('/wechat/status', [App\Http\Controllers\Api\WechatController::class, 'getOpenidStatus'])->name('wechat.status');
// Route::get('/wechat/clear', [App\Http\Controllers\Api\WechatController::class, 'clearOpenid'])->name('wechat.clear');

// // 微信开发者工具页面（保持兼容）
// Route::get('/wechat/get-openid', [App\Http\Controllers\Api\WechatController::class, 'auth']);

// // 微信授权测试页面
// Route::get('/wechat/test', function() {
//     return view('wechat.test');
// })->name('wechat.test');

// // 微信授权调试页面
// Route::get('/wechat/debug', function() {
//     $config = [
//         'appid' => env('WEIXIN_APPID'),
//         'has_secret' => !empty(env('WEIXIN_SECRET')),
//         'app_url' => env('APP_URL'),
//         'callback_url' => url('/wechat/callback'),
//         'auth_url' => url('/wechat/auth'),
//         'domain' => request()->getHost(),
//         'is_https' => request()->isSecure(),
//         'user_agent' => request()->header('User-Agent'),
//         'is_wechat_browser' => strpos(request()->header('User-Agent'), 'MicroMessenger') !== false
//     ];
//     return view('wechat.debug', compact('config'));
// })->name('wechat.debug');

// // 微信授权调试API
// Route::get('/wechat/debug/config', [App\Http\Controllers\Api\WechatController::class, 'debugConfig']);
// Route::get('/wechat/debug/auth-url', [App\Http\Controllers\Api\WechatController::class, 'debugAuthUrl']);
// Route::get('/wechat/debug/callback-test', [App\Http\Controllers\Api\WechatController::class, 'debugCallbackTest']);
