##项目核心组件说明

### 1. 支付与第三方平台集成组件

alipaysdk/easysdk（^2.0）
支付宝开放平台官方 SDK 的简化封装，提供统一的 API 接口，支持支付宝各类支付场景（如 APP 支付、扫码支付、转账等），同时集成了身份验证、订单查询、退款处理等功能。v2.0 版本优化了接口调用方式，简化了签名验证流程，适配支付宝最新的 API 规范，适合需要快速接入支付宝生态的电商、服务类应用。
wechatpay/wechatpay（^1.4）
微信支付官方 SDK，支持微信内支付（JSAPI）、APP 支付、H5 支付等主流场景，提供证书管理、签名生成、回调验证等核心功能。v1.4 版本强化了异常处理机制，兼容微信支付 API 的最新安全规范，适用于需接入微信支付的电商、社交类应用，确保支付流程的安全性与合规性。
overtrue/laravel-wechat（^5.0）
基于 Laravel 框架的微信生态集成工具，封装了微信公众号、小程序、企业微信等平台的接口，支持消息交互、用户管理、模板消息、素材管理等功能。v5.0 版本适配 Laravel 8+ 特性，提供更简洁的配置方式，适合需要在 Laravel 项目中快速实现微信生态功能（如公众号自动回复、小程序登录）的场景。

### 2. 后端框架与核心工具

laravel/framework（^8.75）
项目基础框架，提供路由管理、ORM 数据库操作、中间件、依赖注入等核心功能。
laravel/sanctum（^2.11）
Laravel 官方的轻量级身份验证工具，支持 API 令牌认证、SPA（单页应用）会话管理，无需复杂配置即可实现用户身份验证。v2.11 版本优化了令牌刷新机制，适合前后端分离项目或移动端 API 的身份验证场景，保障接口访问的安全性。
laravel/tinker（^2.5）
Laravel 命令行交互工具，允许在终端中直接执行 PHP 代码、操作数据库模型，便于开发过程中的快速调试与数据查询，提升开发效率。

#### 3. 管理后台与性能优化

encore/laravel-admin（^1.8）
基于 Laravel 的后台管理系统快速构建工具，提供可视化的后台界面生成、数据表格、表单组件、权限管理等功能。v1.8 版本兼容 Laravel 8，支持自定义主题与扩展插件，适合快速开发内部管理系统（如订单管理、用户后台），减少重复开发工作。
swooletw/laravel-swoole（^2.13）
Laravel 与 Swoole 的集成扩展，将传统的 PHP-FPM 运行模式替换为 Swoole 的常驻内存模式，支持 HTTP 服务、WebSocket、任务队列等功能。v2.13 版本显著提升请求处理性能（尤其高并发场景），降低内存占用，适合需要优化接口响应速度的项目（如实时通知、高频 API 调用）。

## 虚拟卷对接api

卡易信前台开放API：https://dy2auzvmkt.apifox.cn 密码kayixin

## 入帐支付对接(微信和支付宝)：

https://mer.feixpay.cn/#/merchant/apps

## 出账

> > 官方支付宝的出账
> > 1,微信的商家对个人奖励提现 2,微信支付个人入帐‘提现’确认

## 后台访问地址

域名：http://域名/admin

## 前端UI

<img src="./docs/images/02/a01 (1).png" />
<img src="./docs/images/02/a01 (2).png" />
<img src="./docs/images/02/a01 (3).png" />

## 后端UI

<img src="./docs/images/admin (1).png" />
<img src="./docs/images/admin (2).png" />
<img src="./docs/images/admin (3).png" />
<img src="./docs/images/admin (4).png" />
<img src="./docs/images/admin (5).png" />
<img src="./docs/images/admin (6).png" />
<img src="./docs/images/admin (7).png" />
<img src="./docs/images/admin (8).png" />
<img src="./docs/images/admin (9).png" />
<img src="./docs/images/admin (10).png" />
<img src="./docs/images/admin (11).png" />
<img src="./docs/images/admin (12).png" />



## 说明：

1，后台加了参数配置说明
2，后台设计了支付配置说明

