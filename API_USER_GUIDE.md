# API接口使用文档

**版本**：v1.0.0  
**最后更新**：2025-12-21  
**适用对象**：设备厂家、开发者

---

## 📋 目录

1. [快速开始](#快速开始)
2. [接口列表](#接口列表)
3. [请求示例](#请求示例)
4. [错误处理](#错误处理)
5. [常见问题](#常见问题)

---

## 快速开始

### API基础信息

- **基础URL**：`https://your-domain.com/index.php/api`
- **请求方式**：GET
- **数据格式**：JSON
- **字符编码**：UTF-8

### 鉴权说明

所有接口（除健康检查外）都需要在请求参数中携带 `api_key`。

**获取api_key**：
1. 联系管理员创建批次
2. 获取批次对应的 `api_key`
3. 在请求时作为Query参数传递

**示例**：
```
GET /api/coins/list?api_key=your_api_key_here
```

---

## 接口列表

### 1. 健康检查接口

**接口路径**：`GET /api/health/index`

**功能**：检查系统运行状态

**请求参数**：
- `api_key`（可选）：批次密钥

**响应示例**：
```json
{
  "code": 1,
  "msg": "Health check completed",
  "time": 1766299475,
  "data": {
    "status": "ok",
    "timestamp": 1766299475,
    "version": "1.0.0",
    "database": "connected",
    "cache": "working"
  }
}
```

---

### 2. 币种列表接口

**接口路径**：`GET /api/coins/list`

**功能**：获取所有支持的币种列表

**请求参数**：
- `api_key`（必需）：批次密钥

**响应示例**：
```json
{
  "code": 1,
  "msg": "获取成功",
  "time": 1766299475,
  "data": [
    {
      "id": "bitcoin",
      "symbol": "btc",
      "name": "Bitcoin",
      "logo": "https://your-domain.com/uploads/coins/bitcoin.png",
      "description": "Bitcoin description"
    }
  ]
}
```

**字段说明**：
- `id`：币种ID（用于后续接口）
- `symbol`：币种符号
- `name`：币种名称
- `logo`：Logo图片URL
- `description`：币种描述

---

### 3. 行情数据接口

**接口路径**：`GET /api/coins/quote`

**功能**：获取单个币种的实时行情数据

**请求参数**：
- `api_key`（必需）：批次密钥
- `coin_id`（必需）：币种ID（如：bitcoin）
- `currency`（可选）：计价货币，默认USD

**响应示例**：
```json
{
  "code": 1,
  "msg": "获取成功",
  "time": 1766299475,
  "data": {
    "coin": {
      "id": "bitcoin",
      "symbol": "btc",
      "name": "Bitcoin",
      "logo": "https://your-domain.com/uploads/coins/bitcoin.png"
    },
    "quote": {
      "usd": 88000.50,
      "usd_24h_change": -0.5,
      "usd_24h_change_percentage": -0.5,
      "usd_market_cap": 1750000000000,
      "usd_24h_vol": 30000000000,
      "last_updated_at": 1766299475
    }
  }
}
```

**字段说明**：
- `coin`：币种基本信息
- `quote`：行情数据
  - `usd`：当前价格（USD）
  - `usd_24h_change`：24小时涨跌幅（百分比数值）
  - `usd_24h_change_percentage`：24小时涨跌幅（百分比）
  - `usd_market_cap`：市值
  - `usd_24h_vol`：24小时交易量
  - `last_updated_at`：最后更新时间戳

---

### 4. K线图接口

**接口路径**：`GET /api/coins/ohlc`

**功能**：获取K线数据（开盘价、最高价、最低价、收盘价）

**请求参数**：
- `api_key`（必需）：批次密钥
- `coin_id`（必需）：币种ID
- `interval`（必需）：时间维度
  - `5m`：5分钟K线
  - `1h`：1小时K线
  - `1d`：1天K线
- `currency`（可选）：计价货币，默认USD

**响应示例**：
```json
{
  "code": 1,
  "msg": "获取成功",
  "time": 1766299475,
  "data": [
    [1766240400, 88000, 88500, 87500, 88050],
    [1766240700, 88050, 88200, 87800, 88100]
  ]
}
```

**数据格式**：
每个数组元素：`[timestamp, open, high, low, close]`
- `timestamp`：时间戳（Unix时间戳）
- `open`：开盘价
- `high`：最高价
- `low`：最低价
- `close`：收盘价

---

### 5. 固件版本接口

**接口路径**：`GET /api/firmware/version`

**功能**：获取最新固件版本信息

**请求参数**：
- `api_key`（必需）：批次密钥
- `current_version`（可选）：当前设备固件版本

**响应示例**：
```json
{
  "code": 1,
  "msg": "获取成功",
  "time": 1766299475,
  "data": {
    "latest_version": "1.1.0",
    "current_version": "1.0.0",
    "need_update": true,
    "download_url": "https://your-domain.com/uploads/firmware/v1.1.0.bin",
    "file_size": 1048576,
    "release_notes": "修复了一些bug，新增了功能",
    "force_update": false
  }
}
```

**字段说明**：
- `latest_version`：最新版本号
- `current_version`：当前版本号（如果提供）
- `need_update`：是否需要更新
- `download_url`：下载URL
- `file_size`：文件大小（字节）
- `release_notes`：发布说明
- `force_update`：是否强制更新

---

### 6. 配置信息接口

**接口路径**：`GET /api/config/index`

**功能**：获取系统配置信息

**请求参数**：
- `api_key`（必需）：批次密钥

**响应示例**：
```json
{
  "code": 1,
  "msg": "获取成功",
  "time": 1766299475,
  "data": {
    "refresh_interval": 300,
    "default_currency": "USD",
    "display_coins": ["bitcoin", "ethereum"],
    "timezone": "Asia/Shanghai"
  }
}
```

---

## 请求示例

### cURL示例

```bash
# 健康检查
curl "https://your-domain.com/index.php/api/health/index?api_key=your_api_key"

# 币种列表
curl "https://your-domain.com/index.php/api/coins/list?api_key=your_api_key"

# 行情数据
curl "https://your-domain.com/index.php/api/coins/quote?coin_id=bitcoin&api_key=your_api_key"

# K线数据
curl "https://your-domain.com/index.php/api/coins/ohlc?coin_id=bitcoin&interval=5m&api_key=your_api_key"
```

### JavaScript示例

```javascript
// 获取币种列表
fetch('https://your-domain.com/index.php/api/coins/list?api_key=your_api_key')
  .then(response => response.json())
  .then(data => {
    if (data.code === 1) {
      console.log('币种列表:', data.data);
    } else {
      console.error('错误:', data.msg);
    }
  });

// 获取行情数据
fetch('https://your-domain.com/index.php/api/coins/quote?coin_id=bitcoin&api_key=your_api_key')
  .then(response => response.json())
  .then(data => {
    if (data.code === 1) {
      console.log('行情数据:', data.data);
    }
  });
```

### Python示例

```python
import requests

api_key = 'your_api_key'
base_url = 'https://your-domain.com/index.php/api'

# 获取币种列表
response = requests.get(f'{base_url}/coins/list', params={'api_key': api_key})
data = response.json()
if data['code'] == 1:
    print('币种列表:', data['data'])

# 获取行情数据
response = requests.get(f'{base_url}/coins/quote', params={
    'coin_id': 'bitcoin',
    'api_key': api_key
})
data = response.json()
if data['code'] == 1:
    print('行情数据:', data['data'])
```

---

## 错误处理

### 错误响应格式

```json
{
  "code": 0,
  "msg": "错误信息",
  "time": 1766299475,
  "data": null
}
```

### 错误码说明

| 错误码 | HTTP状态码 | 说明 | 解决方案 |
|--------|-----------|------|---------|
| 400 | 400 | 缺少必需参数：api_key | 在请求中添加api_key参数 |
| 401 | 401 | api_key无效 | 检查api_key是否正确 |
| 403 | 403 | 批次被禁用 | 联系管理员启用批次 |
| 429 | 429 | 请求频率超限 | 降低请求频率，等待后重试 |

### 限流说明

- 每个批次有独立的请求频率限制
- 默认限制：100次/分钟
- 超过限制时返回429错误
- 响应头包含限流信息：
  - `X-RateLimit-Limit`：限流上限
  - `X-RateLimit-Remaining`：剩余请求次数
  - `X-RateLimit-Reset`：重置时间戳

---

## 常见问题

### Q1: 如何获取api_key？

A: 联系系统管理员创建批次，获取对应的api_key。

### Q2: api_key会过期吗？

A: 不会，api_key永久有效，除非批次被禁用。

### Q3: 数据更新频率是多少？

A: 
- 币种列表：30分钟更新一次
- 行情数据：5分钟更新一次
- K线数据：根据时间维度（5m/1h/1d）不同

### Q4: 如何判断是否需要更新固件？

A: 调用固件版本接口，传入当前版本号，接口会返回 `need_update` 字段。

### Q5: 请求失败怎么办？

A: 
1. 检查网络连接
2. 检查api_key是否正确
3. 查看错误码和错误信息
4. 联系技术支持

### Q6: 支持哪些币种？

A: 通过币种列表接口获取所有支持的币种。系统默认支持常见币种，如需添加请联系管理员。

---

## 技术支持

如有问题，请联系技术支持：
- 邮箱：support@example.com
- 电话：400-xxx-xxxx

---

**文档版本**：v1.0.0  
**最后更新**：2025-12-21

