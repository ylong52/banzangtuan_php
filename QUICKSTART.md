# 快速开始指南

## 1. 安装依赖

```bash
npm install
```

## 2. 配置环境变量

创建 `.env.development` 文件（开发环境）：

```env
VITE_API_BASE_URL=http://localhost:8000/api
```

创建 `.env.production` 文件（生产环境）：

```env
VITE_API_BASE_URL=https://api.yourapp.com/api
```

## 3. 启动开发服务器

```bash
npm run dev
```

访问 http://localhost:3000

## 4. 项目结构说明

### API 层 (`src/api/`)

- `request.js` - API 请求封装类，处理 Token、错误、超时等
- `modules/user.js` - 用户相关 API
- `modules/product.js` - 产品相关 API

### 组合式函数 (`src/composables/`)

- `useApi.js` - 封装 API 调用的组合式函数，提供 loading、error、data 状态

### 状态管理 (`src/store/`)

- `user.js` - 用户状态管理（Pinia store）
- `index.js` - Pinia 实例

### 路由 (`src/router/`)

- `index.js` - 路由配置和守卫

### 组件 (`src/components/`)

- `common/` - 通用组件（Loading、ErrorMessage 等）
- `business/` - 业务组件（UserList 等）

### 视图 (`src/views/`)

- `Home.vue` - 首页
- `Login.vue` - 登录页
- `UserList.vue` - 用户列表页

## 5. 使用示例

### 调用 API

```javascript
import { userApi } from '@/api/modules/user'

// 获取用户列表
const users = await userApi.getList({ page: 1, limit: 10 })
```

### 使用组合式函数

```javascript
import { useApi } from '@/composables/useApi'
import { userApi } from '@/api/modules/user'

const { loading, error, data, execute } = useApi(userApi.getList)

// 在组件中
onMounted(async () => {
  await execute({ page: 1, limit: 10 })
})
```

### 使用状态管理

```javascript
import { useUserStore } from '@/store/user'

const userStore = useUserStore()

// 登录
await userStore.login({ username: 'admin', password: '123456' })

// 访问状态
console.log(userStore.userName)
console.log(userStore.isLoggedIn)
```

## 6. 添加新的 API 模块

1. 在 `src/api/modules/` 创建新文件，例如 `order.js`：

```javascript
import apiClient from '../request'

export const orderApi = {
  getList(params) {
    return apiClient.get('/order/list', params)
  },
  // ... 其他方法
}
```

2. 在 `src/api/index.js` 中导出：

```javascript
export { orderApi } from './modules/order'
```

## 7. 构建生产版本

```bash
npm run build
```

构建产物在 `dist/` 目录。

## 注意事项

- Token 会自动从 localStorage 读取并添加到请求头
- 401 错误会自动清除 Token 并跳转到登录页
- API 响应格式应为：`{ code: 200, data: {...}, message: '...' }`
- 所有 API 请求都有 10 秒超时限制

