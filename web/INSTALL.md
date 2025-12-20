# FastAdmin 安装说明

## 技术栈

- **数据库**：MySQL（FastAdmin官方支持）
- **PHP版本**：>= 7.4.0
- **MySQL版本**：>= 5.7 或 MariaDB >= 10.2

## 快速安装

### 1. 确保MySQL服务运行

```bash
# macOS (Homebrew)
brew services start mysql

# 或手动启动
mysql.server start
```

### 2. 创建数据库

```bash
mysql -u root -p
CREATE DATABASE fastadmin CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
EXIT;
```

### 3. 配置数据库连接

编辑 `web/.env` 文件：

```ini
[database]
type = mysql
hostname = 127.0.0.1
database = fastadmin
username = root
password = 你的MySQL密码
hostport = 3306
prefix = fa_
```

### 4. 启动安装

**方式A：Web界面安装（推荐）**

```bash
cd web
php -S localhost:8000 -t public
```

然后访问：http://localhost:8000/install.php

**方式B：命令行安装**

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

## 安装后访问

- 后台地址：http://localhost:8000/admin.php
- 使用安装时设置的管理员账号登录

## 重新安装

如需重新安装，删除安装锁文件：

```bash
rm web/application/admin/command/Install/install.lock
```
