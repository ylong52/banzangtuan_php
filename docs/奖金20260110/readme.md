# 京东联盟优惠券查询系统接口分析

## 系统概述

这是一个基于京东联盟开放平台的优惠券查询系统，通过调用京东联盟的多个API接口，实现商品搜索、优惠券查询、礼金券生成和推广链接生成等功能。

## 核心文件结构

### 1. JdUnionClient.php - 京东联盟API客户端

#### 功能描述
- 封装京东联盟API的通用调用逻辑
- 处理请求签名、参数验证、HTTP请求等基础功能

#### 核心方法

**构造函数 `__construct($config)`**
- 初始化客户端配置
- 配置包含：appKey、appSecret、accessToken、serverUrl、method、format、v、timestamp

**参数验证 `validateSystemParams($params)`**
- 验证必传系统参数：method、app_key、timestamp、format、v、sign
- 验证时间戳格式（yyyy-MM-dd HH:mm:ss）
- 可选验证access_token（按需开启）

**签名生成 `generateSign($params)`**
- 按参数名首字母升序排序
- 拼接参数：appSecret + key1 + value1 + key2 + value2 + ... + appSecret
- MD5加密后转大写

**请求执行 `execute($bizParams)`**
- 构建系统参数 + 业务参数
- 生成签名并验证参数
- 发送HTTP请求到京东API网关
- 处理响应数据（JSON解析）

### 2. jdapi.php - 业务逻辑处理

#### 全局配置
```php
$appKey = '77ed81195127ae87148f5bebce9d28fb';
$appSecret = '69294b9488cd40b8a64ade8be19e52bb';
```

#### 核心函数

**`callJdApi($appKey, $appSecret, $method, $bizParams, $accessToken = '')`**
- 通用京东API调用函数
- 返回统一格式：`['success' => bool, 'message' => string, 'data' => array]`

**`callPromotionBySubUnionIdApi($appKey, $appSecret, $bizParams, $accessToken = '')`**
- 调用推广链接生成接口
- 提取shortURL字段用于最终跳转

#### 调用的京东联盟API接口

##### 1. jd.union.open.goods.query - 商品搜索接口
**功能**：根据关键词搜索京东商品
**请求参数**：
```php
$bizParams = [
    'goodsReqDTO' => [
        'keyword' => $keyword,  // 搜索关键词（必须包含"京东"和URL）
        'pageIndex' => 1,       // 页码
        'pageSize' => 2         // 每页条数
    ]
];
```
**返回数据提取**：
- `skuName`：商品名称
- `comments`：评论数
- `imageInfo.imageList[0].url`：商品图片
- `materialUrl`：商品链接
- `priceInfo.price`：商品价格
- `couponInfo.couponList[0].link`：优惠券链接
- `commissionInfo.couponCommission`：优惠券佣金
- `itemId`：商品ID

##### 2. jd.union.open.coupon.gift.get - 礼金券生成接口
**功能**：为商品生成礼金券
**请求参数**：
```php
$promotionBizParams = [
    'couponReq' => [
        'skuMaterialId' => $goodsInfo['itemId'],     // 商品ID
        'discount' => $goodsInfo['discount'],        // 礼金金额
        'amount' => $goodsInfo['amount'],            // 礼金数量
        'receiveStartTime' => date('Y-m-d 00', strtotime('today')),     // 领取开始时间
        'receiveEndTime' => date('Y-m-d 00', strtotime('+1 day')),      // 领取结束时间
        'effectiveDays' => 1,                        // 有效天数
        'expireType' => 1                            // 到期类型
    ]
];
```
**返回数据**：`giftCouponKey` - 礼金券密钥

##### 3. jd.union.open.promotion.bysubunionid.get - 推广链接生成接口
**功能**：生成带有子联盟ID的推广链接
**请求参数**：
```php
$promotionBizParams = [
    'promotionCodeReq' => [
        'materialId' => $goodsInfo['itemId'],         // 物料ID
        'giftCouponKey' => $goodsInfo['giftCouponKey'], // 礼金券密钥
        'couponUrl' => $goodsInfo['couponlink'],     // 优惠券链接
        'sceneld' => 1                               // 场景ID
    ]
];
```
**返回数据**：`shortURL` - 短链接，用于最终用户跳转

#### 佣金计算逻辑

**`calculateAdjustedY($x)` 函数**
- 计算礼金券数量的算法
- 输入：礼金金额 x
- 输出：礼金券数量 y

**计算规则**：
1. x ≤ 0：返回 0
2. x < 1：返回 0
3. x > 50：返回 1（上限50元）
4. 正常情况：y = ceil(20/x)，确保 x*y ∈ [20,50]

### 3. index.html - 前端界面

#### 页面结构
- Logo展示区域
- 搜索输入框（京东商品链接）
- 优惠券展示卡片
- 底部信息栏

#### 前端功能
- 关键词验证：必须包含"京东"和有效URL
- AJAX调用后端API
- 动态更新优惠券信息
- 复制券链接功能
- 跳转到shortURL领取优惠券

#### 响应式设计
- 支持移动端适配
- 搜索框在小屏幕上垂直排列
- 优惠券卡片网格布局

## API调用流程

1. **用户输入**：京东商品链接
2. **参数验证**：检查格式（包含"京东"和URL）
3. **商品搜索**：调用 jd.union.open.goods.query 获取商品信息
4. **佣金计算**：根据优惠券佣金计算礼金金额和数量
5. **礼金券生成**：调用 jd.union.open.coupon.gift.get 生成礼金券
6. **推广链接生成**：调用 jd.union.open.promotion.bysubunionid.get 生成最终跳转链接
7. **结果展示**：前端展示商品信息和领取按钮

## 数据流转

```
用户输入 → 关键词验证 → 商品搜索API → 提取商品信息
       ↓
   佣金计算 → 礼金券生成API → 获取giftCouponKey
       ↓
推广链接生成API → 获取shortURL → 前端展示 → 用户领取
```

## 错误处理

- HTTP请求失败：返回状态码和响应信息
- API业务错误：返回京东接口的错误信息
- 参数格式错误：正则验证关键词格式
- JSON解析失败：异常捕获和错误返回

## 安全考虑

- AppKey和AppSecret硬编码（生产环境建议配置化）
- HTTPS请求（生产环境必须开启SSL验证）
- 输入验证防止恶意请求
- 错误信息过滤避免敏感信息泄露

## 技术栈

- **后端**：PHP原生（无框架）
- **前端**：HTML5 + CSS3 + JavaScript + jQuery
- **HTTP客户端**：cURL
- **数据格式**：JSON
- **API协议**：京东联盟开放平台API

## 部署要求

- PHP 5.6+
- cURL扩展
- HTTPS证书（生产环境）
- 京东联盟开发者账号及相应权限

## 扩展建议

1. **缓存机制**：添加Redis缓存减少API调用
2. **日志系统**：记录API调用和错误信息
3. **配置管理**：将AppKey等敏感信息移至配置文件
4. **数据库存储**：保存查询历史和统计数据
5. **用户系统**：添加用户登录和个人优惠券管理
6. **多线程处理**：优化并发请求处理能力
