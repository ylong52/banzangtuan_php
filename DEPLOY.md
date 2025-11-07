# 部署说明

## 问题诊断：空白页面

如果访问 `https://jq2025.cn/lottery/index.html` 显示空白页面，请按以下步骤排查：

### 1. 检查文件结构

确保服务器上的文件结构如下：
```
/var/www/html/lottery/  (或你的网站根目录/lottery/)
├── index.html
└── assets/
    ├── index-*.js
    ├── index-*.css
    └── ...
```

### 2. Nginx 配置

**关键配置点：**

```nginx
location /lottery/ {
    alias /var/www/html/lottery/;  # 指向 dist 目录的内容
    
    # 重要：必须配置 try_files，让所有路由都回退到 index.html
    try_files $uri $uri/ /lottery/index.html;
    
    # 确保正确设置 MIME 类型
    location ~* \.(js|mjs)$ {
        add_header Content-Type application/javascript;
    }
    
    location ~* \.(css)$ {
        add_header Content-Type text/css;
    }
}
```

### 3. 常见问题排查

#### 问题 1: JavaScript 文件加载失败
- 打开浏览器开发者工具 (F12)
- 查看 Network 标签，检查 JS/CSS 文件是否返回 404
- 如果返回 404，检查 nginx 配置中的 `alias` 路径是否正确

#### 问题 2: 路由无法访问
- 确保 nginx 配置中有 `try_files $uri $uri/ /lottery/index.html;`
- 这确保所有 `/lottery/` 下的路由都返回 `index.html`

#### 问题 3: 资源路径错误
- 检查 `dist/index.html` 中的资源路径是否以 `/lottery/` 开头
- 如果路径不正确，重新运行 `npm run build`

### 4. 完整 Nginx 配置示例

```nginx
server {
    listen 80;
    server_name jq2025.cn;
    
    root /var/www/html;
    index index.html index.php;
    
    # Vue 应用 - /lottery/ 路径
    location /lottery/ {
        alias /var/www/html/lottery/;
        
        # 关键：所有路由都回退到 index.html
        try_files $uri $uri/ /lottery/index.html;
        
        # 静态资源缓存
        location ~* \.(js|mjs|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
            expires 1y;
            add_header Cache-Control "public, immutable";
        }
        
        # 确保 JS 文件正确识别
        location ~* \.(js|mjs)$ {
            add_header Content-Type "application/javascript; charset=utf-8";
        }
    }
    
    # API 代理
    location /api/ {
        proxy_pass http://localhost:8000/api/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
    
    # PHP 处理
    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 5. 部署步骤

1. **构建项目**
   ```bash
   npm run build
   ```

2. **上传文件**
   - 将 `dist` 目录下的所有文件上传到服务器的 `/var/www/html/lottery/` 目录
   - 确保文件权限正确（通常 755 目录，644 文件）

3. **配置 Nginx**
   - 按照上面的配置示例修改 nginx 配置
   - 测试配置：`nginx -t`
   - 重载配置：`nginx -s reload` 或 `systemctl reload nginx`

4. **验证部署**
   - 访问 `https://jq2025.cn/lottery/`
   - 打开浏览器开发者工具，检查：
     - Console 是否有错误
     - Network 标签中资源是否都加载成功（状态码 200）
     - 检查 JS 文件的 Content-Type 是否为 `application/javascript`

### 6. 调试技巧

如果仍然空白：

1. **检查 index.html 内容**
   ```bash
   curl https://jq2025.cn/lottery/index.html
   ```
   应该能看到 HTML 内容

2. **检查 JS 文件**
   ```bash
   curl -I https://jq2025.cn/lottery/assets/index-*.js
   ```
   应该返回 200 状态码，Content-Type 为 `application/javascript`

3. **检查浏览器控制台**
   - 打开开发者工具 (F12)
   - 查看 Console 标签是否有错误
   - 查看 Network 标签，检查哪些资源加载失败

4. **检查路由**
   - 直接访问 `https://jq2025.cn/lottery/`（不带 index.html）
   - 应该也能正常显示

### 7. 常见错误

- **404 错误**: 检查 nginx `alias` 路径和文件是否存在
- **403 错误**: 检查文件权限
- **空白页面**: 检查 `try_files` 配置和 JS 文件是否正确加载
- **路由不工作**: 确保 `try_files` 包含 `/lottery/index.html`

