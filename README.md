# 数据时钟项目

## 项目简介

数据时钟项目是在普通时钟硬件上显示加密货币行情数据的智能设备。通过对接CoinGecko API获取实时加密货币行情，为时钟设备提供数据支持。

## 技术栈

- **后端框架**：FastAdmin (基于ThinkPHP 5.0)
- **数据库**：MySQL >= 5.7
- **缓存**：文件缓存
- **外部依赖**：CoinGecko API（加密货币数据源）

## 项目结构

```
shuzishizhong/
├── dev-docs/              # 开发文档
│   ├── PRD文档.md         # 产品需求文档
│   ├── 编码规范.md        # 编码规范
│   └── 原始提示词.md
├── database/              # 数据库相关
│   └── backup/            # 数据库备份
├── docker/                # Docker配置
│   └── mysql/             # MySQL初始化脚本
├── docs/                  # 文档归档
│   └── archive/           # 测试文档、开发文档归档
├── web/                   # FastAdmin项目目录
│   ├── application/       # 应用目录
│   │   ├── admin/         # 后台管理
│   │   ├── api/           # API接口
│   │   └── common/        # 公共模块
│   ├── public/            # 公共访问目录
│   ├── .env               # 环境配置文件
│   └── ...
├── DEPLOYMENT.md          # 部署文档
├── API_DOCUMENTATION.md   # API技术文档
├── API_USER_GUIDE.md      # API用户文档
└── README.md              # 本文件
```

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

## 快速开始

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

### 3. 配置环境变量

创建并编辑 `web/.env` 文件：

```ini
[app]
app_debug = false
app_trace = false

[database]
type = mysql
hostname = 127.0.0.1
database = fastadmin
username = root
password = your_password
hostport = 3306
prefix = fa_
charset = utf8mb4

[cache]
type = file
path = runtime/cache/
prefix = ''
expire = 0
```

### 4. 配置数据库

#### 使用Docker MySQL（推荐开发环境）

```bash
# 启动MySQL容器
docker-compose up -d

# 验证MySQL运行状态
docker ps | grep mysql
```

#### 手动安装MySQL

```bash
# Ubuntu/Debian
sudo apt-get install mysql-server

# CentOS/RHEL
sudo yum install mysql-server
```

创建数据库：

```sql
CREATE DATABASE `fastadmin` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
```

### 5. 初始化数据库

```bash
cd web

# 运行FastAdmin安装
php think install

# 创建项目表结构
php think install:project
```

### 6. 同步币种数据（可选）

```bash
php think sync:coins
```

### 7. 启动服务

**开发环境**：
```bash
cd web
php -S localhost:8000 -t public
```

**生产环境**：配置Nginx/Apache指向 `web/public` 目录

访问地址：
- 后台管理：`http://localhost:8000/LUgeswcuTm.php`
- API接口：`http://localhost:8000/index.php/api`

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

### 步骤3：配置环境变量

创建 `web/.env` 文件并配置：

```ini
[database]
type = mysql
hostname = 127.0.0.1
database = fastadmin
username = your_username
password = your_password
hostport = 3306
prefix = fa_
charset = utf8mb4

[app]
app_debug = false  # 生产环境设为false
```

### 步骤4：安装和初始化

```bash
cd web

# 安装Composer依赖
composer install --no-dev --optimize-autoloader

# 运行FastAdmin安装
php think install

# 创建项目表结构
php think install:project

# 同步币种数据（可选）
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

## 数据库备份和恢复

### 数据库备份

#### 使用Docker MySQL容器备份

```bash
docker exec mysql_dev mysqldump -uroot -proot123456 \
  --default-character-set=utf8mb4 \
  --single-transaction \
  --routines \
  --triggers \
  fastadmin > database/backup/fastadmin_$(date +%Y%m%d_%H%M%S).sql
```

#### 使用MySQL客户端备份

```bash
mysqldump -u root -p \
  --default-character-set=utf8mb4 \
  --single-transaction \
  --routines \
  --triggers \
  fastadmin > backup_$(date +%Y%m%d_%H%M%S).sql
```

### 数据库恢复

#### 使用Docker MySQL容器恢复

```bash
# 1. 创建数据库
docker exec mysql_dev mysql -uroot -proot123456 -e \
  "CREATE DATABASE IF NOT EXISTS fastadmin CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"

# 2. 导入数据
docker exec -i mysql_dev mysql -uroot -proot123456 fastadmin < \
  database/backup/fastadmin_YYYYMMDD_HHMMSS.sql
```

#### 使用MySQL客户端恢复

```bash
# 1. 创建数据库
mysql -u root -p -e \
  "CREATE DATABASE IF NOT EXISTS fastadmin CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"

# 2. 导入数据
mysql -u root -p fastadmin < database/backup/fastadmin_YYYYMMDD_HHMMSS.sql
```

### 数据库迁移到新服务器

1. **在原服务器导出数据库**：
   ```bash
   mysqldump -u root -p \
     --default-character-set=utf8mb4 \
     --single-transaction \
     --routines \
     --triggers \
     fastadmin > fastadmin_backup.sql
   ```

2. **传输备份文件到新服务器**：
   ```bash
   scp fastadmin_backup.sql user@new-server:/path/to/backup/
   ```

3. **在新服务器创建数据库并导入**：
   ```bash
   mysql -u root -p -e "CREATE DATABASE fastadmin CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
   mysql -u root -p fastadmin < fastadmin_backup.sql
   ```

4. **更新配置文件**：
   修改 `web/.env` 中的数据库连接信息

## 环境变量配置

### 配置文件位置

`web/.env`

### 主要配置项

#### 应用配置

```ini
[app]
app_debug = false      # 调试模式（生产环境设为false）
app_trace = false      # 应用Trace（生产环境建议关闭）
```

#### 数据库配置

```ini
[database]
type = mysql           # 数据库类型
hostname = 127.0.0.1   # 服务器地址
database = fastadmin   # 数据库名
username = root        # 数据库用户名
password = your_password # 数据库密码
hostport = 3306        # 数据库连接端口
prefix = fa_           # 数据库表前缀
charset = utf8mb4      # 数据库编码
```

#### 缓存配置

```ini
[cache]
type = file            # 缓存方式（file表示文件缓存）
path = runtime/cache/  # 缓存保存目录
prefix = ''            # 缓存前缀
expire = 0             # 缓存有效期（0表示永久缓存）
```

### 配置步骤

1. **创建配置文件**：
   ```bash
   cd web
   cp .env.example .env  # 如果有示例文件
   # 或直接创建 .env 文件
   ```

2. **编辑配置文件**：
   ```bash
   vi .env
   ```

3. **设置数据库连接**：
   根据实际数据库信息修改配置

4. **验证配置**：
   ```bash
   cd web
   php think
   # 如果配置正确，会显示ThinkPHP命令行工具
   ```

## API接口列表

1. **健康检查接口**：`GET /api/health`
2. **币种列表接口**：`GET /api/coins/list`
3. **行情数据接口**：`GET /api/coins/quote`
4. **K线图接口**：`GET /api/coins/ohlc`
5. **固件版本接口**：`GET /api/firmware/version`
6. **配置信息接口**：`GET /api/config`

## 文档

- **[部署文档](./DEPLOYMENT.md)** - 完整的部署指南（包含详细步骤、配置说明、常见问题）
- **[API接口文档（客户版）](./API_USER_GUIDE.md)** - 设备厂家和开发者使用文档
- **[API接口文档（技术版）](./API_DOCUMENTATION.md)** - 完整技术文档
- **[产品需求文档](./dev-docs/PRD文档.md)** - PRD文档
- **[编码规范](./dev-docs/编码规范.md)** - 编码规范和最佳实践

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

### Q5: 数据库导入失败

**解决方案**：
1. 检查数据库字符集是否为 `utf8mb4`
2. 检查SQL文件是否完整
3. 检查数据库用户权限
4. 临时禁用外键检查：
   ```sql
   SET FOREIGN_KEY_CHECKS=0;
   -- 导入数据
   SET FOREIGN_KEY_CHECKS=1;
   ```

### Q6: 环境变量配置不生效

**解决方案**：
1. 确保 `.env` 文件在 `web/` 目录下
2. 检查文件权限（应该是可读的）
3. 清理配置缓存：`rm -rf runtime/cache/*`
4. 重启Web服务器

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

## 更新升级

### 更新代码

```bash
git pull origin master
cd web
composer install --no-dev --optimize-autoloader
```

### 更新数据库结构

```bash
cd web
php think install:project
```

### 清理缓存

```bash
cd web
rm -rf runtime/cache/*
rm -rf runtime/temp/*
```

## 开发状态

- ✅ Phase 1: 基础框架搭建 - FastAdmin环境搭建完成
- ✅ Phase 2: 核心功能开发 - API接口开发完成
- ✅ Phase 3: 固件管理功能 - 固件版本管理完成
- ✅ Phase 4: 管理后台开发 - 后台管理功能完成
- ✅ Phase 5: 测试和优化 - 功能测试完成

## 注意事项

1. 所有API接口必须携带 `api_key` 批次参数（除健康检查接口）
2. 不带批次参数视为非法操作，返回400错误
3. 使用MySQL数据库，字符集为 `utf8mb4`
4. 所有接口都有缓存，根据数据特性设置不同的缓存时间
5. 默认限流：100次/分钟（可在后台调整）

## 相关链接

- [FastAdmin官方文档](https://doc.fastadmin.net)
- [ThinkPHP 5.0文档](https://www.kancloud.cn/manual/thinkphp5)
- [CoinGecko API文档](https://www.coingecko.com/en/api/documentation)

---

**最后更新**：2025-12-21
