# API 接口文档

## 基础说明

### 鉴权机制

所有API接口（除健康检查接口外）都需要在Query参数中携带 `api_key` 参数。

**鉴权规则**：
- 不带 `api_key` 参数：返回 400 错误（Missing required parameter: api_key）
- `api_key` 无效：返回 401 错误（Invalid api_key）
- 批次被禁用：返回 403 错误（Batch is disabled）
- 验证通过：继续执行接口逻辑

**测试批次密钥**：`test_api_key_123456`

---

## 接口列表

### 1. 健康检查接口

**接口路径**：`GET /api/health/index`

**功能描述**：检查系统运行状态，用于监控和健康检查

**请求参数**：
- `api_key`（可选）：批次密钥。如果提供会验证并返回批次信息

**响应示例**：
```json
{
  "code": 1,
  "msg": "Health check completed",
  "time": 1766240433,
  "data": {
    "status": "ok",
    "timestamp": 1766240433,
    "version": "1.0.0",
    "database": "connected",
    "cache": "working",
    "batch": {
      "id": 1,
      "name": "测试批次",
      "status": 1
    }
  }
}
```

**响应字段说明**：
- `status`：系统状态（"ok"表示正常，"error"表示异常）
- `timestamp`：当前时间戳（Unix时间戳）
- `version`：系统版本号
- `database`：数据库连接状态（"connected"表示已连接）
- `cache`：缓存状态（"working"表示正常工作）
- `batch`：批次信息（如果提供了api_key且验证通过）

**说明**：
- 健康检查接口可以不提供 `api_key`，也可以提供（可选）
- 如果提供了 `api_key` 且验证通过，会返回批次信息

**测试命令**：
```bash
# 不带api_key
curl "http://localhost:8000/index.php/api/health/index"

# 带api_key
curl "http://localhost:8000/index.php/api/health/index?api_key=test_api_key_123456"
```

---

## 错误码说明

| 错误码 | HTTP状态码 | 说明 |
|--------|-----------|------|
| 400 | 400 | 缺少必需参数：api_key |
| 401 | 401 | api_key无效 |
| 403 | 403 | 批次被禁用 |

---

## 开发进度

- ✅ 鉴权机制实现
- ✅ 健康检查接口
- ⏳ 币种列表接口
- ⏳ 行情数据接口
- ⏳ K线图接口
- ⏳ Logo接口
- ⏳ 固件版本接口
- ⏳ 配置信息接口

