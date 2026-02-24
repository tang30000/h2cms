<?php
/**
 * H2CMS 教程文章种子 — 覆盖 H2PHP 框架所有功能
 * 运行：php _seed_tutorials.php
 */
$pdo = new PDO('mysql:host=localhost;dbname=h2cms;charset=utf8mb4', 'root', 'usbw');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("SET NAMES utf8mb4");

$cat = $pdo->query("SELECT id FROM categories WHERE slug='tutorial'")->fetch(PDO::FETCH_ASSOC);
$catId = $cat ? $cat['id'] : null;
if (!$catId) {
    $pdo->exec("INSERT INTO categories (name, slug) VALUES ('教程','tutorial')");
    $catId = $pdo->lastInsertId();
}

$tutorials = [

// ─── 第1课 ───
[
'title' => '第1课：目录即路由 — H2PHP 的核心设计',
'slug'  => 'tutorial-01-routing',
'excerpt' => 'URL 直接映射到文件系统，无需配置路由文件。支持位置参数、安全校验、三种 URL 格式。',
'body'  => '
<h2>什么是目录即路由？</h2>
<p>H2PHP 最核心的设计理念：<strong>URL 路径 = 文件路径 + 方法名</strong>，零配置，零学习成本。</p>

<h2>URL 解析规则</h2>
<p>URL 格式为 <code>/a/b/c/d1/d2/d3...</code>，各段含义如下：</p>
<table border="1" cellpadding="8" style="border-collapse:collapse; margin:1rem 0">
    <tr><th>段</th><th>含义</th><th>默认值</th></tr>
    <tr><td><code>a</code></td><td>目录（app/ 下的子目录）</td><td>home</td></tr>
    <tr><td><code>b</code></td><td>文件名（.php 不含扩展名）</td><td>index</td></tr>
    <tr><td><code>c</code></td><td>方法名（public 方法）</td><td>index</td></tr>
    <tr><td><code>d...</code></td><td>位置参数（可多个）</td><td>无</td></tr>
</table>

<h3>完整映射示例</h3>
<pre><code>URL                          控制器文件                 调用
/                         → app/home/index.php       → index()
/post/index/view/3        → app/post/index.php       → view(3)
/admin/posts/edit/5       → app/admin/posts.php      → edit(5)
/user/login               → app/user/login.php       → index()
/article/list/show/1/20   → app/article/list.php     → show(1, 20)
</code></pre>

<h3>多个位置参数</h3>
<p>第 4 段及以后全部作为方法参数传入，支持字符串和数字：</p>
<pre><code>// URL: /article/list/show/tech/1/20
public function show(string $category, int $page, int $size): void
{
    // $category = "tech", $page = 1, $size = 20
}
</code></pre>

<h3>安全校验</h3>
<ul>
    <li><strong>a/b/c</strong> 段：只允许字母、数字、下划线 <code>/^[a-zA-Z0-9_]+$/</code>，防止路径穿越攻击</li>
    <li><strong>d 参数</strong>段：额外允许连字符和小数点 <code>/^[a-zA-Z0-9_\\-.]+$/</code>，支持 slug 和版本号</li>
    <li>不合法的参数直接返回 <strong>400 错误</strong></li>
</ul>

<h3>三种 URL 格式</h3>
<p>Router 会按优先级依次尝试：</p>
<ol>
    <li><strong>Apache .htaccess</strong>：通过 <code>$_GET[\'_route\']</code>（生产推荐）</li>
    <li><strong>Query String</strong>：<code>?user/login</code> 风格（通过 <code>QUERY_STRING</code>）</li>
    <li><strong>Path Info</strong>：<code>/user/login</code> 风格（通过 <code>REQUEST_URI</code>）</li>
</ol>
<p>PHP 内置服务器和 Apache 环境都能无缝使用。</p>

<h3>默认值配置</h3>
<pre><code>// config/config.php
\'default\' => [
    \'a\' => \'home\',   // 默认目录
    \'b\' => \'index\',  // 默认文件
    \'c\' => \'index\',  // 默认方法
],
</code></pre>

<h3>在 H2CMS 中的应用</h3>
<ul>
    <li>首页 <code>/</code> → <code>app/home/index.php → index()</code></li>
    <li>文章 <code>/post/index/view/3</code> → <code>app/post/index.php → view(3)</code></li>
    <li>后台 <code>/admin/posts/edit/5</code> → <code>app/admin/posts.php → edit(5)</code></li>
</ul>
'],

// ─── 第2课 ───
[
'title' => '第2课：控制器与视图 — MVC 架构基础',
'slug'  => 'tutorial-02-mvc',
'excerpt' => '控制器继承 Core 获得全部能力，视图自动/手动查找，支持 set/setMulti/render/json/redirect/abort。',
'body'  => '
<h2>控制器基础</h2>
<p>所有控制器文件中的类名必须是 <code>main</code>，继承 <code>\Lib\Core</code> 基类：</p>
<pre><code>class main extends \Lib\Core
{
    public function index(): void
    {
        $this->set(\'title\', \'首页\');
        $this->render();
    }
}
</code></pre>

<h2>传递变量到视图</h2>

<h3>单个变量</h3>
<pre><code>$this->set(\'title\', \'文章列表\');
$this->set(\'posts\', $posts);
</code></pre>

<h3>批量传递</h3>
<pre><code>$this->setMulti([
    \'title\'    => \'文章详情\',
    \'post\'     => $post,
    \'comments\' => $comments,
    \'category\' => $category,
]);
</code></pre>

<h2>渲染模板</h2>

<h3>自动查找（推荐）</h3>
<pre><code>$this->render();
// 自动按控制器路径查找，优先级：
//   1. views/a/b/c.html  （精确到方法）
//   2. views/a/b.html    （控制器级 fallback）
</code></pre>

<h3>手动指定模板</h3>
<pre><code>$this->render(\'shared/error\');     // → views/shared/error.html
$this->render(\'admin/posts/form\'); // → views/admin/posts/form.html
</code></pre>

<h3>自定义扩展名</h3>
<pre><code>$this->render(\'api/result\', \'.xml\');  // → views/api/result.xml
$this->render(null, \'.php\');           // 使用 .php 扩展名
</code></pre>

<h2>其他响应方式</h2>

<h3>JSON 响应（API 接口）</h3>
<pre><code>$this->json([\'users\' => $list]);           // 200
$this->json([\'error\' => \'not found\'], 404); // 404

// 语义化快捷方式
$this->success($data);                // {code:0, msg:"ok", data:...}
$this->success($data, \'创建成功\');    // 自定义消息
$this->fail(\'参数错误\');             // {code:-1, msg:"参数错误", data:null}
$this->fail(\'未找到\', 404);          // 自定义错误码
</code></pre>

<h3>重定向</h3>
<pre><code>$this->redirect(\'/user/login\');            // 302 临时跳转
$this->redirect(\'/new-url\', 301);          // 301 永久跳转
$this->redirect(\'https://example.com\');    // 外部 URL
// 子目录部署时，/开头路径自动拼接 base_path
</code></pre>

<h3>终止并显示错误</h3>
<pre><code>$this->abort(404, \'页面不存在\');  // 显示 404 错误页
$this->abort(403, \'无权访问\');   // 显示 403 错误页
$this->abort(500);              // 不传消息使用默认
</code></pre>

<h2>before/after 钩子</h2>
<pre><code>class main extends \Lib\Core
{
    // 跳过 before() 的方法列表
    protected array $skipBefore = [\'index\', \'list\'];

    // 方法执行前自动调用（通常用于鉴权）
    public function before(): void
    {
        if (empty($_SESSION[\'user\'])) {
            $this->redirect(\'/user/login\');
        }
    }

    // 方法执行后自动调用
    public function after(): void
    {
        // 记录访问日志等
    }
}
</code></pre>

<h3>在 H2CMS 中的应用</h3>
<ul>
    <li>首页用 <code>setMulti()</code> 批量传递文章、分类、分页数据</li>
    <li>后台控制器用 <code>before()</code> 钩子检查管理员登录</li>
    <li>文章详情找不到时用 <code>$this-&gt;abort(404)</code></li>
    <li>后台编辑用 <code>$this-&gt;render(\'admin/posts/form\')</code> 复用创建/编辑表单</li>
</ul>
'],

// ─── 第3课 ───
[
'title' => '第3课：链式查询 — 优雅的数据库操作',
'slug'  => 'tutorial-03-db-query',
'excerpt' => '轻量级 PDO 封装，支持 table/where/order/limit/fields/fetch/fetchAll/count/value/insert/update/delete 全链式操作。',
'body'  => '
<h2>基础 CRUD</h2>

<h3>查询多条</h3>
<pre><code>$posts = $this->db->table(\'posts\')
    ->where(\'status=?\', [\'published\'])
    ->order(\'created_at DESC\')
    ->limit(10, 0)   // 取10条，偏移0
    ->fetchAll();
</code></pre>

<h3>查询单条</h3>
<pre><code>$post = $this->db->table(\'posts\')
    ->where(\'id=?\', [$id])
    ->fetch();  // 返回关联数组或 false
</code></pre>

<h3>指定字段</h3>
<pre><code>$names = $this->db->table(\'users\')
    ->fields(\'id, username, email\')
    ->fetchAll();
</code></pre>

<h3>获取单个值</h3>
<pre><code>$title = $this->db->table(\'posts\')
    ->fields(\'title\')
    ->where(\'id=?\', [$id])
    ->value();  // 返回标量值
</code></pre>

<h3>统计数量</h3>
<pre><code>$total = $this->db->table(\'posts\')
    ->where(\'status=?\', [\'published\'])
    ->count();
</code></pre>

<h3>插入</h3>
<pre><code>$id = $this->db->table(\'posts\')->insert([
    \'title\'  => \'新文章\',
    \'body\'   => \'内容...\',
    \'status\' => \'draft\',
]);
// 返回自增 ID
</code></pre>

<h3>更新</h3>
<pre><code>$affected = $this->db->table(\'posts\')
    ->where(\'id=?\', [$id])
    ->update([
        \'title\'  => \'修改后\',
        \'status\' => \'published\',
    ]);
// 返回受影响行数
</code></pre>

<h3>删除</h3>
<pre><code>$this->db->table(\'comments\')
    ->where(\'post_id=?\', [$id])
    ->delete();
</code></pre>

<h2>查询缓存</h2>
<pre><code>// 缓存 300 秒
$categories = $this->db->table(\'categories\')
    ->cache(300)
    ->fetchAll();

// 强制刷新缓存（写操作后更新热点数据）
$this->db->table(\'categories\')
    ->cache(300, true)
    ->fetchAll();
</code></pre>

<h2>原生 SQL</h2>
<pre><code>// 查询
$rows = $this->db->query(
    \'SELECT p.*, c.name as cat_name FROM posts p LEFT JOIN categories c ON p.category_id = c.id WHERE p.status=?\',
    [\'published\']
);

// 执行（INSERT/UPDATE/DELETE）
$this->db->exec(\'UPDATE posts SET views = views + 1 WHERE id = ?\', [$id]);
</code></pre>

<h2>ORM 关联查询</h2>
<pre><code>// 一对多：获取用户的所有文章
$posts = $this->db->hasMany(\'posts\', \'user_id\', $userId)
    ->order(\'id DESC\')->limit(10)->fetchAll();

// 多对一：获取文章的作者
$user = $this->db->belongsTo(\'users\', \'id\', $post[\'user_id\'])->fetch();

// 多对多：获取文章的标签（通过中间表）
$tags = $this->db->belongsToMany(\'tags\', \'post_tag\', \'post_id\', \'tag_id\', $postId)
    ->order(\'tags.name\')->fetchAll();
</code></pre>

<h3>在 H2CMS 中的应用</h3>
<ul>
    <li>首页用 <code>where + order + limit</code> 分页查询文章</li>
    <li>分类列表用 <code>cache(300)</code> 缓存避免重复查库</li>
    <li>文章详情用 <code>fetch()</code> 获取单条，<code>count()</code> 统计评论数</li>
</ul>
'],

// ─── 第4课 ───
[
'title' => '第4课：表单验证 — 安全的用户输入处理',
'slug'  => 'tutorial-04-validation',
'excerpt' => '内置验证器支持 required/email/min_len/max_len/numeric/integer/confirmed/unique/in 等规则。',
'body'  => '
<h2>基本用法</h2>
<pre><code>$v = $this->validate($_POST, [
    \'title\'    => \'required|max_len:200\',
    \'email\'    => \'required|email|unique:users,email\',
    \'password\' => \'required|min_len:6|confirmed\',
    \'status\'   => \'required|in:draft,published\',
    \'age\'      => \'integer\',
    \'price\'    => \'numeric\',
], [
    \'title\'    => \'标题\',
    \'email\'    => \'邮箱\',
    \'password\' => \'密码\',
]);

if ($v->fails()) {
    $errors = $v->errors();        // 全部错误（关联数组）
    $first  = $v->firstError();    // 第一条错误字符串
    $this->flash(\'error\', $first);
    $this->redirect(\'/form\');
}
</code></pre>

<h2>全部验证规则</h2>
<table border="1" cellpadding="8" style="border-collapse:collapse; margin:1rem 0">
    <tr><th>规则</th><th>说明</th><th>示例</th></tr>
    <tr><td>required</td><td>必填</td><td><code>\'title\' => \'required\'</code></td></tr>
    <tr><td>email</td><td>邮箱格式</td><td><code>\'email\' => \'email\'</code></td></tr>
    <tr><td>min_len:N</td><td>最小长度</td><td><code>\'password\' => \'min_len:6\'</code></td></tr>
    <tr><td>max_len:N</td><td>最大长度</td><td><code>\'title\' => \'max_len:200\'</code></td></tr>
    <tr><td>numeric</td><td>数字（含小数）</td><td><code>\'price\' => \'numeric\'</code></td></tr>
    <tr><td>integer</td><td>整数</td><td><code>\'qty\' => \'integer\'</code></td></tr>
    <tr><td>confirmed</td><td>确认字段一致</td><td><code>\'password\' => \'confirmed\'</code>（需 password_confirmation）</td></tr>
    <tr><td>unique:t,c</td><td>数据库唯一</td><td><code>\'email\' => \'unique:users,email\'</code></td></tr>
    <tr><td>in:a,b,c</td><td>枚举值</td><td><code>\'role\' => \'in:admin,editor,user\'</code></td></tr>
</table>

<h3>第三个参数：字段显示名</h3>
<p>设置后，错误消息会使用中文名称而非字段名：</p>
<pre><code>// 不设置: "title 不能为空"
// 设置后: "标题 不能为空"
</code></pre>

<h3>在 H2CMS 中的应用</h3>
<ul>
    <li>登录表单验证 username + password 必填</li>
    <li>文章编辑验证 title 必填 + max_len:200</li>
    <li>评论提交验证 author_name 必填 + body 长度 3~1000</li>
</ul>
'],

// ─── 第5课 ───
[
'title' => '第5课：CSRF 防护 — 防止跨站请求伪造',
'slug'  => 'tutorial-05-csrf',
'excerpt' => '三步完成 CSRF 防护：csrfField() 输出隐藏字段、csrfToken() 获取 Token、csrfVerify() 验证。',
'body'  => '
<h2>原理</h2>
<p>CSRF 攻击让用户在不知情时提交恶意请求。H2PHP 的防护流程：</p>
<ol>
    <li>服务端生成随机 Token 存入 Session</li>
    <li>表单中包含 Token 隐藏字段</li>
    <li>提交时服务端验证 Token 是否匹配</li>
    <li>验证失败直接返回 403</li>
</ol>

<h2>完整用法</h2>

<h3>步骤 1：控制器生成 Token</h3>
<pre><code>// 方式一：传递整个隐藏字段 HTML
$this->set(\'csrfField\', $this->csrfField());

// 方式二：只传 token 值（用于 AJAX）
$this->set(\'csrfToken\', $this->csrfToken());
</code></pre>

<h3>步骤 2：视图中使用</h3>
<pre><code>&lt;!-- 表单方式 --&gt;
&lt;form method="POST" action="/post/index/comment/3"&gt;
    &lt;?= $csrfField ?&gt;
    &lt;textarea name="body"&gt;&lt;/textarea&gt;
    &lt;button type="submit"&gt;提交&lt;/button&gt;
&lt;/form&gt;

&lt;!-- AJAX 方式 --&gt;
&lt;meta name="csrf-token" content="&lt;?= $csrfToken ?&gt;"&gt;
&lt;script&gt;
fetch(url, {
    method: "POST",
    headers: {"X-CSRF-Token": document.querySelector("meta[name=csrf-token]").content},
    body: data
});
&lt;/script&gt;
</code></pre>

<h3>步骤 3：验证</h3>
<pre><code>public function comment(int $postId): void
{
    $this->csrfVerify();  // 失败自动 403，不需 if 判断
    // 通过后继续处理表单数据...
}
</code></pre>

<h3>在 H2CMS 中的应用</h3>
<p>所有 POST 表单都使用了 CSRF 防护：登录、评论、文章编辑、分类管理、页面管理。</p>
'],

// ─── 第6课 ───
[
'title' => '第6课：中间件 — 可复用的请求处理管道',
'slug'  => 'tutorial-06-middleware',
'excerpt' => '洋葱模型中间件管道，支持全局中间件和控制器级中间件，用于鉴权、日志、CORS 等。',
'body'  => '
<h2>中间件类型</h2>
<ul>
    <li><strong>全局中间件</strong>：所有请求都经过，在 config 中配置</li>
    <li><strong>控制器级中间件</strong>：仅指定控制器的请求经过</li>
</ul>

<h2>创建中间件</h2>
<p>中间件文件放在 <code>app/middleware/</code> 目录，类名与文件名一致：</p>
<pre><code>// app/middleware/AdminAuth.php
class AdminAuth
{
    public function handle(callable $next): void
    {
        if (empty($_SESSION[\'user\'])) {
            header(\'Location: /user/login\');
            exit;
        }

        if ($_SESSION[\'user\'][\'role\'] !== \'admin\') {
            http_response_code(403);
            echo \'无权访问\';
            exit;
        }

        $next();  // 验证通过，继续执行下一个中间件或控制器
    }
}
</code></pre>

<h2>在控制器中声明</h2>
<pre><code>class main extends \Lib\Core
{
    // 列出需要经过的中间件（按顺序执行）
    protected array $middleware = [\'AdminAuth\', \'LogRequest\'];

    public function index(): void
    {
        // 到达这里说明已通过所有中间件
    }
}
</code></pre>

<h2>洋葱模型</h2>
<p>多个中间件按声明顺序嵌套，像洋葱一样层层包裹：</p>
<pre><code>AdminAuth → LogRequest → 控制器方法 → LogRequest后处理 → AdminAuth后处理
</code></pre>

<h2>中间件 vs before() 钩子</h2>
<table border="1" cellpadding="8" style="border-collapse:collapse; margin:1rem 0">
    <tr><th></th><th>中间件</th><th>before()</th></tr>
    <tr><td>作用域</td><td>整个控制器</td><td>可通过 skipBefore 排除方法</td></tr>
    <tr><td>复用性</td><td>可跨控制器复用</td><td>仅当前控制器</td></tr>
    <tr><td>嵌套</td><td>洋葱模型</td><td>单层</td></tr>
</table>

<h3>在 H2CMS 中的应用</h3>
<p>后台所有控制器（dashboard/posts/categories/pages/comments）都声明了 <code>AdminAuth</code> 中间件。</p>
'],

// ─── 第7课 ───
[
'title' => '第7课：文件上传 — 安全的文件处理',
'slug'  => 'tutorial-07-upload',
'excerpt' => '框架内置 Upload 类，支持链式配置大小限制、文件类型、命名策略，一行代码完成上传。',
'body'  => '
<h2>基本用法</h2>
<pre><code>$file = $this->upload(\'featured_image\', \'static/uploads\');

if ($file->fails()) {
    $this->flash(\'error\', \'上传失败: \' . $file->error());
    $this->redirect(\'/admin/posts/create\');
    return;
}

$path = $file->path();  // 存入数据库的相对路径，如 \'static/uploads/1708xxx.jpg\'
</code></pre>

<h2>链式配置</h2>
<pre><code>$file = $this->upload(\'photo\', \'static/uploads\')
    ->maxSize(5 * 1024 * 1024)          // 最大 5MB
    ->allowTypes([\'jpg\', \'png\', \'webp\']) // 限制类型
    ->rename(\'timestamp\');               // 用时间戳重命名
</code></pre>

<h2>参数说明</h2>
<table border="1" cellpadding="8" style="border-collapse:collapse; margin:1rem 0">
    <tr><th>方法</th><th>说明</th><th>默认值</th></tr>
    <tr><td>maxSize(bytes)</td><td>文件大小上限</td><td>2MB</td></tr>
    <tr><td>allowTypes([])</td><td>允许的扩展名</td><td>[\'jpg\',\'jpeg\',\'png\',\'gif\',\'webp\']</td></tr>
    <tr><td>rename(\'策略\')</td><td>重命名策略</td><td>原文件名</td></tr>
</table>

<h2>表单 HTML</h2>
<pre><code>&lt;form method="POST" enctype="multipart/form-data"&gt;
    &lt;?= $csrfField ?&gt;
    &lt;input type="file" name="featured_image"&gt;
    &lt;button type="submit"&gt;上传&lt;/button&gt;
&lt;/form&gt;
</code></pre>

<h3>在 H2CMS 中的应用</h3>
<p>文章编辑页支持上传特色图片，存储在 <code>static/uploads/</code> 目录。</p>
'],

// ─── 第8课 ───
[
'title' => '第8课：软删除与回收站 — 数据保护机制',
'slug'  => 'tutorial-08-soft-delete',
'excerpt' => '通过 deleted_at 字段实现软删除，支持 softDelete/restore/onlyTrashed/withTrashed 完整流程。',
'body'  => '
<h2>前提</h2>
<p>数据库表需包含 <code>deleted_at DATETIME DEFAULT NULL</code> 字段。</p>

<h2>启用软删除</h2>
<pre><code>// 查询自动过滤已删除记录
$posts = $this->db->table(\'posts\')
    ->softDeletes()   // 启用
    ->order(\'id DESC\')
    ->fetchAll();     // 自动加 WHERE deleted_at IS NULL
</code></pre>

<h2>删除（软删除）</h2>
<pre><code>$this->db->table(\'posts\')
    ->softDeletes()
    ->where(\'id=?\', [$id])
    ->softDelete();   // 设置 deleted_at = NOW()
</code></pre>

<h2>恢复</h2>
<pre><code>$this->db->table(\'posts\')
    ->softDeletes()
    ->where(\'id=?\', [$id])
    ->restore();      // 清除 deleted_at = NULL
</code></pre>

<h2>查看回收站</h2>
<pre><code>// 只查已删除的
$trashed = $this->db->table(\'posts\')
    ->softDeletes()
    ->onlyTrashed()   // WHERE deleted_at IS NOT NULL
    ->fetchAll();

// 查全部（含已删除的）
$all = $this->db->table(\'posts\')
    ->softDeletes()
    ->withTrashed()   // 不加 deleted_at 条件
    ->fetchAll();
</code></pre>

<h2>彻底删除</h2>
<pre><code>// 不经过 softDeletes()，直接物理删除
$this->db->table(\'posts\')
    ->where(\'id=?\', [$id])
    ->delete();
</code></pre>

<h3>在 H2CMS 中的应用</h3>
<p>后台文章管理拥有完整流程：列表 → 软删除 → 回收站 → 恢复/彻底删除。前台自动过滤已删除文章。</p>
'],

// ─── 第9课 ───
[
'title' => '第9课：事务 — 保证数据一致性',
'slug'  => 'tutorial-09-transaction',
'excerpt' => '闭包事务自动 commit/rollback，也支持手动 beginTransaction/commit/rollback。',
'body'  => '
<h2>闭包事务（推荐）</h2>
<pre><code>$this->db->transaction(function($db) use ($id) {
    $db->table(\'comments\')
       ->where(\'post_id=?\', [$id])
       ->delete();

    $db->table(\'posts\')
       ->where(\'id=?\', [$id])
       ->delete();
});
// 闭包内任何异常自动回滚并重新抛出
</code></pre>

<h2>带返回值的事务</h2>
<pre><code>$orderId = $this->db->transaction(function($db) use ($data) {
    $id = $db->table(\'orders\')->insert($data);
    $db->table(\'stock\')->where(\'id=?\', [$data[\'product_id\']])
       ->update([\'qty\' => $newQty]);
    return $id;  // 事务成功后返回
});
</code></pre>

<h2>手动事务</h2>
<pre><code>$this->db->beginTransaction();
try {
    $this->db->table(\'accounts\')->where(\'id=?\', [1])
        ->update([\'balance\' => $newBalance1]);
    $this->db->table(\'accounts\')->where(\'id=?\', [2])
        ->update([\'balance\' => $newBalance2]);
    $this->db->commit();
} catch (\Exception $e) {
    $this->db->rollback();
    throw $e;
}
</code></pre>

<h2>获取原始 PDO</h2>
<pre><code>$pdo = $this->db->pdo(); // 获取底层 PDO 对象，用于高级操作
</code></pre>

<h3>在 H2CMS 中的应用</h3>
<p>后台彻底删除文章时使用事务：先删评论、再删文章，保证一致性。</p>
'],

// ─── 第10课 ───
[
'title' => '第10课：事件系统 — 解耦业务逻辑',
'slug'  => 'tutorial-10-events',
'excerpt' => '通过 on() 注册监听、fire() 触发事件，实现核心逻辑与附加操作的解耦。',
'body'  => '
<h2>注册监听</h2>
<pre><code>$this->on(\'post.created\', function($data) {
    // 发送邮件通知
    $this->mail($admin, \'新文章\', "标题: {$data[\'title\']}");
});

$this->on(\'user.registered\', function($user) {
    // 写入日志
    $this->log(\'info\', \'新用户注册\', [\'id\' => $user[\'id\']]);
    // 发送欢迎邮件
    $this->mail($user[\'email\'], \'欢迎\', \'...\');
});
</code></pre>

<h2>触发事件</h2>
<pre><code>// 文章被浏览
$this->fire(\'post.viewed\', $post);

// 文章创建成功
$this->fire(\'post.created\', [\'id\' => $id, \'title\' => $title]);

// 用户注册成功
$this->fire(\'user.registered\', $user);
</code></pre>

<h2>一个事件多个监听</h2>
<pre><code>$this->on(\'order.paid\', function($order) { /* 扣库存 */ });
$this->on(\'order.paid\', function($order) { /* 发邮件 */ });
$this->on(\'order.paid\', function($order) { /* 写日志 */ });

$this->fire(\'order.paid\', $order); // 三个监听器都会执行
</code></pre>

<h3>在 H2CMS 中的应用</h3>
<ul>
    <li>文章详情页：<code>fire(\'post.viewed\', $post)</code></li>
    <li>可扩展用于浏览量统计、推荐算法等</li>
</ul>
'],

// ─── 第11课 ───
[
'title' => '第11课：日志系统 — 记录运行轨迹',
'slug'  => 'tutorial-11-logging',
'excerpt' => '支持 debug/info/warning/error 四个级别，按日期分割文件存储，附加上下文数据。',
'body'  => '
<h2>使用方式</h2>
<pre><code>$this->log(\'info\',    \'用户登录成功\', [\'user_id\' => 5, \'ip\' => $ip]);
$this->log(\'warning\', \'登录失败次数过多\', [\'username\' => $name, \'attempts\' => 5]);
$this->log(\'error\',   \'支付接口异常\', [\'order_id\' => 100, \'error\' => $msg]);
$this->log(\'debug\',   \'SQL 查询\', [\'sql\' => $sql, \'time\' => \'0.05s\']);
</code></pre>

<h2>日志级别</h2>
<table border="1" cellpadding="8" style="border-collapse:collapse; margin:1rem 0">
    <tr><th>级别</th><th>用途</th></tr>
    <tr><td>debug</td><td>开发调试信息</td></tr>
    <tr><td>info</td><td>正常操作记录</td></tr>
    <tr><td>warning</td><td>需要注意但不影响运行</td></tr>
    <tr><td>error</td><td>错误，需要处理</td></tr>
</table>

<h2>日志文件</h2>
<pre><code>logs/
├── 2024-02-25.log     ← 按日期自动分割
├── 2024-02-24.log
└── ...

文件内容格式：
[2024-02-25 10:30:15] INFO: 用户登录成功 {"user_id":5,"ip":"127.0.0.1"}
[2024-02-25 10:30:20] WARNING: 登录失败次数过多 {"username":"hacker","attempts":5}
</code></pre>

<h3>在 H2CMS 中的应用</h3>
<p>用户登录/登出、文章 CRUD、评论提交都写入了日志，便于排查问题和安全审计。</p>
'],

// ─── 第12课 ───
[
'title' => '第12课：Flash 消息 — 一次性操作提示',
'slug'  => 'tutorial-12-flash',
'excerpt' => '存入 Session 的一次性提示，支持多类型（success/error/warning），显示后自动清除。',
'body'  => '
<h2>设置消息</h2>
<pre><code>$this->flash(\'success\', \'文章已保存\');
$this->flash(\'error\',   \'操作失败\');
$this->flash(\'warning\', \'余额不足\');
$this->redirect(\'/admin/posts\');
</code></pre>

<h2>在控制器中读取</h2>
<pre><code>// 读取单个类型
$msg = $this->getFlash(\'success\');  // 返回消息或 null

// 读取全部
$all = $this->getAllFlash();  // [\'success\' => \'...\', \'error\' => \'...\']
</code></pre>

<h2>在布局模板中自动渲染（推荐）</h2>
<pre><code>&lt;?php if (!empty($_SESSION[\'_flash\'])): ?&gt;
    &lt;?php $flashes = $_SESSION[\'_flash\']; unset($_SESSION[\'_flash\']); ?&gt;
    &lt;?php foreach ($flashes as $type =&gt; $msg): ?&gt;
    &lt;div class="alert alert-&lt;?= $type ?&gt;"&gt;
        &lt;?= htmlspecialchars($msg) ?&gt;
    &lt;/div&gt;
    &lt;?php endforeach; ?&gt;
&lt;?php endif; ?&gt;
</code></pre>
<p>放在布局文件中，所有页面统一显示提示消息。</p>

<h3>在 H2CMS 中的应用</h3>
<p>前后台布局统一渲染 Flash 消息。登录成功/失败、文章 CRUD、评论审核都通过 Flash 反馈。</p>
'],

// ─── 第13课 ───
[
'title' => '第13课：布局与模板 — 页面结构复用',
'slug'  => 'tutorial-13-layout',
'excerpt' => '布局通过 $content 注入页面内容，Partial 实现导航、页脚等片段复用，支持传参。',
'body'  => '
<h2>使用布局</h2>
<pre><code>// 控制器中指定布局
$this->layout(\'front\');   // → views/_layouts/front.html
$this->layout(\'admin\');   // → views/_layouts/admin.html
$this->layout(null);      // 不使用布局（如 API 接口、错误页）
</code></pre>

<h2>布局文件结构</h2>
<pre><code>&lt;!-- views/_layouts/front.html --&gt;
&lt;!DOCTYPE html&gt;
&lt;html&gt;
&lt;head&gt;
    &lt;title&gt;&lt;?= $title ?? \'H2CMS\' ?&gt;&lt;/title&gt;
    &lt;link rel="stylesheet" href="&lt;?= $basePath ?&gt;/static/css/front.css"&gt;
&lt;/head&gt;
&lt;body&gt;
    &lt;?php $this-&gt;partial(\'front-nav\') ?&gt;

    &lt;main&gt;&lt;?= $content ?&gt;&lt;/main&gt;

    &lt;?php $this-&gt;partial(\'footer\') ?&gt;
&lt;/body&gt;
&lt;/html&gt;
</code></pre>
<p><code>$content</code> 变量由框架自动注入，包含视图渲染的内容。</p>

<h2>局部模板（Partial）</h2>
<pre><code>// 基本引入
$this->partial(\'nav\');         // → views/_partials/nav.html
$this->partial(\'footer\');      // → views/_partials/footer.html
$this->partial(\'admin-nav\');   // → views/_partials/admin-nav.html

// 传递局部变量
$this->partial(\'sidebar\', [
    \'categories\' => $cats,
    \'tags\'       => $tags,
]);
// sidebar.html 中可直接使用 $categories 和 $tags
</code></pre>

<h2>自动注入 basePath</h2>
<p>框架自动向所有视图注入 <code>$basePath</code> 变量，用于生成正确的 URL：</p>
<pre><code>&lt;a href="&lt;?= $basePath ?&gt;/user/login"&gt;登录&lt;/a&gt;
&lt;link href="&lt;?= $basePath ?&gt;/static/css/front.css"&gt;
&lt;img src="&lt;?= $basePath ?&gt;/static/uploads/photo.jpg"&gt;
</code></pre>
<p>根目录部署时 <code>$basePath</code> 为空串，子目录部署时为 <code>/h2php</code> 之类。</p>

<h3>在 H2CMS 中的应用</h3>
<ul>
    <li>前台用 <code>front.html</code> 布局（顶部导航 + 主内容 + 页脚）</li>
    <li>后台用 <code>admin.html</code> 布局（侧边栏 + 顶栏 + 内容区 + Flash 消息）</li>
    <li>导航、页脚都是独立的 Partial 文件</li>
</ul>
'],

// ─── 第14课 ───
[
'title' => '第14课：自动时间戳 — 省心的时间管理',
'slug'  => 'tutorial-14-timestamps',
'excerpt' => 'timestamps() 自动为 insert 填充 created_at + updated_at，为 update 刷新 updated_at。',
'body'  => '
<h2>启用</h2>
<pre><code>// 在链式查询中加 ->timestamps() 即可
$this->db->table(\'posts\')->timestamps()->insert([...]);
$this->db->table(\'posts\')->timestamps()->where(\'id=?\', [$id])->update([...]);
</code></pre>

<h2>INSERT 行为</h2>
<pre><code>$this->db->table(\'posts\')->timestamps()->insert([
    \'title\' => \'新文章\',
    \'body\'  => \'内容\',
]);
// 自动追加：
//   created_at = \'2024-02-25 10:30:00\'
//   updated_at = \'2024-02-25 10:30:00\'
// 如果手动传了 created_at，以手动值为准
</code></pre>

<h2>UPDATE 行为</h2>
<pre><code>$this->db->table(\'posts\')->timestamps()
    ->where(\'id=?\', [$id])
    ->update([\'title\' => \'修改后\']);
// 自动追加：
//   updated_at = \'2024-02-25 11:00:00\'
// 不影响 created_at
</code></pre>

<h2>前提条件</h2>
<p>表需要有以下字段：</p>
<pre><code>CREATE TABLE posts (
    ...
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ...
);
</code></pre>

<h3>在 H2CMS 中的应用</h3>
<p>posts、pages、comments 的创建和更新都使用了 <code>timestamps()</code>。</p>
'],

// ─── 第15课 ───
[
'title' => '第15课：配置与部署 — 从开发到生产',
'slug'  => 'tutorial-15-config-deploy',
'excerpt' => '配置系统支持全局+本地覆盖，部署支持 Apache/Nginx/PHP内置服务器，含子目录和分页等功能。',
'body'  => '
<h2>配置系统</h2>

<h3>全局配置</h3>
<pre><code>// config/config.php
return [
    \'db\' => [
        \'dsn\'  => \'mysql:host=localhost;dbname=h2cms;charset=utf8mb4\',
        \'user\' => \'root\',
        \'password\' => \'\',
    ],
    \'default\' => [\'a\' => \'home\', \'b\' => \'index\', \'c\' => \'index\'],
    \'base_path\' => \'\',    // 子目录部署时设置
    \'debug\' => true,
    \'cache\' => [\'driver\' => \'file\', \'path\' => ROOT.\'/cache\'],
];
</code></pre>

<h3>本地覆盖（不提交 Git）</h3>
<pre><code>// config/config.local.php
return [
    \'db\' => [\'password\' => \'my_local_pwd\'],
    \'debug\' => true,
];
// 通过 array_replace_recursive 深度合并
</code></pre>

<h2>部署</h2>

<h3>Apache</h3>
<p>将项目放入 Web 根目录，<code>.htaccess</code> 已包含 URL 重写规则，开箱即用。</p>

<h3>Nginx</h3>
<pre><code>location / {
    try_files $uri $uri/ /index.php?_route=$uri&amp;$args;
}
</code></pre>

<h3>PHP 内置服务器</h3>
<pre><code>php -S localhost:8080 index.php</code></pre>
<p>路由同时支持 <code>/path</code> 和 <code>?path</code> 格式。</p>

<h3>子目录部署</h3>
<pre><code>// 如部署在 http://localhost/myapp/
\'base_path\' => \'/myapp\',

// 控制器中生成 URL
$url = $this->url(\'/user/login\');  // → /myapp/user/login

// redirect 自动拼接
$this->redirect(\'/admin/dashboard\');  // → /myapp/admin/dashboard

// 视图中使用 $basePath
&lt;a href="&lt;?= $basePath ?&gt;/user/login"&gt;登录&lt;/a&gt;
</code></pre>

<h2>分页功能</h2>
<pre><code>$total = $this->db->table(\'posts\')->count();
$pager = $this->paginate($total, $page, 10, \'/home/index\');

// 返回值结构
[
    \'page\'    => 当前页码,
    \'pages\'   => 总页数,
    \'total\'   => 总记录数,
    \'offset\'  => SQL OFFSET 值,
    \'prevUrl\' => 上一页URL或null,
    \'nextUrl\' => 下一页URL或null,
    \'links\'   => [
        [\'page\' => 1, \'url\' => \'...\', \'active\' => true],
        [\'page\' => 2, \'url\' => \'...\', \'active\' => false],
        ...
    ],
]

// 在视图中渲染分页
&lt;?php foreach ($pager[\'links\'] as $link): ?&gt;
    &lt;a href="&lt;?= $link[\'url\'] ?&gt;"
       class="&lt;?= $link[\'active\'] ? \'active\' : \'\' ?&gt;"&gt;
        &lt;?= $link[\'page\'] ?&gt;
    &lt;/a&gt;
&lt;?php endforeach; ?&gt;
</code></pre>

<h2>队列（异步任务）</h2>
<pre><code>// 推入队列
$this->queue(\'SendWelcomeEmail\', [\'user_id\' => 5]);
$this->queue(\'SendReminder\', [\'user_id\' => 5], 3600); // 1小时后

// Job 文件：app/jobs/SendWelcomeEmail.php
class SendWelcomeEmail {
    public function handle(array $payload): void {
        // 发送邮件...
    }
}

// 启动 Worker
php h2 queue:work
</code></pre>

<h2>邮件</h2>
<pre><code>$this->mail(\'user@example.com\', \'注册成功\', \'&lt;h1&gt;欢迎！&lt;/h1&gt;\');
</code></pre>

<h3>在 H2CMS 中的应用</h3>
<p>首页使用分页查询文章，config.local.php 存放本地数据库密码（已 gitignore），后台操作可通过队列异步发邮件。</p>
'],

]; // end tutorials

// INSERT or UPDATE
$insertStmt = $pdo->prepare("INSERT INTO posts (title, slug, body, excerpt, category_id, user_id, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 1, 'published', NOW(), NOW())");
$updateStmt = $pdo->prepare("UPDATE posts SET title=?, body=?, excerpt=? WHERE slug=?");

$inserted = 0;
$updated = 0;
foreach ($tutorials as $t) {
    $exists = $pdo->prepare("SELECT id FROM posts WHERE slug=?");
    $exists->execute([$t['slug']]);
    if ($exists->fetch()) {
        $updateStmt->execute([$t['title'], $t['body'], $t['excerpt'], $t['slug']]);
        $updated++;
        echo "UPDATE: {$t['title']}\n";
    } else {
        $insertStmt->execute([$t['title'], $t['slug'], $t['body'], $t['excerpt'], $catId]);
        $inserted++;
        echo "INSERT: {$t['title']}\n";
    }
}

echo "\nDone! Inserted: {$inserted}, Updated: {$updated}\n";
