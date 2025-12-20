-- 初始化数据库脚本
-- 创建fastadmin数据库（如果docker-compose中已创建，这里会忽略）
CREATE DATABASE IF NOT EXISTS fastadmin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 授权（可选，如果需要单独用户）
-- GRANT ALL PRIVILEGES ON fastadmin.* TO 'fastadmin'@'%';
-- FLUSH PRIVILEGES;

