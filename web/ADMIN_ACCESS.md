# FastAdmin 后台访问说明

## 后台入口地址

FastAdmin安装时会生成一个随机名称的后台入口文件，以提高安全性。

**当前后台入口文件**：`LUgeswcuTm.php`

## 访问方式

### 方式1：直接访问后台入口（推荐）
```
http://localhost:8000/LUgeswcuTm.php
```
会自动跳转到登录页面。

### 方式2：直接访问登录页面
```
http://localhost:8000/LUgeswcuTm.php/index/login
```

### 方式3：访问后台首页（需要登录）
```
http://localhost:8000/LUgeswcuTm.php/index/index
```

## 登录信息

- **用户名**：admin
- **密码**：qexF3dkAER

⚠️ **重要**：请登录后立即修改默认密码！

## 安全提示

1. **不要将后台入口文件名改为 `admin.php`**
   - FastAdmin使用随机文件名是为了隐藏后台入口，提高安全性
   - 建议保持随机文件名

2. **修改默认密码**
   - 登录后立即修改管理员密码
   - 使用强密码（至少8位，包含大小写字母、数字和特殊字符）

3. **定期更新**
   - 定期检查FastAdmin和ThinkPHP的更新
   - 及时修复安全漏洞

## 常见问题

### Q: 访问后台入口看不到登录页面？
A: 请确保：
1. PHP服务器正在运行
2. 访问正确的URL（包含随机文件名）
3. 数据库连接正常
4. 安装锁文件存在（`application/admin/command/Install/install.lock`）

### Q: 如何修改后台入口文件名？
A: 不建议修改。如果必须修改，请：
1. 重命名 `public/LUgeswcuTm.php` 为新文件名
2. 更新所有引用该文件的地方（如配置文件、视图文件等）

### Q: 忘记后台入口文件名怎么办？
A: 可以通过以下方式查找：
```bash
cd web
ls -la public/*.php
```
查找除了 `index.php` 和 `router.php` 之外的PHP文件。

