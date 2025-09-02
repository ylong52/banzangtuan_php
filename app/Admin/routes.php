<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Encore\Admin\Facades\Admin;
 
Admin::routes();

// Add captcha route outside the auth middleware
Route::get('admin/captcha', 'App\Admin\Controllers\CaptchaController@generateCaptcha');
Route::get('admin/test-login', function() {
    return view('admin.auth.test_login');
});
Route::get('admin/login-test', function() {
    return view('admin.auth.login_test');
});
Route::get('admin/debug-login', function() {
    return view('admin.auth.debug_login');
});
Route::post('admin/test-login', 'App\Admin\Controllers\TestLoginController@testLogin');
Route::post('admin/test-captcha', 'App\Admin\Controllers\TestLoginController@testCaptcha');
Route::post('admin/check-user', 'App\Admin\Controllers\TestLoginController@checkUser');
Route::get('admin/captcha-info', 'App\Admin\Controllers\TestLoginController@getCaptchaInfo');

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
    'as'            => config('admin.route.prefix') . '.',
], function (Router $router) {

    $router->get('/', 'HomeController@index')->name('home');
    // $router->get('/', 'BorderController@index')->name('home');
     
    $router->resource('user', 'UserController');
   
   
    $router->get('jdgoods/query', 'JdGoodsController@goodsQueryPage')->name('jd_goods.query_page');
    $router->post('jdgoods/query', 'JdGoodsController@queryGoods')->name('jd_goods.query');
    $router->post('jdgoods/save', 'JdGoodsController@save')->name('jd_goods.save');
    $router->resource('jdgoods', 'JdGoodsController');
    // 添加一个测试路由，不使用中间件
    $router->get('jdgoods/test-query', function() {
        return '测试路由正常工作';
    })->name('jd_goods.test');
    // $router->resource('jdgoods', 'JdGoodsController');
    //充值
    $router->resource('recharge', 'RechargeController');
    //提现
    $router->resource('user-withdrawal', 'UserWithdrawalController');
    //推荐奖励
    $router->resource('promotion', 'PromotionController');
    $router->get('promotion/export', 'PromotionController@export')->name('promotion.export');
    //订单
    $router->resource('orders', 'OrdersController');
    
    //京东订单
    $router->resource('jd-orders', 'JdOrdersController');
    
    //全局配置
    $router->resource('global-config', 'GlobalConfigController');
    
    //动态配置管理
    $router->resource('dynamic-property', 'DynamicPropertyController');
    $router->post('dynamic-property/save', 'DynamicPropertyController@save');

    //用户佣金结算表
    $router->resource('user-commission-settlement', 'UserCommissionSettlementController');
 
    $router->post('user-commission-settlement/getUserStats', 'UserCommissionSettlementController@getUserStats');
});
