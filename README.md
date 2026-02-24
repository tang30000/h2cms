# H2CMS — H2PHP 框架完整示例应用

> 基于 [H2PHP](https://github.com/tang30000/h2php) 轻量级 PHP MVC 框架构建的 WordPress 风格内容管理系统。
>
> 本项目同时作为 H2PHP 框架的**实战教程**，演示框架全部 26 项功能的真实用法。

---

## ✨ 功能特性

| 模块 | 功能 |
|------|------|
| 前台 | 文章列表、分页、搜索、分类筛选、详情、评论 |
| 后台 | 仪表盘、文章 CRUD、分类管理、页面管理、评论审核 |
| 用户 | 登录、注册、退出、角色权限 |
| 安全 | CSRF 防护、表单验证、密码哈希、AdminAuth 中间件 |
| 高级 | 图片上传、软删除/回收站、事务、事件、查询缓存、日志 |

## 🚀 快速开始

### 1. 克隆项目

```bash
git clone https://github.com/tang30000/h2cms.git
cd h2cms
```

### 2. 创建数据库

```bash
# 创建 MySQL 数据库并执行迁移
php _init_db.php
```

> 需要先修改 `config/config.php` 中的数据库连接信息。

### 3. 启动开发服务器

```bash
php -S localhost:8081 index.php
```

访问 http://localhost:8081 查看前台，默认管理员：`admin` / `admin123`

### 4. 生产部署（Apache/Nginx）

项目已包含 `.htaccess`（Apache）和 `nginx.conf.example`（Nginx）配置文件，
将项目目录指向 Web 根即可。

---

## 📁 项目结构

```
h2cms/
├── index.php              # 入口（3行）
├── config/
│   └── config.php         # 数据库、缓存、队列、邮件配置
├── migrations/
│   └── 001_create_tables.php  # 6张表 + 种子数据
├── app/                   # 控制器（目录即路由）
│   ├── home/index.php     # 前台首页
│   ├── post/index.php     # 文章详情 + 评论
│   ├── page/index.php     # 静态页面
│   ├── user/login.php     # 登录/注册/登出
│   ├── admin/dashboard.php
│   ├── admin/posts.php    # 文章 CRUD + 上传 + 软删除
│   ├── admin/categories.php
│   ├── admin/pages.php
│   ├── admin/comments.php
│   └── middleware/AdminAuth.php
├── views/                 # 视图模板
│   ├── _layouts/          # 前后台布局
│   ├── _partials/         # 导航、页脚
│   └── ...                # 各控制器对应视图
├── static/
│   └── css/               # 前台 + 后台样式
└── lib/                   # H2PHP 框架核心
```

---

## 📖 框架教程

本项目演示了 H2PHP 的 **26 项功能**，以下按模块讲解：

### 1. 目录即路由

H2PHP 使用「目录结构 = URL 路由」的设计，无需配置路由文件。

```
访问 URL                控制器文件             调用方法
/                    → app/home/index.php   → index()
/post/index/view/3   → app/post/index.php   → view(3)
/admin/posts/edit/5  → app/admin/posts.php  → edit(5)
/user/login          → app/user/login.php   → index()
```

路由规则：`/a/b/c/d` → `app/{a}/{b}.php` → `c($d)`

### 2. 控制器 & 视图

控制器继承 `\Lib\Core`，获得数据库、验证、缓存等全部能力：

```php
class main extends \Lib\Core
{
    public function index(): void
    {
        $posts = $this->db->table('posts')->order('created_at DESC')->fetchAll();
        $this->layout('front');           // 使用 views/_layouts/front.html
        $this->set('posts', $posts);      // 传变量到视图
        $this->render();                  // 渲染 views/home/index/index.html
    }
}
```

### 3. 链式查询

```php
// 基础查询
$posts = $this->db->table('posts')
    ->where('status=?', ['published'])
    ->order('created_at DESC')
    ->limit(10, 0)
    ->fetchAll();

// 软删除过滤
$posts = $this->db->table('posts')->softDeletes()->fetchAll();

// 查询缓存（60秒）
$categories = $this->db->table('categories')->cache(300)->fetchAll();

// 聚合
$total = $this->db->table('posts')->count();
```

### 4. 表单验证

```php
$v = $this->validate($_POST, [
    'title'    => 'required|max_len:200',
    'email'    => 'required|email|unique:users,email',
    'password' => 'required|min_len:6|confirmed',
], [
    'title' => '标题', 'email' => '邮箱', 'password' => '密码',
]);

if ($v->fails()) {
    $this->flash('error', $v->firstError());
    $this->redirect('/admin/posts/create');
    return;
}
```

### 5. CSRF 防护

```php
// 控制器中
$this->set('csrfField', $this->csrfField());  // 生成隐藏字段
$this->csrfVerify();                            // 验证提交

// 视图中
<form method="POST">
    <?= $csrfField ?>
    ...
</form>
```

### 6. 中间件

```php
// app/middleware/AdminAuth.php
class AdminAuth
{
    public function handle(callable $next): void
    {
        if (empty($_SESSION['user'])) {
            header('Location: /user/login');
            exit;
        }
        $next();
    }
}

// 控制器中声明
class main extends \Lib\Core
{
    protected array $middleware = ['AdminAuth'];  // 自动加载并执行
}
```

### 7. 文件上传

```php
$file = $this->upload('featured_image', 'static/uploads')
    ->maxSize(5 * 1024 * 1024)
    ->allowTypes(['jpg', 'jpeg', 'png', 'gif', 'webp']);

if ($file->fails()) {
    $this->flash('error', '上传失败: ' . $file->error());
} else {
    $path = $file->path();  // 'static/uploads/xxx.jpg'
}
```

### 8. 软删除 & 回收站

```php
// 软删除（设置 deleted_at）
$this->db->table('posts')->softDeletes()->where('id=?', [$id])->softDelete();

// 恢复
$this->db->table('posts')->softDeletes()->where('id=?', [$id])->restore();

// 查看已删除的
$trashed = $this->db->table('posts')->softDeletes()->onlyTrashed()->fetchAll();

// 彻底删除
$this->db->table('posts')->where('id=?', [$id])->delete();
```

### 9. 事务

```php
$this->db->transaction(function($db) use ($id) {
    $db->table('comments')->where('post_id=?', [$id])->delete();
    $db->table('posts')->where('id=?', [$id])->delete();
});
```

### 10. 事件系统

```php
// 触发事件
$this->fire('post.viewed', $post);
$this->fire('post.created', ['id' => $id, 'title' => $title]);

// 监听（在 config 或控制器中注册）
$this->on('post.created', function($data) {
    // 发邮件通知、写日志等
});
```

### 11. 日志

```php
$this->log('info',    '用户登录', ['user_id' => $id, 'ip' => $ip]);
$this->log('warning', '登录失败', ['username' => $name]);
$this->log('error',   '支付异常', ['order_id' => $oid]);
```

### 12. Flash 消息

```php
// 设置
$this->flash('success', '文章已创建');
$this->flash('error', '操作失败');

// 视图中渲染
<?php if (!empty($_SESSION['_flash'])): ?>
    <div class="alert alert-<?= $flash['key'] ?>">
        <?= htmlspecialchars($flash['msg']) ?>
    </div>
<?php endif; ?>
```

### 13. 布局 & 模板

```php
$this->layout('front');   // 使用 views/_layouts/front.html
$this->partial('nav');    // 引入 views/_partials/nav.html
```

**布局文件** (`views/_layouts/front.html`):
```html
<header><?php $this->partial('front-nav') ?></header>
<main><?= $content ?></main>
<footer><?php $this->partial('footer') ?></footer>
```

### 14. 自动时间戳

```php
// insert 自动填充 created_at
$this->db->table('posts')->timestamps()->insert([...]);

// update 自动更新 updated_at
$this->db->table('posts')->timestamps()->where('id=?', [$id])->update([...]);
```

### 15. 配置系统

```php
// config/config.php — 全局配置
// config/config.local.php — 本地覆盖（不提交 Git）
// 支持 array_replace_recursive 深度合并
```

---

## 📊 覆盖的框架功能

| # | 功能 | H2CMS 中的使用位置 |
|---|------|-------------------|
| 1 | 目录路由 | 全部控制器 |
| 2 | MVC 架构 | 全部 app/ + views/ |
| 3 | 链式 DB 查询 | 首页、后台 CRUD |
| 4 | 表单验证 | 登录、注册、文章、评论 |
| 5 | CSRF 防护 | 所有表单 |
| 6 | 中间件 | AdminAuth |
| 7 | 文件上传 | 文章特色图片 |
| 8 | 软删除 | 文章回收站 |
| 9 | 事务 | 彻底删除文章+评论 |
| 10 | 事件 | post.viewed / post.created |
| 11 | 日志 | 登录、CRUD 操作 |
| 12 | Flash 消息 | 全部操作反馈 |
| 13 | 布局/模板 | front.html / admin.html |
| 14 | 时间戳 | insert/update 自动填充 |
| 15 | 查询缓存 | 首页分类、文章列表 |
| 16 | 配置系统 | config.php |
| 17 | Session | 用户登录状态 |
| 18 | 分页 | 首页、后台列表 |
| 19 | 数据库迁移 | migrations/ |
| 20 | Partial 模板 | 导航、页脚 |
| 21 | 搜索 | 首页关键词搜索 |
| 22 | 密码哈希 | 注册/登录 |
| 23 | 请求对象 | $this->request->get() / ip() |
| 24 | skipBefore | 登录控制器跳过鉴权 |
| 25 | 静态文件 | StaticFile::serve() |
| 26 | Bootstrap | Bootstrap::run() |

---

## 📝 License

MIT — 随意使用、修改、分发。
