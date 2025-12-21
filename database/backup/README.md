# 数据库备份说明

## 备份文件

数据库备份文件保存在此目录下，文件名格式：`fastadmin_YYYYMMDD_HHMMSS.sql`

## 导出命令

```bash
# 使用Docker MySQL容器导出
docker exec mysql_dev mysqldump -uroot -proot123456 \
  --default-character-set=utf8mb4 \
  --single-transaction \
  --routines \
  --triggers \
  fastadmin > database/backup/fastadmin_$(date +%Y%m%d_%H%M%S).sql
```

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
- ✅ 所有表结构（CREATE TABLE）
- ✅ 所有表数据（INSERT INTO）
- ✅ 存储过程和函数（--routines）
- ✅ 触发器（--triggers）
- ✅ 字符集信息（utf8mb4）

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

