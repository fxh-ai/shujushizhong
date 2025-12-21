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

### 步骤4：配置环境变量

#### 创建.env配置文件

```bash
cd web
vi .env
```

#### 配置数据库连接

编辑 `.env` 文件，设置数据库信息：

```ini
[database]
type = mysql
hostname = 127.0.0.1
database = fastadmin
username = root
password = your_password
hostport = 3306
prefix = fa_
charset = utf8mb4
```

#### 配置应用模式

```ini
[app]
app_debug = false  # 生产环境设为false
app_trace = false
```

### 步骤5：安装和初始化

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
- 数据库信息（如果.env已配置，会自动读取）
- 管理员账号和密码

**注意**：如果已通过 `.env` 配置数据库，安装程序会自动读取配置。

#### 创建项目表结构

```bash
php think install:project
```

此命令会创建项目所需的业务表：
- `fa_batches` - 批次表
- `fa_coins` - 币种表
- `fa_coin_quotes` - 行情数据表
- `fa_coin_ohlc_cache` - K线缓存表
- `fa_firmware_versions` - 固件版本表
- `fa_system_configs` - 系统配置表
- `fa_rate_limit_logs` - 限流日志表

#### 导入初始数据（可选）

如果有数据库备份文件，可以直接导入：

```bash
# 导入完整数据库（包含FastAdmin系统表和项目表）
mysql -u root -p fastadmin < database/backup/fastadmin_YYYYMMDD_HHMMSS.sql

# 或只导入项目表结构
php think install:project
```

#### 同步币种数据（可选）

```bash
php think sync:coins
```

此命令会从CoinGecko API同步常见币种数据。

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

**完整配置示例**：
```ini
[app]
# 应用调试模式（生产环境必须设为false）
app_debug = false

# 应用Trace（生产环境建议关闭）
app_trace = false

[database]
# 数据库类型
type = mysql

# 服务器地址
hostname = 127.0.0.1

# 数据库名
database = fastadmin

# 数据库用户名
username = root

# 数据库密码
password = your_password

# 数据库连接端口
hostport = 3306

# 数据库连接参数
params = []

# 数据库编码默认采用utf8mb4
charset = utf8mb4

# 数据库表前缀
prefix = fa_

# 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
deploy = 0

# 数据库读写是否分离 主从式有效
rw_separate = false

# 读写分离后 主服务器数量
master_num = 1

# 指定从服务器序号
slave_no = ''

# 是否严格检查字段是否存在
fields_strict = true

# 数据集返回类型
resultset_type = array

# 自动写入时间戳字段
auto_timestamp = false

# 时间字段取出后的默认时间格式
datetime_format = 'Y-m-d H:i:s'

# 是否需要进行SQL性能分析
sql_explain = false

[cache]
# 缓存方式（file表示文件缓存）
type = file

# 缓存保存目录
path = runtime/cache/

# 缓存前缀
prefix = ''

# 缓存有效期 0表示永久缓存
expire = 0
```

**重要配置项说明**：

1. **app_debug**：
   - 开发环境：`true`（显示详细错误信息）
   - 生产环境：`false`（隐藏错误信息，提高安全性）

2. **database配置**：
   - `hostname`：数据库服务器地址（本地为127.0.0.1，远程填写IP或域名）
   - `database`：数据库名称（默认为fastadmin）
   - `username`：数据库用户名
   - `password`：数据库密码（生产环境使用强密码）
   - `hostport`：数据库端口（MySQL默认为3306）
   - `prefix`：表前缀（默认为fa_，建议保持默认）

3. **cache配置**：
   - `type`：缓存类型（file表示文件缓存，生产环境可考虑使用Redis）
   - `path`：缓存文件存储路径

### 环境变量配置步骤

1. **复制配置文件**：
   ```bash
   cd web
   cp .env.example .env  # 如果有示例文件
   # 或直接创建 .env 文件
   ```

2. **编辑配置文件**：
   ```bash
   vi .env
   # 或使用其他编辑器
   ```

3. **设置数据库连接**：
   根据实际数据库信息修改以下配置：
   ```ini
   [database]
   hostname = 127.0.0.1
   database = fastadmin
   username = your_username
   password = your_password
   hostport = 3306
   prefix = fa_
   ```

4. **设置应用模式**：
   ```ini
   [app]
   app_debug = false  # 生产环境设为false
   ```

5. **验证配置**：
   ```bash
   cd web
   php think
   # 如果配置正确，会显示ThinkPHP命令行工具
   ```

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

### Q6: 数据库导入失败

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

### Q7: 环境变量配置不生效

**解决方案**：
1. 确保 `.env` 文件在 `web/` 目录下
2. 检查文件权限（应该是可读的）
3. 清理配置缓存：`rm -rf runtime/cache/*`
4. 重启Web服务器

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

## 数据库备份和恢复

### 数据库备份

#### 方法1：使用Docker MySQL容器备份（推荐）

```bash
# 进入项目目录
cd /path/to/shuzishizhong

# 创建备份目录
mkdir -p database/backup

# 执行备份
docker exec mysql_dev mysqldump -uroot -proot123456 \
  --default-character-set=utf8mb4 \
  --single-transaction \
  --routines \
  --triggers \
  fastadmin > database/backup/fastadmin_$(date +%Y%m%d_%H%M%S).sql
```

#### 方法2：使用MySQL客户端备份

```bash
# 直接使用mysqldump命令
mysqldump -u root -p \
  --default-character-set=utf8mb4 \
  --single-transaction \
  --routines \
  --triggers \
  fastadmin > backup_$(date +%Y%m%d_%H%M%S).sql
```

**备份参数说明**：
- `--default-character-set=utf8mb4`：使用utf8mb4字符集
- `--single-transaction`：保证数据一致性（InnoDB表）
- `--routines`：包含存储过程和函数
- `--triggers`：包含触发器

#### 备份内容

备份文件包含：
- ✅ 所有表结构（CREATE TABLE）
- ✅ 所有表数据（INSERT INTO）
- ✅ 存储过程和函数
- ✅ 触发器
- ✅ 字符集信息

#### 备份文件位置

备份文件保存在：`database/backup/fastadmin_YYYYMMDD_HHMMSS.sql`

### 数据库恢复

#### 方法1：使用Docker MySQL容器恢复

```bash
# 1. 确保目标服务器已创建数据库
docker exec mysql_dev mysql -uroot -proot123456 -e \
  "CREATE DATABASE IF NOT EXISTS fastadmin CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"

# 2. 导入数据
docker exec -i mysql_dev mysql -uroot -proot123456 fastadmin < \
  database/backup/fastadmin_20251221_151800.sql
```

#### 方法2：使用MySQL客户端恢复

```bash
# 1. 创建数据库（如果不存在）
mysql -u root -p -e \
  "CREATE DATABASE IF NOT EXISTS fastadmin CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"

# 2. 导入数据
mysql -u root -p fastadmin < database/backup/fastadmin_20251221_151800.sql
```

#### 恢复步骤详解

1. **创建数据库**（如果不存在）：
   ```sql
   CREATE DATABASE IF NOT EXISTS `fastadmin` 
   CHARACTER SET utf8mb4 
   COLLATE utf8mb4_general_ci;
   ```

2. **导入数据**：
   ```bash
   mysql -u root -p fastadmin < backup_file.sql
   ```

3. **验证恢复**：
   ```sql
   -- 检查表数量
   SELECT COUNT(*) as table_count 
   FROM information_schema.tables 
   WHERE table_schema = 'fastadmin';
   
   -- 检查主要表的数据
   SELECT COUNT(*) as batch_count FROM fa_batches;
   SELECT COUNT(*) as coin_count FROM fa_coins;
   SELECT COUNT(*) as quote_count FROM fa_coin_quotes;
   ```

### 数据库迁移到新服务器

#### 完整迁移步骤

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
   # 使用SCP传输
   scp fastadmin_backup.sql user@new-server:/path/to/backup/
   
   # 或使用FTP/SFTP工具
   ```

3. **在新服务器创建数据库**：
   ```bash
   mysql -u root -p -e \
     "CREATE DATABASE fastadmin CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
   ```

4. **在新服务器导入数据**：
   ```bash
   mysql -u root -p fastadmin < fastadmin_backup.sql
   ```

5. **更新配置文件**：
   修改 `web/.env` 中的数据库连接信息：
   ```ini
   [database]
   hostname = new_server_ip  # 新服务器地址
   database = fastadmin
   username = root
   password = new_password
   hostport = 3306
   ```

6. **测试连接**：
   ```bash
   cd web
   php think
   # 如果配置正确，会显示ThinkPHP命令行工具
   ```

### 代码备份

```bash
# 备份整个项目
tar -czf code_backup_$(date +%Y%m%d).tar.gz \
  --exclude='web/node_modules' \
  --exclude='web/vendor' \
  --exclude='web/runtime' \
  --exclude='.git' \
  .

# 或只备份web目录
tar -czf web_backup_$(date +%Y%m%d).tar.gz web/
```

### 完整恢复流程

```bash
# 1. 恢复代码
tar -xzf code_backup_20251221.tar.gz

# 2. 恢复数据库
mysql -u root -p fastadmin < backup_20251221.sql

# 3. 安装依赖
cd web
composer install --no-dev --optimize-autoloader

# 4. 设置权限
chmod -R 755 runtime
chmod -R 755 public/uploads

# 5. 更新配置文件
vi .env  # 修改数据库连接信息

# 6. 清理缓存
rm -rf runtime/cache/*
rm -rf runtime/temp/*
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

