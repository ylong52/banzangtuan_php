# Vue 3 + PHP 交互框架

一个完整的 Vue 3.0 + Tailwind CSS 前端框架，用于与 PHP 后端交互。

## 核心特性

1. **项目结构** - 清晰的模块化目录组织
2. **API 配置** - 基于原生 Fetch 的请求封装，包含：
   - 请求/响应拦截
   - Token 自动管理
   - 统一错误处理
   - 超时控制

3. **API 模块** - 按业务功能组织的 API 方法
4. **组合式函数** - 可复用的 `useApi` Hook
5. **组件示例** - 完整的用户列表组件
6. **状态管理** - Pinia 状态管理示例
7. **环境配置** - 开发/生产环境分离

## 主要优势

- ✅ 不依赖第三方库（使用原生 Fetch）
- ✅ 不使用 TypeScript，方便维护
- ✅ 自动 Token 管理和刷新
- ✅ 统一的错误处理机制
- ✅ 可复用的组合式函数
- ✅ 清晰的代码组织结构

## 项目结构

```
vue3-php-app/
├── src/
│   ├── api/
│   │   ├── index.js           # API 配置
│   │   ├── request.js         # Fetch 封装
│   │   └── modules/           # API 模块
│   │       ├── user.js
│   │       └── product.js
│   ├── components/
│   │   ├── common/            # 通用组件
│   │   └── business/          # 业务组件
│   ├── composables/           # 组合式函数
│   │   └── useApi.js
│   ├── views/                 # 页面视图
│   ├── router/                # 路由配置
│   ├── store/                 # Pinia 状态管理
│   ├── utils/                 # 工具函数
│   ├── App.vue
│   └── main.js
├── public/
├── package.json
├── vite.config.js
└── tailwind.config.js
```

## 安装

```bash
npm install
```

## 开发

```bash
npm run dev
```

## 构建

```bash
npm run build
```

## 预览

```bash
npm run preview
```

## 环境变量

项目使用 Vite 的环境变量功能，支持通过 `.env` 文件配置不同环境的 API 域名。

### 创建环境变量文件

在项目根目录创建以下文件：

**`.env`** - 开发环境配置（默认）
```env
# API 基础域名
VITE_API_BASE_URL=/api

# 如果需要直接连接后端服务器，取消注释并修改为实际域名
# VITE_API_BASE_URL=http://localhost:8000/api
```

**`.env.production`** - 生产环境配置
```env
# 生产环境配置
# 请根据实际部署域名修改
VITE_API_BASE_URL=https://your-domain.com/api
```

### 环境变量说明

- `VITE_API_BASE_URL`: API 请求的基础 URL
  - 开发环境：默认使用 `/api`（通过 Vite 代理转发）
  - 生产环境：设置为完整的后端 API 地址

### 使用环境变量

在代码中通过 `import.meta.env.VITE_API_BASE_URL` 访问环境变量：

```javascript
// src/api/request.js 中已自动使用
const apiClient = new ApiClient(import.meta.env.VITE_API_BASE_URL)
```

> **注意**：只有以 `VITE_` 开头的变量才会暴露给客户端代码。

## 使用示例

### API 调用

```javascript
import { userApi } from '@/api/modules/user'

// 获取用户列表
const users = await userApi.getList({ page: 1, limit: 10 })

// 创建用户
await userApi.create({ name: '张三', email: 'zhangsan@example.com' })
```

### 使用组合式函数

```javascript
import { useApi } from '@/composables/useApi'
import { userApi } from '@/api/modules/user'

const { loading, error, data, execute } = useApi(userApi.getList)

// 执行请求
await execute({ page: 1, limit: 10 })
```

### 使用状态管理

```javascript
import { useUserStore } from '@/store/user'

const userStore = useUserStore()

// 登录
await userStore.login({ username: 'admin', password: '123456' })

// 获取用户信息
await userStore.fetchUserInfo()

// 退出登录
userStore.logout()
```

## 技术栈

- Vue 3.4.0
- Vue Router 4.2.5
- Pinia 2.1.7
- Tailwind CSS 3.4.0
- Vite 5.0.0

## 许可证

MIT

