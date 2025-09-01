# 微信小程序手机号解密功能说明

## 功能概述

本功能实现了微信小程序手机号自动注册和登录，支持：
- 微信小程序加密手机号解密
- 新用户自动注册
- 已存在用户自动登录
- 邀请码处理
- JWT Token 生成

## 接口信息

### 接口地址
```
POST /api/autoRegisterAndLogin
```

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| detail.encryptedData | string | 是 | 微信加密的手机号数据 |
| detail.iv | string | 是 | 加密算法的初始向量 |
| detail.code | string | 是 | 微信授权码 |
| openid | string | 是 | 微信用户唯一标识 |
| invitationCode | string | 否 | 邀请码（可选） |

### 请求示例

```json
{
    "detail": {
        "errMsg": "getPhoneNumber:ok",
        "encryptedData": "eVrort8nzJdxugoTVkZ9vvS26RYFukhA4ZC0W4f8e7HaP7m6F4QvI1XqQeCJ9oHu0AxnJ4LB5CP+xJtItX2YXHg2T1\\/eoCSU\\/P0Zs0yq4W+0XSOg9FENKVQ5qZA6Yxdciy+5GuSagF060o9QcCHm1G9wHX9+KhEV4Qy6YZSuKpkW7lV5+rwfO+qd+MXm9GKGyvfzdEWXENzaqTJ+mTTeyA==",
        "iv": "qDk9Xq1vvk21ML62\\/Nir8g==",
        "code": "fc5b2dab88c97766f6351ae7924f5ba2524a34bb1fd475c0e26e285a537fe430"
    },
    "openid": "wx_lk13vip0hz9",
    "invitationCode": "1234"
}
```

## 响应格式

### 成功响应

#### 新用户注册成功
```json
{
    "status": "success",
    "message": "注册成功",
    "data": {
        "token": "1|abc123...",
        "token_type": "Bearer",
        "expires_in": 604800,
        "user": {
            "id": 10001,
            "username": "wx_1234_1234567890",
            "phone": "13800138000",
            "wechat_openid": "wx_lk13vip0hz9",
            "balance": 0
        },
        "is_new_user": true
    }
}
```

#### 已存在用户登录成功
```json
{
    "status": "success",
    "message": "登录成功",
    "data": {
        "token": "1|abc123...",
        "token_type": "Bearer",
        "expires_in": 604800,
        "user": {
            "id": 10001,
            "username": "wx_1234_1234567890",
            "phone": "13800138000",
            "wechat_openid": "wx_lk13vip0hz9",
            "balance": 100
        },
        "is_new_user": false
    }
}
```

### 错误响应

```json
{
    "status": "error",
    "message": "错误描述"
}
```

## 实现原理

### 1. 手机号解密流程
1. 接收微信小程序传来的 `encryptedData`、`iv`、`code`
2. 通过 `code` 调用微信接口获取 `session_key`
3. 使用 `session_key` 和 `iv` 解密 `encryptedData` 获取手机号

### 2. 用户处理流程
1. 检查用户是否已存在（通过 `openid` 或手机号）
2. 如果用户存在：更新 `openid`（如果需要），生成 token 并返回
3. 如果用户不存在：创建新用户，处理邀请码，生成 token 并返回

### 3. 邀请码处理
- 如果提供了邀请码，会创建推广记录
- 给推荐人发放邀请奖励
- 更新用户余额日志

## 配置要求

### 1. 微信配置
确保 `config/wechat.php` 文件配置正确：
```php
return [
    'appid' => 'your_app_id',
    'appsecret' => 'your_app_secret',
    // ... 其他配置
];
```

### 2. 数据库字段
确保 `users` 表包含以下字段：
- `wechat_openid` - 微信 OpenID
- `phone` - 手机号
- `username` - 用户名
- `password` - 密码（自动生成）

### 3. 依赖包
- Laravel Sanctum（用于 Token 认证）
- 确保 `User` 模型使用了 `HasApiTokens` trait

## 使用示例

### 微信小程序端
```javascript
// 获取手机号
wx.getPhoneNumber({
    success: (res) => {
        // 发送到后端
        wx.request({
            url: 'https://your-domain.com/api/autoRegisterAndLogin',
            method: 'POST',
            data: {
                detail: res,
                openid: 'user_openid',
                invitationCode: '1234' // 可选
            },
            success: (response) => {
                if (response.data.status === 'success') {
                    // 保存 token
                    wx.setStorageSync('token', response.data.data.token);
                    // 保存用户信息
                    wx.setStorageSync('userInfo', response.data.data.user);
                }
            }
        });
    }
});
```

### 后端调用
```php
// 使用生成的 token 调用需要认证的接口
$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . $token,
    'Accept' => 'application/json'
])->get('https://your-domain.com/api/userinfo');
```

## 注意事项

1. **Code 时效性**：微信的 `code` 只能使用一次，且有效期为 5 分钟，过期后需要重新获取
2. **安全性**：`session_key` 有时效性，需要及时使用
3. **错误处理**：建议在前端做好错误提示，特别是处理 code 过期的情况
4. **Token 管理**：Token 有效期为 7 天，需要及时刷新
5. **日志记录**：系统会自动记录关键操作的日志
6. **事务处理**：用户创建过程使用数据库事务，确保数据一致性
7. **测试数据**：不要使用过期的测试数据，每次测试都需要获取新的 code

## 故障排除

### 常见问题

1. **获取微信会话密钥失败**
   - **错误码 40029**: `code` 无效（已过期或已使用，请重新获取）
   - **错误码 45011**: 频率限制，每个用户每分钟100次
   - **错误码 40226**: 高风险用户，小程序后台拦截
   - **错误码 -1**: 系统繁忙，请稍后再试
   - **错误码 40013**: AppID 无效
   - **错误码 40014**: AppSecret 无效

2. **手机号解密失败**
   - 检查微信配置是否正确
   - 确认 `code` 是否有效
   - 检查 `encryptedData` 和 `iv` 格式
   - 确认 `session_key` 是否正确获取

3. **用户创建失败**
   - 检查数据库连接
   - 确认 User 模型字段配置
   - 查看错误日志

4. **Token 生成失败**
   - 确认 Laravel Sanctum 配置
   - 检查 User 模型是否使用了 `HasApiTokens`

### 调试建议

1. 查看 `storage/logs/laravel.log` 文件
2. 使用 `save_log()` 函数记录关键数据
3. 检查微信 API 返回的错误信息
4. 验证数据库字段和约束

## 更新日志

- 2025-01-XX：初始版本，实现基本的手机号解密和用户注册/登录功能
- 支持邀请码处理
- 支持自动 Token 生成
- 完整的错误处理和日志记录
