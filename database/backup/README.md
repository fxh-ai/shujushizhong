# 数据库备份说明

## 备份文件

数据库备份文件保存在此目录下，文件名格式：`fastadmin_YYYYMMDD_HHMMSS.sql`

## 导出命令

### 标准导出（MySQL/MariaDB通用）

```bash
# 使用Docker MySQL容器导出
docker exec mysql_dev mysqldump -uroot -proot123456 \
  --default-character-set=utf8mb4 \
  --single-transaction \
  --routines \
  --triggers \
  fastadmin > database/backup/fastadmin_$(date +%Y%m%d_%H%M%S).sql
```

### MariaDB 10.5.27 兼容导出（推荐用于生产环境）

```bash
# 使用Docker MySQL容器导出（包含数据库创建语句，兼容MariaDB 10.5.27）
docker exec mysql_dev mysqldump -uroot -proot123456 \
  --default-character-set=utf8mb4 \
  --single-transaction \
  --routines \
  --triggers \
  --events \
  --add-drop-database \
  --databases fastadmin > database/backup/fastadmin_mariadb_$(date +%Y%m%d_%H%M%S).sql
```

**MariaDB 兼容导出参数说明**：
- `--events`：包含事件调度器
- `--add-drop-database`：包含 `DROP DATABASE IF EXISTS` 和 `CREATE DATABASE` 语句
- `--databases`：导出数据库创建语句，便于在新服务器直接导入

## 导入命令

### 方法1：使用Docker MySQL容器导入

```bash
# 1. 确保目标服务器已创建数据库
docker exec mysql_dev mysql -uroot -proot123456 -e "CREATE DATABASE IF NOT EXISTS fastadmin CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"

# 2. 导入数据
docker exec -i mysql_dev mysql -uroot -proot123456 fastadmin < database/backup/fastadmin_YYYYMMDD_HHMMSS.sql
```

### 方法2：使用MySQL客户端导入

```bash
# 1. 创建数据库
mysql -uroot -p -e "CREATE DATABASE IF NOT EXISTS fastadmin CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"

# 2. 导入数据
mysql -uroot -p fastadmin < database/backup/fastadmin_YYYYMMDD_HHMMSS.sql
```

## 注意事项

1. **字符集**：备份文件使用 `utf8mb4` 字符集，确保目标数据库也使用相同字符集
2. **表前缀**：默认表前缀为 `fa_`，如果目标服务器使用不同前缀，需要修改SQL文件
3. **时区**：确保目标服务器的MySQL时区设置正确
4. **权限**：导入后需要检查用户权限和API密钥是否正确

## 备份内容

备份包含：
- ✅ 数据库创建语句（CREATE DATABASE，仅MariaDB兼容版本）
- ✅ 所有表结构（CREATE TABLE）
- ✅ 所有表数据（INSERT INTO）
- ✅ 存储过程和函数（--routines）
- ✅ 触发器（--triggers）
- ✅ 事件调度器（--events，仅MariaDB兼容版本）
- ✅ 字符集信息（utf8mb4）

## 备份文件说明

- `fastadmin_YYYYMMDD_HHMMSS.sql`：标准备份文件（不包含数据库创建语句）
- `fastadmin_mariadb_YYYYMMDD_HHMMSS.sql`：MariaDB 10.5.27 兼容备份文件（包含数据库创建语句，推荐用于生产环境迁移）

## 验证备份

导入后，建议执行以下检查：

```sql
-- 检查表数量
SELECT COUNT(*) as table_count FROM information_schema.tables 
WHERE table_schema = 'fastadmin';

-- 检查主要表的数据
SELECT COUNT(*) as batch_count FROM fa_batches;
SELECT COUNT(*) as coin_count FROM fa_coins;
SELECT COUNT(*) as quote_count FROM fa_coin_quotes;
```

## 迁移步骤

1. **导出数据库**（已完成）
   ```bash
   docker exec mysql_dev mysqldump ... > backup.sql
   ```

2. **传输备份文件到目标服务器**
   ```bash
   scp database/backup/fastadmin_*.sql user@target-server:/path/to/backup/
   ```

3. **在目标服务器创建数据库**
   ```bash
   mysql -uroot -p -e "CREATE DATABASE fastadmin CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
   ```

4. **导入数据**
   ```bash
   mysql -uroot -p fastadmin < fastadmin_*.sql
   ```

5. **更新配置文件**
   - 修改 `web/.env` 中的数据库连接信息
   - 确保数据库用户名、密码、主机地址正确

6. **测试连接**
   ```bash
   cd web
   php think
   ```

## 常见问题

### Q: 导入时出现字符集错误？
A: 确保目标数据库使用 `utf8mb4` 字符集：
```sql
ALTER DATABASE fastadmin CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
```

### Q: 导入时出现外键约束错误？
A: 临时禁用外键检查：
```sql
SET FOREIGN_KEY_CHECKS=0;
-- 导入数据
SET FOREIGN_KEY_CHECKS=1;
```

### Q: 如何只导出特定表？
A: 在mysqldump命令后添加表名：
```bash
mysqldump ... fastadmin table1 table2 > backup.sql
```

### Q: 如何导入到 MariaDB 10.5.27？
A: 使用 MariaDB 兼容版本的备份文件：
```bash
# 1. 如果备份文件包含 CREATE DATABASE 语句，直接导入即可
mysql -u root -p < fastadmin_mariadb_YYYYMMDD_HHMMSS.sql

# 2. 如果备份文件不包含 CREATE DATABASE 语句，需要先创建数据库
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS fastadmin CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
mysql -u root -p fastadmin < fastadmin_YYYYMMDD_HHMMSS.sql
```

**注意**：MariaDB 10.5.27 完全兼容标准的 MySQL dump 文件，可以直接导入使用。

