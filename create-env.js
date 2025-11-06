import fs from 'fs';

const envContent = `# API 基础域名
VITE_API_BASE_URL=/api

# 开发环境 API 域名（可选，如果后端不在本地）
# VITE_API_BASE_URL=http://localhost:8000/api

# 生产环境域名（在 .env.production 中配置）
# VITE_API_BASE_URL=https://your-domain.com/api
`;

const envExampleContent = `# API 基础域名
# 开发环境默认使用代理，设置为 /api
VITE_API_BASE_URL=/api

# 如果需要直接连接后端服务器，取消注释并修改为实际域名
# VITE_API_BASE_URL=http://localhost:8000/api

# 生产环境域名
# VITE_API_BASE_URL=https://your-domain.com/api
`;

const envProductionContent = `# 生产环境配置
# 请根据实际部署域名修改
VITE_API_BASE_URL=https://your-domain.com/api
`;

try {
  fs.writeFileSync('.env', envContent, 'utf8');
  console.log('✓ .env 文件已创建');
  
  fs.writeFileSync('.env.example', envExampleContent, 'utf8');
  console.log('✓ .env.example 文件已创建');
  
  fs.writeFileSync('.env.production', envProductionContent, 'utf8');
  console.log('✓ .env.production 文件已创建');
  
  console.log('\n环境变量配置完成！');
  console.log('请在 .env 文件中修改 VITE_API_BASE_URL 为你的实际 API 域名');
} catch (error) {
  console.error('创建文件时出错:', error);
  process.exit(1);
}

