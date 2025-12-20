# FastAdmin 安装成功！

## 安装信息

- **安装时间**：2025-12-20
- **数据库**：MySQL (Docker)
- **数据库名**：fastadmin
- **表前缀**：fa_

## 登录信息

- **后台地址**：http://localhost:8000/admin.php
- **用户名**：admin
- **密码**：qexF3dkAER

⚠️ **重要**：请登录后立即修改默认密码！

## Docker MySQL 服务

### 启动服务
```bash
cd /Users/zhangandy/Documents/work/code/party2025/feixiaohao/shuzishizhong
docker-compose up -d mysql
```

### 停止服务
```bash
docker-compose stop mysql
```

### 查看日志
```bash
docker-compose logs -f mysql
```

### 数据库连接信息
- **主机**：127.0.0.1
- **端口**：3306
- **用户名**：root
- **密码**：root123456
- **数据库**：fastadmin

## 下一步

1. ✅ FastAdmin环境搭建完成
2. ✅ MySQL数据库初始化完成
3. ⏳ 创建项目所需的表结构（batches、coins等）
4. ⏳ 开始开发API接口

## 启动开发服务器

```bash
cd web
php -S localhost:8000 -t public
```

然后访问：http://localhost:8000/admin.php

