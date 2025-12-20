# FastAdmin MySQL 安装指南

## 前置要求

1. **MySQL服务已启动**
   ```bash
   # macOS 启动MySQL（如果使用Homebrew安装）
   brew services start mysql
   
   # 或手动启动
   mysql.server start
   ```

2. **创建数据库**
   ```bash
   mysql -u root -p
   CREATE DATABASE fastadmin CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
   EXIT;
   ```

## 安装方式

### 方式1：Web界面安装（推荐）

1. 启动开发服务器：
   ```bash
   cd web
   php -S localhost:8000 -t public
   ```

2. 访问安装页面：http://localhost:8000/install.php

3. 填写数据库信息：
   - **数据库地址**：127.0.0.1
   - **数据库名**：fastadmin
   - **用户名**：root
   - **密码**：你的MySQL密码
   - **端口**：3306
   - **表前缀**：fa_

4. 填写管理员信息：
   - **管理员用户名**：admin（或自定义）
   - **管理员密码**：设置一个强密码
   - **管理员Email**：admin@admin.com
   - **网站名称**：数据时钟管理系统

5. 点击"安装"完成安装

### 方式2：命令行安装

```bash
cd web
php think install \
  --hostname=127.0.0.1 \
  --hostport=3306 \
  --database=fastadmin \
  --username=root \
  --password=你的MySQL密码 \
  --prefix=fa_
```

## 安装后

1. 访问后台：http://localhost:8000/admin.php
2. 使用安装时设置的管理员账号登录
3. 登录后立即修改密码

## 注意事项

- 安装完成后，`install.php` 文件会被锁定
- 如需重新安装，删除 `application/admin/command/Install/install.lock` 文件
- 确保MySQL服务正常运行

