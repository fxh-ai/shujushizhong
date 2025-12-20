# Git 提交指南

## 当前项目状态

### ✅ 已完成的工作

1. **FastAdmin 环境搭建**
   - MySQL 数据库配置完成
   - 安装锁文件已创建
   - 后台入口文件：`LUgeswcuTm.php`

2. **数据库初始化**
   - Docker MySQL 服务运行中
   - 数据库表结构已创建（7个核心表）

3. **前端资源构建**
   - npm 依赖已安装
   - 静态资源已构建（576个文件）

4. **项目文档**
   - PRD 文档完整
   - 安装文档完整
   - 各种说明文档

### 📝 建议提交的内容

#### 核心代码和配置
- `web/application/` - 应用代码
- `web/public/` - 公共资源（但不包括 `node_modules`）
- `web/composer.json` - PHP 依赖配置
- `web/package.json` - 前端依赖配置
- `web/.env.example` - 环境配置示例（如果有）

#### 项目文档
- `dev-docs/` - 开发文档目录
- `README.md` - 项目说明
- `DOCKER.md` - Docker 使用说明
- 各种安装和配置文档

#### 配置文件
- `docker-compose.yml` - Docker 配置
- `docker/mysql/init/` - MySQL 初始化脚本
- `.gitignore` - Git 忽略规则

### ❌ 不应提交的内容

- `node_modules/` - 前端依赖（很大，通过 `npm install` 安装）
- `vendor/` - PHP 依赖（通过 `composer install` 安装）
- `web/runtime/` - 运行时文件
- `web/.env` - 环境配置文件（包含敏感信息）
- `web/public/uploads/` - 上传文件目录
- 各种日志和缓存文件

## 提交命令示例

```bash
# 查看当前状态
git status

# 添加所有文件（.gitignore 会自动排除不需要的文件）
git add .

# 提交
git commit -m "feat: 初始化FastAdmin项目

- 完成FastAdmin环境搭建（MySQL版本）
- 创建项目数据库表结构（7个核心表）
- 安装并构建前端静态资源
- 添加Docker MySQL支持
- 完善项目文档"

# 推送到远程仓库（如果有）
git push origin main
```

## 注意事项

1. **环境配置文件**：确保 `.env` 文件不会被提交，包含数据库密码等敏感信息
2. **依赖管理**：`node_modules/` 和 `vendor/` 不应提交，团队成员通过 `npm install` 和 `composer install` 安装
3. **构建产物**：`public/assets/libs/` 可以提交（已构建的静态资源），也可以不提交（通过 `npm run build` 构建）
4. **数据库初始化**：SQL 脚本已提交，团队成员可以通过脚本初始化数据库

## 团队成员克隆项目后的操作

```bash
# 1. 克隆项目
git clone <repository-url>
cd shuzishizhong

# 2. 安装PHP依赖
cd web
composer install

# 3. 安装前端依赖
npm install

# 4. 构建静态资源
npm run build

# 5. 启动Docker MySQL
cd ..
docker-compose up -d mysql

# 6. 配置环境变量
cp web/.env.example web/.env
# 编辑 web/.env 文件，配置数据库连接信息

# 7. 初始化数据库（如果需要）
# 使用已提供的SQL脚本或通过FastAdmin安装程序
```

