# Docker MySQL 服务说明

## 服务配置

- **容器名称**：mysql_dev
- **端口**：3306
- **Root密码**：root123456
- **数据库**：fastadmin（自动创建）
- **字符集**：utf8mb4
- **数据持久化**：Docker Volume `shuzishizhong_mysql_data`

## 常用命令

### 启动服务
```bash
docker-compose up -d mysql
# 或
docker compose up -d mysql
```

### 停止服务
```bash
docker-compose stop mysql
# 或
docker compose stop mysql
```

### 查看日志
```bash
docker-compose logs -f mysql
# 或
docker compose logs -f mysql
```

### 进入MySQL命令行
```bash
docker exec -it mysql_dev mysql -uroot -proot123456
```

### 查看数据库
```bash
docker exec mysql_dev mysql -uroot -proot123456 -e "SHOW DATABASES;"
```

### 备份数据库
```bash
docker exec mysql_dev mysqldump -uroot -proot123456 fastadmin > backup.sql
```

### 恢复数据库
```bash
docker exec -i mysql_dev mysql -uroot -proot123456 fastadmin < backup.sql
```

## 连接信息

- **主机**：127.0.0.1
- **端口**：3306
- **用户名**：root
- **密码**：root123456
- **数据库**：fastadmin

## 数据持久化

数据库数据存储在Docker Volume中，即使删除容器，数据也不会丢失。

如需完全清理（包括数据）：
```bash
docker-compose down -v
```

## 其他项目复用

这个MySQL服务可以供其他项目使用，只需：
1. 在项目中创建新的数据库
2. 使用相同的连接信息（127.0.0.1:3306, root/root123456）

