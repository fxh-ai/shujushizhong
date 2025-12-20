# FastAdmin 登录页面检查指南

## 当前状态

✅ **已创建标准后台入口文件**: `admin.php`
✅ **数据库连接正常**: MySQL Docker服务运行中
✅ **管理员账户已创建**: admin / qexF3dkAER
✅ **静态资源文件存在**: CSS、JS、图片文件完整

## 访问方式

### 方式1：使用标准入口文件（推荐）
```
http://localhost:8000/admin.php
```
会自动跳转到登录页面。

### 方式2：直接访问登录页面
```
http://localhost:8000/admin.php/index/login
```

### 方式3：使用随机入口文件
```
http://localhost:8000/LUgeswcuTm.php/index/login
```

## 登录信息

- **用户名**: `admin`
- **密码**: `qexF3dkAER`

⚠️ **重要**: 登录后请立即修改密码！

## 如果登录页面显示不正常

### 1. 检查PHP服务器是否运行
```bash
cd web
php -S localhost:8000 -t public
```

### 2. 检查浏览器控制台
- 按 F12 打开开发者工具
- 查看 Console 标签是否有JavaScript错误
- 查看 Network 标签，检查CSS/JS文件是否正常加载（状态码应该是200）

### 3. 检查常见问题

#### 问题1: 页面空白
- 检查PHP错误日志: `runtime/log/`
- 检查浏览器控制台是否有错误
- 确认PHP版本 >= 7.4

#### 问题2: 样式错乱
- 检查 `/assets/css/backend.css` 是否正常加载
- 检查浏览器控制台是否有404错误
- 清除浏览器缓存后重试

#### 问题3: JavaScript不工作
- 检查 `/assets/js/require.min.js` 是否正常加载
- 检查 `/assets/js/require-backend.js` 是否正常加载
- 查看浏览器控制台的JavaScript错误

#### 问题4: 验证码不显示
- 检查 `/index.php?s=/captcha` 是否可访问
- 检查 `runtime` 目录是否有写权限

#### 问题5: 登录失败
- 确认用户名和密码正确
- 检查数据库连接是否正常
- 查看 `runtime/log/` 中的错误日志

### 4. 检查文件权限
```bash
# 确保runtime目录可写
chmod -R 777 runtime
chmod -R 777 public/uploads
```

### 5. 检查数据库
```bash
# 检查MySQL容器是否运行
docker ps | grep mysql

# 检查数据库连接
docker exec mysql_dev mysql -uroot -proot123456 fastadmin -e "SELECT username, email FROM fa_admin WHERE username='admin';"
```

## 需要帮助？

如果登录页面仍然有问题，请提供以下信息：

1. **浏览器控制台错误**（F12 -> Console）
2. **网络请求状态**（F12 -> Network，查看哪些文件返回404或500）
3. **页面截图**（显示具体问题）
4. **PHP错误日志**（`runtime/log/` 目录下的最新日志文件）

## 快速测试命令

```bash
# 启动PHP服务器
cd web
php -S localhost:8000 -t public

# 在另一个终端测试访问
curl -I http://localhost:8000/admin.php/index/login

# 检查静态资源
curl -I http://localhost:8000/assets/css/backend.css
curl -I http://localhost:8000/assets/js/require.min.js
```

