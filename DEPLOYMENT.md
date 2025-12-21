# 部署文档

**版本**：v1.0.0  
**最后更新**：2025-12-21

---

## 📋 目录

1. [环境要求](#环境要求)
2. [快速部署](#快速部署)
3. [详细部署步骤](#详细部署步骤)
4. [配置说明](#配置说明)
5. [常见问题](#常见问题)

---

## 环境要求

### 服务器要求

- **操作系统**：Linux / macOS / Windows
- **PHP版本**：>= 7.4.0
- **数据库**：MySQL >= 5.7 或 MariaDB >= 10.2
- **Web服务器**：Nginx / Apache / PHP内置服务器
- **扩展要求**：
  - PDO MySQL扩展
  - curl扩展
  - json扩展
  - mbstring扩展
  - openssl扩展

### 开发环境（可选）

- **Composer**：用于管理PHP依赖
- **Docker**：用于本地MySQL开发环境（可选）
- **Node.js**：用于前端资源构建（可选）

---

## 快速部署

### 1. 克隆项目

```bash
git clone <repository-url>
cd shuzishizhong
```

### 2. 安装依赖

```bash
cd web
composer install
```

### 3. 配置数据库

编辑 `web/.env` 文件：

```ini
[database]
type = mysql
hostname = 127.0.0.1
database = fastadmin
username = root
password = your_password
hostport = 3306
prefix = fa_
```

### 4. 初始化数据库

```bash
cd web
php think install
```

### 5. 创建项目表

```bash
php think install:project
```

### 6. 启动服务

**开发环境**：
```bash
php -S localhost:8000 -t public
```

**生产环境**：配置Nginx/Apache指向 `public` 目录

---

## 详细部署步骤

### 步骤1：准备服务器环境

#### 安装PHP和扩展

**Ubuntu/Debian**：
```bash
sudo apt-get update
sudo apt-get install php7.4 php7.4-fpm php7.4-mysql php7.4-curl php7.4-json php7.4-mbstring php7.4-openssl
```

**CentOS/RHEL**：
```bash
sudo yum install php74 php74-php-fpm php74-php-mysql php74-php-curl php74-php-json php74-php-mbstring php74-php-openssl
```

#### 安装Composer

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

#### 安装MySQL

**使用Docker（推荐）**：
```bash
docker-compose up -d
```

**或手动安装MySQL**：
```bash
# Ubuntu/Debian
sudo apt-get install mysql-server

# CentOS/RHEL
sudo yum install mysql-server
```

### 步骤2：部署代码

#### 上传代码到服务器

```bash
# 使用Git
git clone <repository-url>
cd shuzishizhong

# 或使用FTP/SFTP上传代码
```

#### 设置目录权限

```bash
cd web
chmod -R 755 runtime
chmod -R 755 public/uploads
```

### 步骤3：配置数据库

#### 创建数据库

```sql
CREATE DATABASE `fastadmin` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
```

#### 配置数据库连接

编辑 `web/.env` 文件：

```ini
[database]
type = mysql
hostname = 127.0.0.1
database = fastadmin
username = your_username
password = your_password
hostport = 3306
prefix = fa_
```

### 步骤4：安装和初始化

#### 安装Composer依赖

```bash
cd web
composer install --no-dev --optimize-autoloader
```

#### 运行FastAdmin安装

```bash
php think install
```

按照提示输入：
- 数据库信息
- 管理员账号和密码

#### 创建项目表结构

```bash
php think install:project
```

#### 同步币种数据（可选）

```bash
php think sync:coins
```

### 步骤5：配置Web服务器

#### Nginx配置示例

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/shuzishizhong/web/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\. {
        deny all;
    }
}
```

#### Apache配置

确保 `.htaccess` 文件在 `public` 目录下，并启用 `mod_rewrite`：

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### 步骤6：配置定时任务（可选）

如果需要定时同步数据，可以配置Cron：

```bash
# 编辑crontab
crontab -e

# 添加定时任务（每天凌晨2点同步币种数据）
0 2 * * * cd /path/to/shuzishizhong/web && php think sync:coins >> /var/log/sync_coins.log 2>&1
```

---

## 配置说明

### 环境配置文件

**位置**：`web/.env`

**主要配置项**：
- `app_debug`：调试模式（生产环境设为false）
- `database`：数据库配置
- `cache`：缓存配置

### 系统配置

通过后台管理系统配置：
- 数据刷新间隔
- 默认计价货币
- CoinGecko API密钥（可选）
- 时区设置

### 安全配置

1. **修改后台入口文件**：
   - 默认入口文件：`public/LUgeswcuTm.php`
   - 建议重命名为不易猜测的名称

2. **设置强密码**：
   - 管理员密码
   - 数据库密码
   - API密钥

3. **配置防火墙**：
   - 只开放必要端口（80, 443, 3306）
   - 限制数据库访问

---

## 常见问题

### Q1: 安装时提示数据库连接失败

**解决方案**：
1. 检查数据库服务是否启动
2. 检查 `web/.env` 中的数据库配置
3. 检查数据库用户权限
4. 检查防火墙设置

### Q2: 后台登录后显示404

**解决方案**：
1. 检查Web服务器配置
2. 检查 `public/.htaccess` 文件是否存在
3. 检查URL重写是否启用

### Q3: API接口返回500错误

**解决方案**：
1. 检查 `runtime` 目录权限
2. 查看 `runtime/log` 目录下的错误日志
3. 检查PHP错误日志

### Q4: 币种数据为空

**解决方案**：
1. 运行同步命令：`php think sync:coins`
2. 检查CoinGecko API连接
3. 检查网络连接

### Q5: 上传文件失败

**解决方案**：
1. 检查 `public/uploads` 目录权限
2. 检查PHP `upload_max_filesize` 配置
3. 检查磁盘空间

---

## 生产环境优化建议

### 1. 性能优化

- 启用OPcache
- 使用Redis缓存（替代文件缓存）
- 配置CDN加速静态资源
- 启用Gzip压缩

### 2. 安全优化

- 关闭调试模式（`app_debug = false`）
- 使用HTTPS
- 定期更新依赖包
- 配置防火墙规则
- 定期备份数据库

### 3. 监控和日志

- 配置日志轮转
- 监控服务器资源
- 监控API调用频率
- 设置告警机制

---

## 备份和恢复

### 数据库备份

```bash
mysqldump -u root -p fastadmin > backup_$(date +%Y%m%d).sql
```

### 代码备份

```bash
tar -czf code_backup_$(date +%Y%m%d).tar.gz web/
```

### 恢复

```bash
# 恢复数据库
mysql -u root -p fastadmin < backup_20251221.sql

# 恢复代码
tar -xzf code_backup_20251221.tar.gz
```

---

## 更新升级

### 更新代码

```bash
git pull origin master
cd web
composer install --no-dev --optimize-autoloader
```

### 更新数据库结构

```bash
php think install:project
```

### 清理缓存

```bash
rm -rf runtime/cache/*
rm -rf runtime/temp/*
```

---

**最后更新**：2025-12-21

