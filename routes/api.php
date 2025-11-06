<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// 定义一个新的测试路由
// 访问 http://adspower.local/api/welcome 成功

// 不需要登录验证的测试路由
Route::get('/welcome', function () {
    return response()->json([
        'message' => 'welcome api',
        'timestamp' => now()
    ]);
});
 
Route::post('/checkInvitationCode', [App\Http\Controllers\AuthController::class, 'checkInvitationCode']);
 

Route::post('/autoRegisterAndLogin', [App\Http\Controllers\AuthController::class, 'autoRegisterAndLogin']);

// 需要认证的路由
// Route::post('/login', [App\Http\Controllers\AuthController::class, 'login']);

Route::get('/globalconfig', [App\Http\Controllers\Api\GlobalConfigController::class, 'getConfig']);
 

Route::any('/checkToken', [App\Http\Controllers\Api\TokenController::class, 'checkToken']);  // 生成加密链接

Route::post('/upload', [App\Http\Controllers\Api\FileUploadController::class, 'upload']);  //上传图片

// 用户名搜索API（用于后台管理用户筛选）
Route::get('/users', function (Request $request) {
    $q = $request->get('q');
    return \App\Models\User::where('username', 'like', "%$q%")
        ->paginate(null, ['id', 'username as text']);
});

Route::match(['get', 'post'], '/geoip',[App\Http\Controllers\Api\GeoIpController::class,'geoip']);



//--- 路由组开始,需要验证token
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/usertesta', function () {
        return response()->json([
            'message' => '认证成功',
            'user' => auth()->user()
        ],200);
    });

    Route::match(['get', 'post'], '/jdshop/goodsQuery',[App\Http\Controllers\Api\JdApiController::class,'goodsQuery']);

    Route::match(['get', 'post'], '/jdshop/orderQuery',[App\Http\Controllers\Api\JdApiController::class,'orderQuery']);

    Route::match(['get', 'post'], '/order/index',[App\Http\Controllers\Api\OrdersController::class,'index']);

    Route::match(['get', 'post'], '/jdshop/goodslist',[App\Http\Controllers\Api\JdGoodsController::class,'index']);


    Route::match(['get', 'post'], '/income/index',[App\Http\Controllers\Api\IncomeController::class,'index']);

    Route::match(['get', 'post'], '/income/sumorder',[App\Http\Controllers\Api\IncomeController::class,'sumorder']);

    Route::match(['get', 'post'], '/income/orderlists',[App\Http\Controllers\Api\IncomeController::class,'orderlists']);

    //jd转链接
    Route::match(['get', 'post'], '/jdshop/bysubunionid',[App\Http\Controllers\Api\JdApiController::class,'bysubunionid']);

    Route::get('/user/index',  [App\Http\Controllers\Api\UserController::class, 'index']);

    Route::post('/user/storerealname',[App\Http\Controllers\Api\UserController::class,'storeRealName']);

    Route::get('/user/userinfo', [App\Http\Controllers\Api\UserController::class, 'userinfo']);  #获取用户信息
    
 
    Route::match(['get', 'post'], '/user/update', [App\Http\Controllers\Api\UserController::class, 'updateUser']);

     
    //推广记录
    Route::post('/promotion/list', [App\Http\Controllers\Api\PromotionController::class, 'index']);  #推广记录列表
 
    Route::post('/jdgoods/share', [App\Http\Controllers\Api\JdGoodsController::class, 'share']);  #商品分享

});


// Route::match(['get', 'post'], '/jdshop/bysubunionid',[App\Http\Controllers\Api\JdApiController::class,'bysubunionid']);



// H5 登录路由（不需要认证）
Route::match(['get', 'post'], '/h5/login',[App\Http\Controllers\Api\h5\LoginController::class,'login']);

// // H5 需要认证的路由组（使用中间件方式）
// Route::middleware('h5.auth')->group(function () {
//     Route::match(['get', 'post'], '/h5/getUserInfo',[App\Http\Controllers\Api\h5\LoginController::class,'getUserInfo']);
// });

// H5 需要认证的路由组（使用中间件方式）
Route::prefix('h5')->middleware('h5.auth')->group(function () {
    // 用户相关路由
    Route::match(['get', 'post'], '/jdgoods/bysubunionid',[App\Http\Controllers\Api\h5\JdGoodsController::class,'bysubunionid']);
    
});



