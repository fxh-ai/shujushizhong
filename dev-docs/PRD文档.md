# 数据时钟项目 PRD（产品需求文档）

## 1. 项目概述

### 1.1 项目背景
数据时钟项目是在普通时钟硬件上显示加密货币行情数据的智能设备。通过对接CoinGecko API获取实时加密货币行情，为时钟设备提供数据支持。

### 1.2 项目目标
- 为数据时钟硬件设备提供稳定的加密货币行情数据接口
- 支持多批次品牌设备的统一管理
- 提供设备固件升级管理功能
- 最小化系统对外依赖，使用轻量级技术栈

---

## 2. 系统架构

### 2.1 系统组成
- **A. 管理后台**：用于配置管理、设备批次管理、固件版本管理等
- **B. API接口服务**：为时钟设备提供数据接口
- **C. 数据时钟端**：硬件设备（由厂家对接，本项目不涉及）

### 2.2 技术栈
- **后端框架**：FastAdmin
- **数据库**：MySQL
- **缓存**：文件缓存
- **外部依赖**：CoinGecko API（加密货币数据源）

---

## 3. 功能需求

### 3.1 API接口设计

#### 3.1.1 获取所有支持的币种列表
**接口路径**：`GET /api/coins/list`

**功能描述**：返回系统支持的所有加密货币币种列表，数据格式遵循CoinGecko API标准

**请求参数**：
- `api_key`（必需）：批次密钥（Query参数）

**响应格式**：按照CoinGecko API `/coins/list` 标准格式返回，并包含自定义信息
```json
[
  {
    "id": "bitcoin",
    "symbol": "btc",
    "name": "Bitcoin",
    "logo": "https://example.com/uploads/coins/bitcoin.png",
    "description": "Bitcoin description"
  },
  {
    "id": "ethereum",
    "symbol": "eth",
    "name": "Ethereum",
    "logo": "https://example.com/uploads/coins/ethereum.png",
    "description": "Ethereum description"
  }
]
```

**说明**：
- 不需要分页功能，返回所有启用的币种
- 不需要搜索功能，返回完整列表
- 支持自定义币种信息：logo、name、description
- logo返回本地存储的图片URL

**鉴权**：通过Query参数 `api_key` 传递批次密钥（必需，不带批次参数为非法操作）

**缓存策略**：
- 缓存时间：1小时（币种列表变化频率低）
- 缓存失效策略：时间到期后自动失效，下次请求时重新获取

---

#### 3.1.2 获取单个货币的行情数据
**接口路径**：`GET /api/coins/{coin_id}/quote`

**功能描述**：获取指定币种的实时行情数据和币种详情信息

**请求参数**：
- `api_key`（必需）：批次密钥（Query参数）
- `coin_id`：币种ID（路径参数，如：bitcoin、ethereum）
- `currency`（可选）：计价货币，默认USD（支持：usd, cny, eur等）

**响应格式**：返回行情数据和币种详情
```json
{
  "coin": {
    "id": "bitcoin",
    "symbol": "btc",
    "name": "Bitcoin",
    "custom_name": null,
    "logo": "https://example.com/uploads/coins/bitcoin.png",
    "description": "Bitcoin description"
  },
  "quote": {
    "usd": 45000.50,
    "usd_24h_change": 2.5,
    "usd_24h_change_percentage": 2.5,
    "usd_market_cap": 850000000000,
    "usd_24h_vol": 25000000000,
    "last_updated_at": 1234567890
  }
}
```

**响应字段说明**：
- `coin`：币种详情信息
  - `id`：币种ID
  - `symbol`：币种代码
  - `name`：币种名称（CoinGecko原始名称）
  - `custom_name`：自定义名称（如果设置了，优先使用此字段）
  - `logo`：Logo图片URL（本地存储的图片）
  - `description`：币种描述（支持自定义）
- `quote`：行情数据
  - `usd`：当前价格（USD）
  - `usd_24h_change`：24小时涨跌额
  - `usd_24h_change_percentage`：24小时涨跌幅（百分比）
  - `usd_market_cap`：市值
  - `usd_24h_vol`：24小时交易量
  - `last_updated_at`：最后更新时间（Unix时间戳）

**鉴权**：通过Query参数 `api_key` 传递批次密钥（必需，不带批次参数为非法操作）

**缓存策略**：
- 缓存时间：5-10分钟（根据缓存配置）
- 缓存失效策略：时间到期后自动失效，下次请求时重新获取

**数据更新策略**：
- CoinGecko API免费版限制：每分钟最多50次请求

---

#### 3.1.3 获取K线图数据接口
**接口路径**：`GET /api/coins/{coin_id}/ohlc`

**功能描述**：获取指定币种的K线图数据（OHLC），支持多个时间维度

**请求参数**：
- `api_key`（必需）：批次密钥（Query参数）
- `coin_id`：币种ID（路径参数，如：bitcoin、ethereum）
- `vs_currency`（可选）：计价货币，默认USD（支持：usd, cny, eur等）
- `days`（必需）：时间维度，支持以下值：
  - `5m`：5分钟K线
  - `1h`：1小时K线
  - `1d`：1天K线

**响应格式**：按照CoinGecko API `/coins/{id}/ohlc` 标准格式返回
```json
[
  [1234567890000, 45000.50, 45100.00, 44900.00, 45050.00],
  [1234567900000, 45050.00, 45150.00, 45000.00, 45100.00]
]
```
每个数组元素格式：`[时间戳(毫秒), 开盘价, 最高价, 最低价, 收盘价]`

**说明**：
- 一次请求只返回一个币种、一个时间维度的K线数据
- 不同时间维度的数据需要分别请求

**鉴权**：通过Query参数 `api_key` 传递批次密钥（必需，不带批次参数为非法操作）

**缓存策略**：
- 5分钟K线：缓存5分钟
- 1小时K线：缓存60分钟
- 1天K线：缓存1440分钟（24小时）
- 根据时间维度自动调整缓存时间

---

#### 3.1.4 获取固件版本接口
**接口路径**：`GET /api/firmware/version`

**功能描述**：检查设备固件版本，支持自动升级

**请求参数**：
- `api_key`（必需）：批次密钥（Query参数）
- `current_version`：设备当前固件版本号（语义化版本号，如：1.0.0）
- `device_model`（可选）：设备型号

**响应字段**：
```json
{
  "latest_version": "1.2.0",
  "current_version": "1.0.0",
  "need_update": true,
  "download_url": "https://example.com/firmware/v1.2.0.bin",
  "release_notes": "修复了一些bug，新增了功能",
  "force_update": false
}
```

**业务逻辑**：
- 使用语义化版本号比较（如：1.0.0 < 1.2.0 < 2.0.0）
- 比较 `latest_version` 与 `current_version`
- 如果 `latest_version > current_version`，返回 `need_update: true` 并提供下载地址
- 版本号格式：语义化版本号（Semantic Versioning），如：1.0.0、1.2.3、2.0.0

**鉴权**：通过Query参数 `api_key` 传递批次密钥（必需，不带批次参数为非法操作）

**缓存策略**：
- 缓存时间：建议30分钟（固件版本变化频率低）
- 缓存失效策略：时间到期后自动失效，下次请求时重新获取

---

#### 3.1.5 配置信息接口
**接口路径**：`GET /api/config`

**功能描述**：获取设备配置信息

**请求参数**：
- `api_key`（必需）：批次密钥（Query参数）

**响应字段**：
```json
{
  "refresh_interval": 300,
  "default_currency": "USD",
  "display_coins": ["bitcoin", "ethereum"],
  "timezone": "Asia/Shanghai",
  "display_format": "standard"
}
```

**配置项说明**：
- `refresh_interval`：数据刷新间隔（秒），默认300秒（5分钟）
- `default_currency`：默认计价货币，默认USD
- `display_coins`：默认显示的币种列表（币种ID数组）
- `timezone`：时区设置
- `display_format`：显示格式配置

**说明**：配置接口采用简单设计，返回系统基本配置信息

**鉴权**：通过Query参数 `api_key` 传递批次密钥（必需，不带批次参数为非法操作）

**缓存策略**：
- 缓存时间：1分钟（配置信息可能需要快速更新）
- 缓存失效策略：时间到期后自动失效，下次请求时重新获取

---

#### 3.1.7 健康检查接口
**接口路径**：`GET /api/health`

**功能描述**：检查系统运行状态，用于监控和健康检查

**请求参数**：
- 无需参数（可选：`api_key`，如果提供则验证，不提供也可以访问）

**响应字段**：
```json
{
  "status": "ok",
  "timestamp": 1234567890,
  "version": "1.0.0",
  "database": "connected",
  "cache": "working"
}
```

**响应字段说明**：
- `status`：系统状态（"ok"表示正常，"error"表示异常）
- `timestamp`：当前时间戳（Unix时间戳）
- `version`：系统版本号
- `database`：数据库连接状态（"connected"表示已连接）
- `cache`：缓存状态（"working"表示正常工作）

**说明**：
- 健康检查接口用于监控系统运行状态
- 可以不需要批次密钥（可选）
- 用于负载均衡器、监控系统等检查服务是否正常

**缓存策略**：
- 不缓存，每次请求都返回实时状态

---

#### 3.1.6 获取币种Logo接口
**接口路径**：`GET /api/coins/{coin_id}/logo`

**功能描述**：获取指定币种的Logo图片

**请求参数**：
- `api_key`（必需）：批次密钥（Query参数，不带批次参数为非法操作）
- `coin_id`：币种ID（路径参数，如：bitcoin、ethereum）

**响应**：
- 返回本地存储的Logo图片文件
- Content-Type: image/png 或 image/jpeg

**说明**：
- Logo图片从CoinGecko抓取后存储到本地
- 存储路径：`/uploads/coins/{coin_id}.png` 或 `/uploads/coins/{coin_id}.jpg`
- 如果本地没有Logo，返回默认图片或404错误
- 支持自定义Logo，自定义Logo优先于CoinGecko抓取的Logo

**鉴权**：通过Query参数 `api_key` 传递批次密钥（必需，不带批次参数为非法操作）

**缓存策略**：
- 缓存时间：24小时（Logo变化频率极低）
- 缓存失效策略：时间到期后自动失效，下次请求时重新获取

---

#### 3.1.7 健康检查接口
**接口路径**：`GET /api/health`

**功能描述**：检查系统运行状态，用于监控和健康检查

**请求参数**：
- 无需参数（可选：`api_key`，如果提供则验证，不提供也可以访问）

**响应字段**：
```json
{
  "status": "ok",
  "timestamp": 1234567890,
  "version": "1.0.0",
  "database": "connected",
  "cache": "working"
}
```

**响应字段说明**：
- `status`：系统状态（"ok"表示正常，"error"表示异常）
- `timestamp`：当前时间戳（Unix时间戳）
- `version`：系统版本号
- `database`：数据库连接状态（"connected"表示已连接）
- `cache`：缓存状态（"working"表示正常工作）

**说明**：
- 健康检查接口用于监控系统运行状态
- 可以不需要批次密钥（可选）
- 用于负载均衡器、监控系统等检查服务是否正常

**缓存策略**：
- 不缓存，每次请求都返回实时状态

---

### 3.2 鉴权机制

#### 3.2.1 批次密钥管理
**需求描述**：支持多个批次的品牌设备，每个批次需要独立的安全密钥

**实现方案**：
- **密钥传递方式**：Query参数方式
  - 所有API请求必须携带 `?api_key={batch_key}` 参数
  - 示例：`GET /api/coins/list?api_key=abc123xyz`
- **密钥格式**：不限制格式，可以是任意字符串（建议使用随机字符串）
- **密钥长度**：不限制，建议32-64字符以保证安全性
- **密钥有效期**：不需要过期机制，密钥永久有效（除非手动禁用）

**数据库设计**：
- `batches` 表：批次信息
  - `id`：批次ID
  - `name`：批次名称
  - `api_key`：批次密钥（明文存储，或可选项加密存储）
  - `status`：状态（1:启用 0:禁用）
  - `created_at`：创建时间
  - `updated_at`：更新时间

**鉴权流程**：
1. 设备请求时在Query参数中携带批次密钥 `api_key`（必需参数）
2. **如果请求中不带 `api_key` 参数，直接返回400错误（非法操作）**
3. 系统从数据库查询该密钥对应的批次信息
4. 验证密钥是否存在且状态为启用
5. 验证通过后允许访问接口
6. 验证失败返回401错误

**重要说明**：
- **所有API接口都必须携带 `api_key` 参数，不带批次参数视为非法操作，返回400错误**
- 支持多个批次的产品，每个批次有独立的密钥
- 批次管理在后台进行，可以创建、编辑、禁用批次

---

### 3.3 API限流机制

#### 3.3.1 限流策略
**需求描述**：防止API被恶意请求，保护系统资源

**实现方案**：
- **限流维度**：按批次（api_key）进行限流
- **限流规则**：
  - 每个批次每分钟最多请求次数：可配置（建议：60次/分钟）
  - 限流时间窗口：滑动窗口或固定窗口
- **限流存储**：使用文件缓存记录每个批次的请求次数和时间戳

**限流响应**：
当超过限流阈值时，返回429错误：
```json
{
  "code": 429,
  "message": "Too many requests, please try again later",
  "data": null
}
```

**限流实现**：
1. 中间件拦截所有API请求
2. 提取请求中的 `api_key` 参数
3. 检查该批次在时间窗口内的请求次数
4. 如果超过限制，返回429错误
5. 如果未超过，更新请求计数并继续处理

---

### 3.4 错误处理规范

#### 3.4.1 统一错误响应格式
所有API接口的错误响应都遵循以下格式：
```json
{
  "code": 错误码,
  "message": "错误描述信息",
  "data": null
}
```

#### 3.4.2 错误码定义
| 错误码 | HTTP状态码 | 说明 | 示例 |
|--------|-----------|------|------|
| 200 | 200 | 成功 | 请求成功 |
| 400 | 400 | 请求参数错误 | 缺少必需参数（如：缺少api_key批次参数） |
| 401 | 401 | 鉴权失败 | API密钥无效或已禁用 |
| 404 | 404 | 资源不存在 | 币种ID不存在 |
| 429 | 429 | 请求过于频繁 | 超过限流阈值 |
| 500 | 500 | 服务器内部错误 | 数据库错误、API调用失败等 |

#### 3.4.3 错误响应示例

**400 - 缺少批次参数（非法操作）**：
```json
{
  "code": 400,
  "message": "Missing required parameter: api_key",
  "data": null
}
```

**401 - 鉴权失败**：
```json
{
  "code": 401,
  "message": "Invalid or missing API key",
  "data": null
}
```

**404 - 币种不存在**：
```json
{
  "code": 404,
  "message": "Coin not found: bitcoin",
  "data": null
}
```

**429 - 请求过于频繁**：
```json
{
  "code": 429,
  "message": "Too many requests, please try again later",
  "data": null
}
```

**500 - 服务器错误**：
```json
{
  "code": 500,
  "message": "Internal server error",
  "data": null
}
```

---

### 3.5 管理后台功能

**说明**：使用FastAdmin框架，框架自带用户登录、权限管理、后台管理界面等功能。

#### 3.5.1 批次管理
- **创建批次**：生成批次密钥（可手动输入或自动生成）
  - 批次名称：用于标识不同批次的产品
  - 批次密钥：用于API鉴权，必须唯一
  - 支持多个批次的产品管理
- **编辑批次**：修改批次名称等信息
- **禁用/启用批次**：控制批次访问权限
  - 禁用后，该批次的设备无法访问API接口
- **删除批次**：删除不需要的批次
- **查看批次列表**：列表展示所有批次信息
- **批次统计**：查看各批次的访问统计信息

#### 3.5.2 币种管理
- 同步币种列表：从CoinGecko API同步币种列表到本地数据库
  - 同步时自动抓取币种的Logo图片并存储到本地
  - Logo存储路径：`/uploads/coins/{coin_id}.png` 或 `/uploads/coins/{coin_id}.jpg`
  - 如果CoinGecko有Logo URL，自动下载并保存到本地
- 启用/禁用币种：控制哪些币种对外提供
- 设置币种排序：设置币种显示优先级
- **币种自定义功能**：
  - 自定义币种Logo：可以上传自定义Logo图片，覆盖CoinGecko的Logo
  - 自定义币种名称：可以修改币种的显示名称
  - 自定义币种描述：可以添加或修改币种的描述信息
- 币种列表管理：查看、搜索、筛选币种

#### 3.5.3 固件版本管理
- 上传固件文件：上传固件文件到服务器（标准存储路径：`/uploads/firmware/`）
- 设置固件版本号：配置语义化版本号（如：1.0.0）
- 配置强制更新策略：设置是否强制更新
- 管理固件下载地址：自动生成下载URL（相对路径或绝对URL）
- 版本列表管理：查看所有固件版本历史
- 固件文件管理：按照一般标准存储和管理固件文件

#### 3.5.4 系统配置管理
- 配置数据刷新间隔：设置缓存时间（秒）
- 配置默认币种：设置默认显示的币种列表
- 配置默认计价货币：设置默认货币（USD、CNY等）
- 配置时区：设置系统时区
- 其他系统参数配置：通过系统配置表管理

#### 3.5.5 数据同步管理（可选）
- 手动触发CoinGecko数据同步：手动刷新行情数据
- 查看同步日志：查看数据同步记录
- 监控API调用频率：监控CoinGecko API调用情况

#### 3.5.6 API限流管理
- 配置限流规则：设置每个批次的请求频率限制
- 查看限流统计：查看各批次的请求频率情况
- 限流日志：记录被限流的请求

---

## 4. 数据设计

### 4.1 数据库表设计（MySQL）

#### 4.1.1 批次表（batches）
```sql
CREATE TABLE batches (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    api_key VARCHAR(255) NOT NULL UNIQUE COMMENT '密钥格式不限制，长度不限制',
    status TINYINT(1) DEFAULT 1 COMMENT '1:启用 0:禁用',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='批次表';
```

#### 4.1.2 币种表（coins）
```sql
CREATE TABLE coins (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    coin_id VARCHAR(50) NOT NULL UNIQUE COMMENT 'CoinGecko的币种ID',
    symbol VARCHAR(20) NOT NULL,
    name VARCHAR(100) NOT NULL COMMENT '币种名称（支持自定义）',
    custom_name VARCHAR(100) DEFAULT NULL COMMENT '自定义名称（如果设置，优先使用）',
    description TEXT COMMENT '币种描述（支持自定义）',
    icon_url VARCHAR(255) DEFAULT NULL COMMENT 'CoinGecko的原始Logo URL',
    logo_path VARCHAR(255) DEFAULT NULL COMMENT '本地存储的Logo路径（/uploads/coins/{coin_id}.png）',
    custom_logo_path VARCHAR(255) DEFAULT NULL COMMENT '自定义Logo路径（如果设置，优先使用）',
    status TINYINT(1) DEFAULT 1 COMMENT '1:启用 0:禁用',
    sort_order INT(10) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY coin_id (coin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='币种表';
```

#### 4.1.3 行情数据缓存表（coin_quotes）
```sql
CREATE TABLE coin_quotes (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    coin_id VARCHAR(50) NOT NULL,
    price DECIMAL(20, 8) DEFAULT NULL,
    price_change_24h DECIMAL(20, 8) DEFAULT NULL,
    price_change_percentage_24h DECIMAL(10, 4) DEFAULT NULL,
    market_cap DECIMAL(20, 2) DEFAULT NULL,
    volume_24h DECIMAL(20, 2) DEFAULT NULL,
    currency VARCHAR(10) DEFAULT 'USD',
    cached_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_coin_quotes_coin_id (coin_id),
    KEY idx_coin_quotes_cached_at (cached_at),
    FOREIGN KEY (coin_id) REFERENCES coins(coin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='行情数据缓存表';
```

#### 4.1.4 固件版本表（firmware_versions）
```sql
CREATE TABLE firmware_versions (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    version VARCHAR(20) NOT NULL UNIQUE COMMENT '语义化版本号，如：1.0.0',
    file_path VARCHAR(255) DEFAULT NULL,
    file_size INT(10) DEFAULT NULL,
    download_url VARCHAR(255) DEFAULT NULL,
    release_notes TEXT,
    force_update TINYINT(1) DEFAULT 0 COMMENT '是否强制更新',
    status TINYINT(1) DEFAULT 1 COMMENT '1:启用 0:禁用',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY version (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='固件版本表';
```

#### 4.1.5 系统配置表（system_configs）
```sql
CREATE TABLE system_configs (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    config_key VARCHAR(50) NOT NULL UNIQUE,
    config_value TEXT,
    config_type VARCHAR(20) DEFAULT 'string' COMMENT 'string, int, json',
    description VARCHAR(255) DEFAULT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY config_key (config_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统配置表';
```

#### 4.1.6 K线数据缓存表（coin_ohlc_cache）
```sql
CREATE TABLE coin_ohlc_cache (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    coin_id VARCHAR(50) NOT NULL,
    vs_currency VARCHAR(10) DEFAULT 'USD',
    days VARCHAR(10) NOT NULL COMMENT '5m, 1h, 1d',
    ohlc_data TEXT NOT NULL COMMENT 'JSON格式存储K线数据',
    cached_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_coin_ohlc_cache_lookup (coin_id, vs_currency, days),
    KEY idx_coin_ohlc_cache_cached_at (cached_at),
    FOREIGN KEY (coin_id) REFERENCES coins(coin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='K线数据缓存表';
```

#### 4.1.7 访问日志表（access_logs）（可选）
```sql
CREATE TABLE access_logs (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    batch_id INT(10) UNSIGNED DEFAULT NULL,
    api_path VARCHAR(100) DEFAULT NULL,
    ip_address VARCHAR(50) DEFAULT NULL,
    request_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_access_logs_batch_id (batch_id),
    KEY idx_access_logs_request_time (request_time),
    FOREIGN KEY (batch_id) REFERENCES batches(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='访问日志表';
```

#### 4.1.8 限流记录表（rate_limit_logs）（可选）
```sql
CREATE TABLE rate_limit_logs (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    batch_id INT(10) UNSIGNED DEFAULT NULL,
    request_count INT(10) DEFAULT 1,
    time_window_start DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_rate_limit_logs_batch_time (batch_id, time_window_start),
    FOREIGN KEY (batch_id) REFERENCES batches(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='限流记录表';
```

---

## 5. 技术实现细节

### 5.1 CoinGecko API对接
**API端点选择**：
- `/coins/list` - 获取币种列表（用于同步币种数据）
- `/simple/price` - 获取价格（轻量级，适合获取单个币种价格）
- `/coins/{id}` - 获取详细信息（包含更多字段，如市值、交易量等，包含Logo URL）
- `/coins/{id}/ohlc` - 获取K线数据（OHLC格式，支持5分钟、1小时、1天等维度）

**Logo处理流程**：
1. 从CoinGecko API获取币种信息时，提取Logo URL
2. 下载Logo图片到本地存储：`/uploads/coins/{coin_id}.png` 或 `/uploads/coins/{coin_id}.jpg`
3. 在数据库中记录Logo存储路径
4. 接口返回时使用本地Logo URL，而不是CoinGecko的URL
5. 支持自定义Logo，如果设置了自定义Logo，优先使用自定义Logo

**API调用限制**：
- 免费版限制：每分钟最多50次请求
- 不需要API Key（免费版）
- 建议使用 `/simple/price` 接口获取价格，减少API调用次数

**错误处理和重试策略**：
- API调用失败时返回缓存的旧数据（如果有）
- 记录错误日志
- 可配置重试次数（建议3次）

### 5.2 缓存策略
- **文件缓存**：使用FastAdmin的文件缓存机制
- **所有接口都有缓存**（除了实时性要求极高的场景）
- **缓存时间**：
  - **币种列表接口**：1小时（变化频率低）
  - **行情数据接口**：5-10分钟（根据系统配置的refresh_interval）
  - **K线图接口**：
    - 5分钟K线：缓存5分钟
    - 1小时K线：缓存60分钟
    - 1天K线：缓存1440分钟（24小时）
  - **固件版本接口**：30分钟（变化频率低）
  - **配置信息接口**：1分钟（配置信息可能需要快速更新）
  - **Logo接口**：24小时（Logo变化频率极低）
- **缓存键设计**：
  - `coin_list`：币种列表
  - `coin_quote_{coin_id}_{currency}`：单个币种行情
  - `coin_ohlc_{coin_id}_{currency}_{days}`：K线数据（如：coin_ohlc_bitcoin_usd_5m）
  - `firmware_version`：固件版本信息
  - `system_config`：系统配置信息
  - `coin_logo_{coin_id}`：币种Logo
  - `rate_limit_{api_key}_{time_window}`：限流计数
- **缓存失效策略**：
  - 时间到期后自动失效
  - 下次请求时检查缓存，如果过期则重新从CoinGecko获取或从数据库读取

### 5.3 数据同步任务
**同步策略**：
- 采用被动同步策略：当设备请求数据时，检查缓存是否过期
- 如果缓存过期，则从CoinGecko API获取最新数据并更新缓存
- 避免主动定时任务，减少不必要的API调用

**同步流程**：
1. 设备请求行情数据或K线数据
2. 检查本地缓存是否存在且未过期
3. 如果缓存有效，直接返回缓存数据
4. 如果缓存过期或不存在，调用CoinGecko API获取数据
5. 更新缓存并返回数据

**K线数据缓存策略**：
- 5分钟K线：缓存5分钟（数据更新频率高）
- 1小时K线：缓存60分钟（数据更新频率中等）
- 1天K线：缓存1440分钟（24小时，数据更新频率低）
- 根据时间维度自动调整缓存时间，减少API调用

---

## 6. 已确认事项

### 6.1 接口格式
✅ **币种列表和行情数据**：按照CoinGecko API格式标准返回

### 6.2 鉴权机制
✅ **密钥传递方式**：Query参数（`?api_key={batch_key}`）
✅ **密钥格式**：不限制格式和长度
✅ **密钥有效期**：不需要过期机制

### 6.3 固件升级
✅ **版本号格式**：语义化版本号（如：1.0.0）
✅ **升级逻辑**：按正常流程，版本号比较后决定是否需要更新

### 6.4 数据同步策略
✅ **同步方式**：根据数据缓存细节，采用被动同步策略
✅ **缓存时间**：根据系统配置（建议5-10分钟）

### 6.5 技术栈
✅ **后端框架**：FastAdmin（https://github.com/fastadminnet/fastadmin）
✅ **数据库**：MySQL
✅ **缓存**：文件缓存
✅ **管理后台**：FastAdmin框架自带

---

## 7. 已确认的补充事项

### 7.1 接口细节
✅ **币种列表接口**：不需要分页和搜索功能，返回所有启用的币种，包含logo和描述信息
✅ **行情数据接口**：一次返回一个币种的行情数据，不支持批量
✅ **K线图接口**：新增K线图接口，支持5分钟、1小时、1天三个维度，需要缓存（CoinGecko API支持这些维度）
✅ **配置接口**：采用简单设计，返回基本配置信息，缓存1分钟
✅ **Logo接口**：新增Logo接口，返回本地存储的Logo图片

### 7.5 缓存策略
✅ **所有接口都有缓存**：
- 币种列表：1小时
- 行情数据：5-10分钟
- K线图：根据时间维度（5分钟/60分钟/1440分钟）
- 固件版本：30分钟
- 配置信息：1分钟
- Logo：24小时

### 7.6 批次管理
✅ **批次参数必需**：所有接口必须携带api_key批次参数，不带批次参数视为非法操作，返回400错误
✅ **支持多批次产品**：通过后台管理不同批次的产品，每个批次有独立的密钥

### 7.7 Logo和币种自定义
✅ **Logo存储**：从CoinGecko抓取Logo并存储到本地（`/uploads/coins/{coin_id}.png`）
✅ **Logo接口**：提供独立的Logo接口，返回本地存储的图片
✅ **币种自定义**：支持自定义币种的logo、名称、描述等信息

### 7.2 固件管理
✅ **固件存储**：按照一般标准存储，存储路径：`/uploads/firmware/`

### 7.3 错误处理
✅ **错误处理规范**：已定义统一的错误码和响应格式

### 7.4 API限流
✅ **API限流**：需要限流，按批次进行限流，可配置限流规则

---

## 8. API文档和健康检查

### 8.1 API文档
✅ **需要提供API文档**：
- 提供完整的API接口文档
- 包含所有接口的请求参数、响应格式、示例
- 方便设备厂家对接接口
- 可以使用FastAdmin自带的文档功能，或集成Swagger等文档工具

### 8.2 健康检查接口
✅ **需要提供健康检查接口**（`/api/health`）：
- 用于监控系统运行状态
- 检查数据库连接、缓存状态等
- 用于负载均衡器、监控系统等检查服务是否正常
- 可以不需要批次密钥（可选）

### 8.3 日志记录（可选）
- 是否需要记录API访问日志？
- 日志保留时间？
- 用于问题排查和统计分析

---

## 9. 开发计划

### Phase 1: 基础框架搭建
- [ ] FastAdmin环境搭建
- [ ] MySQL数据库初始化
- [ ] 基础表结构创建

### Phase 2: 核心功能开发
- [ ] CoinGecko API对接
- [ ] 鉴权机制实现
- [ ] API限流机制实现
- [ ] 错误处理规范实现
- [ ] 健康检查接口
- [ ] 币种列表接口
- [ ] 行情数据接口
- [ ] K线图接口
- [ ] Logo接口
- [ ] 缓存机制实现

### Phase 3: 固件管理功能
- [ ] 固件版本接口
- [ ] 固件上传功能
- [ ] 版本比较逻辑

### Phase 4: 管理后台开发
- [ ] 批次管理
- [ ] 币种管理
- [ ] 固件管理
- [ ] 系统配置

### Phase 5: 测试和优化
- [ ] 接口测试
- [ ] 性能优化
- [ ] API文档编写
- [ ] 部署文档编写

---

## 10. 风险评估

1. **CoinGecko API限制**：免费版可能有调用频率限制，需要合理设计缓存策略
2. **数据准确性**：依赖第三方API，需要处理API异常情况
3. **固件存储**：如果固件文件较大，需要考虑存储空间和下载速度
4. **安全性**：批次密钥需要安全存储和传输

---

## 11. 总结

### 11.1 核心功能确认
- ✅ 币种列表接口：按照CoinGecko API格式，不需要分页和搜索，包含logo和描述
- ✅ 行情数据接口：按照CoinGecko API格式，一次返回一个币种
- ✅ K线图接口：支持5分钟、1小时、1天三个维度，需要缓存（CoinGecko API支持）
- ✅ Logo接口：返回本地存储的Logo图片
- ✅ 固件版本接口：语义化版本号管理
- ✅ 配置信息接口：简单设计，返回基本配置，缓存1分钟
- ✅ 鉴权机制：Query参数传递密钥（必需），格式不限制，不带批次参数为非法操作
- ✅ 批次管理：支持多批次产品管理，每个批次独立密钥
- ✅ API限流：按批次限流，可配置
- ✅ 错误处理：统一的错误码和响应格式
- ✅ Logo存储：从CoinGecko抓取并存储到本地
- ✅ 币种自定义：支持自定义logo、名称、描述
- ✅ 所有接口都有缓存：根据数据特性设置不同的缓存时间
- ✅ 技术栈：FastAdmin + MySQL + 文件缓存

### 11.2 开发准备
1. 搭建FastAdmin开发环境
2. 初始化MySQL数据库
3. 创建数据库表结构
4. 对接CoinGecko API
5. 实现鉴权中间件
6. 开发API接口
7. 开发管理后台功能

### 11.3 注意事项
1. **CoinGecko API限制**：免费版每分钟最多50次请求，需要合理使用缓存
2. **数据准确性**：依赖第三方API，需要处理API异常情况
3. **固件存储**：需要考虑固件文件存储空间和下载速度
4. **安全性**：批次密钥需要妥善保管，建议在管理后台生成随机密钥

---

**PRD文档版本**：v1.2  
**最后更新**：根据用户补充信息更新（缓存策略、批次管理、Logo处理、币种自定义等）  
**状态**：需求已明确，可开始开发

---

## 12. 更新日志

### v1.2 (最新)
- ✅ 明确所有接口的缓存策略（配置接口1分钟，其他接口都有缓存）
- ✅ 明确批次参数必需，不带批次参数为非法操作（返回400错误）
- ✅ 新增Logo接口，支持返回本地存储的Logo图片
- ✅ 添加Logo存储功能，从CoinGecko抓取并存储到本地
- ✅ 添加币种自定义功能（logo、名称、描述）
- ✅ 更新币种列表接口，包含logo和描述字段
- ✅ 更新数据库表结构，支持币种自定义字段

### v1.1
- ✅ 新增K线图接口，支持5分钟、1小时、1天三个维度
- ✅ 添加API限流机制
- ✅ 添加错误处理规范
- ✅ 明确固件存储路径

### v1.0
- ✅ 初始版本，定义核心功能需求

