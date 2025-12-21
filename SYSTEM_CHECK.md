# 系统检查清单

**系统启动时间**：2025-12-21

---

## 🔗 访问地址

### 后台管理
- **登录地址**：http://localhost:8000/LUgeswcuTm.php
- **用户名**：`admin`
- **密码**：`qexF3dkAER`

### API接口
- **基础URL**：http://localhost:8000/index.php/api
- **测试API密钥**：`test_api_key_123456`

---

## 📋 后台管理功能检查

### 1. 批次管理
- **地址**：http://localhost:8000/LUgeswcuTm.php/batches
- **功能**：
  - ✅ 查看批次列表
  - ✅ 添加新批次
  - ✅ 编辑批次信息
  - ✅ 删除批次
  - ✅ 管理API密钥
  - ✅ 配置限流次数

### 2. 币种管理
- **地址**：http://localhost:8000/LUgeswcuTm.php/coins
- **功能**：
  - ✅ 查看币种列表
  - ✅ 添加新币种
  - ✅ 编辑币种信息
  - ✅ 自定义币种名称、Logo、描述
  - ✅ 启用/禁用币种

### 3. 固件管理
- **地址**：http://localhost:8000/LUgeswcuTm.php/firmwareversions
- **功能**：
  - ✅ 查看固件版本列表
  - ✅ 添加新固件版本
  - ✅ 编辑固件信息
  - ✅ 上传固件文件
  - ✅ 配置强制更新

### 4. 系统配置管理
- **地址**：http://localhost:8000/LUgeswcuTm.php/systemconfigs
- **功能**：
  - ✅ 查看配置列表
  - ✅ 添加新配置
  - ✅ 编辑配置值
  - ✅ 删除配置

---

## 📋 API接口检查

### 1. 健康检查接口
- **路径**：`GET /api/health/index`
- **测试命令**：
  ```bash
  curl "http://localhost:8000/index.php/api/health/index?api_key=test_api_key_123456"
  ```
- **检查项**：
  - ✅ 返回系统状态
  - ✅ 数据库连接状态
  - ✅ 缓存状态
  - ✅ 批次信息（如果提供api_key）

### 2. 币种列表接口
- **路径**：`GET /api/coins/list`
- **测试命令**：
  ```bash
  curl "http://localhost:8000/index.php/api/coins/list?api_key=test_api_key_123456"
  ```
- **检查项**：
  - ✅ 返回币种列表
  - ✅ 包含币种ID、名称、Logo
  - ✅ Logo URL正确

### 3. 行情数据接口
- **路径**：`GET /api/coins/quote`
- **测试命令**：
  ```bash
  curl "http://localhost:8000/index.php/api/coins/quote?coin_id=bitcoin&api_key=test_api_key_123456"
  ```
- **检查项**：
  - ✅ 返回币种信息
  - ✅ 返回行情数据（价格、涨跌幅、市值、交易量）
  - ✅ 数据格式正确

### 4. K线图接口
- **路径**：`GET /api/coins/ohlc`
- **测试命令**：
  ```bash
  # 5分钟K线
  curl "http://localhost:8000/index.php/api/coins/ohlc?coin_id=bitcoin&interval=5m&api_key=test_api_key_123456"
  
  # 1小时K线
  curl "http://localhost:8000/index.php/api/coins/ohlc?coin_id=bitcoin&interval=1h&api_key=test_api_key_123456"
  
  # 1天K线
  curl "http://localhost:8000/index.php/api/coins/ohlc?coin_id=bitcoin&interval=1d&api_key=test_api_key_123456"
  ```
- **检查项**：
  - ✅ 返回K线数据
  - ✅ 数据格式正确（[timestamp, open, high, low, close]）
  - ✅ 不同时间维度数据正确

### 5. 固件版本接口
- **路径**：`GET /api/firmware/version`
- **测试命令**：
  ```bash
  curl "http://localhost:8000/index.php/api/firmware/version?api_key=test_api_key_123456&current_version=1.0.0"
  ```
- **检查项**：
  - ✅ 返回最新版本信息
  - ✅ 版本比较功能正常
  - ✅ 下载URL正确

### 6. 配置信息接口
- **路径**：`GET /api/config/index`
- **测试命令**：
  ```bash
  curl "http://localhost:8000/index.php/api/config/index?api_key=test_api_key_123456"
  ```
- **检查项**：
  - ✅ 返回系统配置
  - ✅ 配置项完整

---

## 🔐 鉴权测试

### 测试场景
1. **不带api_key**：
   ```bash
   curl "http://localhost:8000/index.php/api/coins/list"
   ```
   - 预期：返回400错误

2. **无效api_key**：
   ```bash
   curl "http://localhost:8000/index.php/api/coins/list?api_key=invalid_key"
   ```
   - 预期：返回401错误

3. **有效api_key**：
   ```bash
   curl "http://localhost:8000/index.php/api/coins/list?api_key=test_api_key_123456"
   ```
   - 预期：返回正常数据

---

## 📊 数据库检查

### 检查表数据
```bash
# 连接数据库
docker exec -it mysql_dev mysql -uroot -proot123456 fastadmin

# 检查数据
SELECT COUNT(*) FROM fa_batches;
SELECT COUNT(*) FROM fa_coins;
SELECT COUNT(*) FROM fa_firmware_versions;
SELECT COUNT(*) FROM fa_system_configs;
```

---

## 🛠️ 服务器管理

### 查看服务器状态
```bash
ps aux | grep "php -S localhost:8000"
```

### 查看服务器日志
```bash
tail -f /tmp/fastadmin_server.log
```

### 停止服务器
```bash
kill $(cat /tmp/fastadmin_server.pid)
```

### 重启服务器
```bash
cd web
php -S localhost:8000 -t public > /tmp/fastadmin_server.log 2>&1 &
echo $! > /tmp/fastadmin_server.pid
```

---

## ✅ 检查清单

- [ ] 后台登录正常
- [ ] 批次管理功能正常
- [ ] 币种管理功能正常
- [ ] 固件管理功能正常
- [ ] 系统配置管理功能正常
- [ ] 健康检查接口正常
- [ ] 币种列表接口正常
- [ ] 行情数据接口正常
- [ ] K线图接口正常
- [ ] 固件版本接口正常
- [ ] 配置信息接口正常
- [ ] 鉴权机制正常
- [ ] 错误处理正常

---

**最后更新**：2025-12-21

