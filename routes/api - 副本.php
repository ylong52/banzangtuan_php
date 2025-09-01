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


Route::post('/autoRegisterAndLogin', [App\Http\Controllers\AuthController::class, 'autoRegisterAndLogin']);

// 需要认证的路由
Route::post('/login', [App\Http\Controllers\AuthController::class, 'login']);

Route::get('/globalconfig', [App\Http\Controllers\Api\GlobalConfigController::class, 'getConfig']);


 

// 忘记密码
Route::post('/forgotPassword', [App\Http\Controllers\AuthController::class, 'forgotPassword']); 

// 发送验证码
Route::post('/send-verify-code', [App\Http\Controllers\AuthController::class, 'sendVerifyCode']);

Route::any('/checkToken', [App\Http\Controllers\Api\TokenController::class, 'checkToken']);  // 生成加密链接

Route::post('/upload', [App\Http\Controllers\Api\FileUploadController::class, 'upload']);  //上传图片


Route::get('/product/category', [App\Http\Controllers\Api\ProductCategoryController::class, 'index']);

Route::get('/product/category/level2', [App\Http\Controllers\Api\ProductCategoryController::class, 'level2'])->name('product.category.level2');  // 可通过传参 ?type=xxx 过滤类型


Route::get('/product/detail/{id}', [App\Http\Controllers\Api\ProductDetailController::class, 'index']);  #商品详情
Route::get('/product/category/ayqy', [App\Http\Controllers\Api\ProductCategoryController::class, 'getFromAyqyApi']);

Route::get('/product/getFromAyqyApi', [App\Http\Controllers\Api\ProductsController::class, 'getFromAyqyApi']);  #请求商品[从上游] 
Route::post('/product', [App\Http\Controllers\Api\ProductsController::class, 'index']);  #商品列表

// Ayqy 同步商品
Route::get('/sync/product/detail', [App\Http\Controllers\Api\SyncAyqyController::class, 'SyncProductDetail']);  #同步商品详情
Route::get('/sync/product', [App\Http\Controllers\Api\SyncAyqyController::class, 'SyncProduct']);  #同步商品


// Route::get('/order', [App\Http\Controllers\OrderController::class, 'index']);  #订单列表

Route::get('/productcategory/getFromAyqyApi', [App\Http\Controllers\Api\ProductCategoryController::class, 'getFromAyqyApi']);

//支付回调地址
Route::post('/payment/callback', [App\Http\Controllers\Api\PaymentController::class, 'callback']);

Route::post('/transfer/callback', [App\Http\Controllers\Api\PaymentController::class, 'transfercallback']);

//--- 路由组开始,需要验证token
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/usertesta', function () {
        return response()->json([
            'message' => '认证成功',
            'user' => auth()->user()
        ],200);
    });

    Route::get('/user/index',  [App\Http\Controllers\Api\UserController::class, 'index']);

    Route::get('/userinfo', [App\Http\Controllers\Api\UserController::class, 'userinfo']);  #获取用户信息

    Route::get('/user/balance', [App\Http\Controllers\Api\UserController::class, 'balance']);  #获取用户余额

    Route::match(['get', 'post'], '/user/update', [App\Http\Controllers\Api\UserController::class, 'updateUser']);

    Route::post('/logout', [App\Http\Controllers\AuthController::class, 'logout']);

    Route::post('change-password', [\App\Http\Controllers\AuthController::class, 'changePassword']);


    Route::get('/order/orderinfo/{order_number}', [App\Http\Controllers\Api\OrderController::class, 'getOrderInfo']);  #获取订单信息
 
    Route::post('/order/pay', [App\Http\Controllers\Api\OrderController::class, 'payOrder']);  #支付订单

    //export const cancelOrder = (orderNumber) => request.post(`/api/order/cancel/${orderNumber}`);
    Route::post('/order/cancel/{order_number}', [App\Http\Controllers\Api\OrderController::class, 'cancelOrder']);  #取消订单    
    Route::post('/order/list', [App\Http\Controllers\Api\OrderController::class, 'list']);  #订单列表
    
    //充值记录
    Route::post('/recharge/createpay', [App\Http\Controllers\Api\RechargeRecordController::class, 'createpay']);  #创建充值记录
    Route::post('/recharge/list', [App\Http\Controllers\Api\RechargeRecordController::class, 'index']);  #充值记录列表
    Route::post('/recharge/show/{id}', [App\Http\Controllers\Api\RechargeRecordController::class, 'show']);  #充值记录详情   

    //提现记录
    Route::post('/withdrawal/create', [App\Http\Controllers\Api\UserWithdrawalController::class, 'create']);  #创建提现记录
    Route::post('/withdrawal/list', [App\Http\Controllers\Api\UserWithdrawalController::class, 'index']);  #提现记录列表
    Route::post('/withdrawal/show/{id}', [App\Http\Controllers\Api\UserWithdrawalController::class, 'show']);  #提现记录详情   
    Route::post('/withdrawal/delete/{id}', [App\Http\Controllers\Api\UserWithdrawalController::class, 'delete']);  #删除提现记录
    // Route::post('/withdrawal/wechatconfirm/{id}', [App\Http\Controllers\Api\UserWithdrawalController::class, 'wechatConfirm']);  #微信待收款确认

    //推广记录
    Route::post('/promotion/list', [App\Http\Controllers\Api\PromotionController::class, 'index']);  #推广记录列表
 
    //绑定账户
    Route::post('/account/getOne', [App\Http\Controllers\Api\AccountBindingController::class, 'getOne']);  #绑定账户
    Route::post('/account/store', [App\Http\Controllers\Api\AccountBindingController::class, 'store']);  #绑定账户
    Route::post('/account/delete/{type}', [App\Http\Controllers\Api\AccountBindingController::class, 'delete']);  #删除绑定账户

    Route::post('/account/modifyUseAccounttype/{use_accounttype}', [App\Http\Controllers\Api\AccountBindingController::class, 'modifyUseAccounttype']);  #修改绑定账户类型

    //修改微信用户名
    Route::post('/account/modifyWechatName', [App\Http\Controllers\Api\AccountBindingController::class, 'modifyWechatName']);  #修改微信用户名

    //微信待收款确认
    Route::post('/withdrawal/wechatconfirm', [App\Http\Controllers\Api\UserWithdrawalController::class, 'wechatConfirm']);

    //微信待收款后确认状态
    Route::post('/withdrawal/wxcheckTransferStatus', [App\Http\Controllers\Api\UserWithdrawalController::class, 'wxcheckTransferStatus']);


});

