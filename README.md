# 数据时钟项目

## 项目简介

数据时钟项目是在普通时钟硬件上显示加密货币行情数据的智能设备。通过对接CoinGecko API获取实时加密货币行情，为时钟设备提供数据支持。

## 技术栈

- **后端框架**：FastAdmin (基于ThinkPHP)
- **数据库**：MySQL
- **缓存**：文件缓存
- **外部依赖**：CoinGecko API（加密货币数据源）

## 项目结构

```
shuzishizhong/
├── dev-docs/          # 开发文档
│   ├── PRD文档.md     # 产品需求文档
│   └── 原始提示词.md
├── web/               # FastAdmin项目目录
│   ├── application/   # 应用目录
│   ├── public/        # 公共访问目录
│   └── ...
└── README.md
```

## 环境要求

- PHP >= 7.4.0
- Composer
- MySQL >= 5.7 或 MariaDB >= 10.2
- PDO MySQL扩展

## 安装步骤

### 1. FastAdmin环境搭建 ✅

FastAdmin已经成功搭建完成：

```bash
cd web
composer install  # 依赖已安装
```

**配置说明**：
- 数据库类型：MySQL
- 配置文件：`web/.env`
- 表前缀：`fa_`

### 2. 启动开发服务器

```bash
cd web
php -S localhost:8000 -t public
```

访问：http://localhost:8000

### 3. 下一步开发计划

- [ ] MySQL数据库初始化
- [ ] 基础表结构创建
- [ ] CoinGecko API对接
- [ ] 鉴权机制实现
- [ ] API接口开发

## API接口列表

1. **健康检查接口**：`GET /api/health`
2. **币种列表接口**：`GET /api/coins/list`
3. **行情数据接口**：`GET /api/coins/{coin_id}/quote`
4. **K线图接口**：`GET /api/coins/{coin_id}/ohlc`
5. **Logo接口**：`GET /api/coins/{coin_id}/logo`
6. **固件版本接口**：`GET /api/firmware/version`
7. **配置信息接口**：`GET /api/config`

详细API文档请参考：`dev-docs/PRD文档.md`

## 开发状态

- ✅ Phase 1: 基础框架搭建 - FastAdmin环境搭建完成
- ⏳ Phase 2: 核心功能开发 - 进行中
- ⏳ Phase 3: 固件管理功能
- ⏳ Phase 4: 管理后台开发
- ⏳ Phase 5: 测试和优化

## 注意事项

1. 所有API接口必须携带 `api_key` 批次参数
2. 不带批次参数视为非法操作，返回400错误
3. 使用MySQL数据库
4. 所有接口都有缓存，根据数据特性设置不同的缓存时间

## 相关文档

- [PRD文档](./dev-docs/PRD文档.md)
- [FastAdmin官方文档](https://doc.fastadmin.net)

