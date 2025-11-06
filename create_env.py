#!/usr/bin/env python
# -*- coding: utf-8 -*-

env_content = """# API 基础域名
VITE_API_BASE_URL=/api

# 开发环境 API 域名（可选，如果后端不在本地）
# VITE_API_BASE_URL=http://localhost:8000/api

# 生产环境域名（在 .env.production 中配置）
# VITE_API_BASE_URL=https://your-domain.com/api
"""

env_example_content = """# API 基础域名
# 开发环境默认使用代理，设置为 /api
VITE_API_BASE_URL=/api

# 如果需要直接连接后端服务器，取消注释并修改为实际域名
# VITE_API_BASE_URL=http://localhost:8000/api

# 生产环境域名
# VITE_API_BASE_URL=https://your-domain.com/api
"""

env_production_content = """# 生产环境配置
# 请根据实际部署域名修改
VITE_API_BASE_URL=https://your-domain.com/api
"""

try:
    with open('.env', 'w', encoding='utf-8') as f:
        f.write(env_content)
    print('✓ .env 文件已创建')
    
    with open('.env.example', 'w', encoding='utf-8') as f:
        f.write(env_example_content)
    print('✓ .env.example 文件已创建')
    
    with open('.env.production', 'w', encoding='utf-8') as f:
        f.write(env_production_content)
    print('✓ .env.production 文件已创建')
    
    print('\n环境变量配置完成！')
    print('请在 .env 文件中修改 VITE_API_BASE_URL 为你的实际 API 域名')
except Exception as e:
    print(f'创建文件时出错: {e}')
    exit(1)

