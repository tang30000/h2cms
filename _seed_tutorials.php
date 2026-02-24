<?php
/**
 * 向 H2CMS 数据库插入 H2PHP 框架教程文章
 * 运行：php _seed_tutorials.php
 */
$pdo = new PDO('mysql:host=localhost;dbname=h2cms;charset=utf8mb4', 'root', 'usbw');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("SET NAMES utf8mb4");

// 确保"教程"分类存在
$cat = $pdo->query("SELECT id FROM categories WHERE slug='tutorial'")->fetch(PDO::FETCH_ASSOC);
$catId = $cat ? $cat['id'] : null;
if (!$catId) {
    $pdo->exec("INSERT INTO categories (name, slug) VALUES ('教程','tutorial')");
    $catId = $pdo->lastInsertId();
}

$tutorials = [
    [
        'title' => '第1课：目录即路由 — H2PHP 的核心设计',
        'slug'  => 'tutorial-01-routing',
        'body'  => '<h2>什么是目录即路由？</h2>
<p>H2PHP 采用「目录结构 = URL 路由」的设计，<strong>无需配置路由文件</strong>。URL 的每一段直接映射到文件系统：</p>

<pre><code>访问 URL                 控制器文件              调用方法
/                     → app/home/index.php    → index()
/post/index/view/3    → app/post/index.php    → view(3)
/admin/posts/edit/5   → app/admin/posts.php   → edit(5)
/user/login           → app/user/login.php    → index()
</code></pre>

<h3>路由规则</h3>
<p>URL 格式为 <code>/a/b/c/d</code>，其中：</p>
<ul>
    <li><code>a</code> — 目录名（对应 app/ 下的子目录）</li>
    <li><code>b</code> — 文件名（对应 .php 文件，不含扩展名）</li>
    <li><code>c</code> — 方法名（控制器中的 public 方法）</li>
    <li><code>d</code> — 参数（可多个，如 /article/list/show/1/20）</li>
</ul>

<h3>安全校验</h3>
<p>路由的 a/b/c 段只允许字母、数字、下划线，防止路径穿越攻击。</p>

<h3>在 H2CMS 中的应用</h3>
<p>本 CMS 的前台首页对应 <code>app/home/index.php</code>，文章详情对应 <code>app/post/index.php</code> 的 <code>view()</code> 方法，后台管理对应 <code>app/admin/</code> 目录下的各个控制器。</p>',
        'excerpt' => 'H2PHP 采用目录结构等于URL路由的设计，无需配置路由文件。URL每段直接映射到文件系统。',
    ],
    [
        'title' => '第2课：控制器与视图 — MVC 架构基础',
        'slug'  => 'tutorial-02-mvc',
        'body'  => '<h2>控制器</h2>
<p>控制器继承 <code>\Lib\Core</code> 基类，获得数据库、验证、缓存等全部能力：</p>

<pre><code>class main extends \Lib\Core
{
    public function index(): void
    {
        $posts = $this-&gt;db-&gt;table(\'posts\')
            -&gt;order(\'created_at DESC\')
            -&gt;fetchAll();

        $this-&gt;layout(\'front\');
        $this-&gt;set(\'posts\', $posts);
        $this-&gt;render();
    }
}
</code></pre>

<h2>视图</h2>
<p>视图文件放在 <code>views/</code> 目录下，自动按控制器路径查找：</p>
<ul>
    <li><code>app/home/index.php → index()</code> 会渲染 <code>views/home/index/index.html</code></li>
    <li>找不到时 fallback 到 <code>views/home/index.html</code></li>
</ul>

<h3>传递变量</h3>
<p><code>$this-&gt;set(\'key\', $value)</code> 传递变量到视图，视图中直接用 <code>$key</code> 访问。</p>

<h3>在 H2CMS 中的应用</h3>
<p>首页控制器 <code>app/home/index.php</code> 查询文章列表、分类、分页数据，通过 <code>set()</code> 传递给 <code>views/home/index/index.html</code> 视图渲染。</p>',
        'excerpt' => '控制器继承 Core 基类获得全部能力，视图自动按路径查找，通过 set() 传递变量。',
    ],
    [
        'title' => '第3课：链式查询 — 优雅的数据库操作',
        'slug'  => 'tutorial-03-db-query',
        'body'  => '<h2>基础查询</h2>
<pre><code>// 查询已发布文章，按时间倒序，取前10条
$posts = $this-&gt;db-&gt;table(\'posts\')
    -&gt;where(\'status=?\', [\'published\'])
    -&gt;order(\'created_at DESC\')
    -&gt;limit(10, 0)
    -&gt;fetchAll();

// 查单条记录
$post = $this-&gt;db-&gt;table(\'posts\')
    -&gt;where(\'id=?\', [$id])
    -&gt;fetch();
</code></pre>

<h2>聚合查询</h2>
<pre><code>$total = $this-&gt;db-&gt;table(\'posts\')-&gt;count();
$maxPrice = $this-&gt;db-&gt;table(\'goods\')-&gt;max(\'price\');
</code></pre>

<h2>查询缓存</h2>
<pre><code>// 缓存300秒，相同查询不再访问数据库
$categories = $this-&gt;db-&gt;table(\'categories\')
    -&gt;cache(300)
    -&gt;fetchAll();
</code></pre>

<h2>软删除过滤</h2>
<pre><code>// 自动排除 deleted_at 不为 NULL 的记录
$posts = $this-&gt;db-&gt;table(\'posts\')
    -&gt;softDeletes()
    -&gt;fetchAll();
</code></pre>

<h3>在 H2CMS 中的应用</h3>
<p>首页文章列表使用 <code>where + order + limit</code>；分类列表使用 <code>cache(300)</code> 缓存；文章详情用 <code>softDeletes()</code> 过滤已删除文章。</p>',
        'excerpt' => 'H2PHP 内置轻量级 PDO 封装，支持 where/order/limit/count 链式查询，以及缓存和软删除。',
    ],
    [
        'title' => '第4课：表单验证 — 安全的用户输入处理',
        'slug'  => 'tutorial-04-validation',
        'body'  => '<h2>基本用法</h2>
<pre><code>$v = $this-&gt;validate($_POST, [
    \'title\'    =&gt; \'required|max_len:200\',
    \'email\'    =&gt; \'required|email|unique:users,email\',
    \'password\' =&gt; \'required|min_len:6|confirmed\',
], [
    \'title\' =&gt; \'标题\',
    \'email\' =&gt; \'邮箱\',
    \'password\' =&gt; \'密码\',
]);

if ($v-&gt;fails()) {
    $this-&gt;flash(\'error\', $v-&gt;firstError());
    $this-&gt;redirect(\'/admin/posts/create\');
    return;
}
</code></pre>

<h2>可用规则</h2>
<ul>
    <li><code>required</code> — 必填</li>
    <li><code>email</code> — 邮箱格式</li>
    <li><code>min_len:N / max_len:N</code> — 长度限制</li>
    <li><code>numeric / integer</code> — 数字类型</li>
    <li><code>confirmed</code> — 确认字段一致（如密码确认）</li>
    <li><code>unique:table,column</code> — 数据库唯一性检查</li>
    <li><code>in:val1,val2</code> — 枚举值限制</li>
</ul>

<h3>在 H2CMS 中的应用</h3>
<p>登录表单验证用户名密码、注册验证邮箱唯一性、后台文章编辑验证标题必填、评论提交验证内容长度。</p>',
        'excerpt' => '框架内置验证器支持 required/email/min_len/max_len/unique 等规则，自动生成中文错误提示。',
    ],
    [
        'title' => '第5课：CSRF 防护 — 防止跨站请求伪造',
        'slug'  => 'tutorial-05-csrf',
        'body'  => '<h2>原理</h2>
<p>CSRF（跨站请求伪造）攻击让用户在不知情的情况下提交恶意请求。H2PHP 通过 Token 机制防护：每个表单携带一个随机 Token，提交时服务端验证 Token 是否匹配。</p>

<h2>使用方式</h2>

<h3>1. 视图中输出隐藏字段</h3>
<pre><code>&lt;form method="POST"&gt;
    &lt;?= $csrfField ?&gt;
    &lt;!-- 其他表单字段 --&gt;
    &lt;button type="submit"&gt;提交&lt;/button&gt;
&lt;/form&gt;
</code></pre>

<h3>2. 控制器中生成 Token</h3>
<pre><code>$this-&gt;set(\'csrfField\', $this-&gt;csrfField());
</code></pre>

<h3>3. 处理提交时验证</h3>
<pre><code>public function store(): void
{
    $this-&gt;csrfVerify();  // 验证失败自动返回 403
    // ... 处理表单数据
}
</code></pre>

<h3>在 H2CMS 中的应用</h3>
<p>所有表单（登录、注册、文章编辑、评论提交、分类管理）都使用了 CSRF 防护。</p>',
        'excerpt' => '通过 csrfField() 和 csrfVerify() 两步实现 CSRF 防护，所有表单提交自动验证 Token。',
    ],
    [
        'title' => '第6课：中间件 — 可复用的请求处理管道',
        'slug'  => 'tutorial-06-middleware',
        'body'  => '<h2>什么是中间件？</h2>
<p>中间件是在控制器方法执行之前运行的过滤器，用于登录检查、权限验证、日志记录等。</p>

<h2>创建中间件</h2>
<pre><code>// app/middleware/AdminAuth.php
class AdminAuth
{
    public function handle(callable $next): void
    {
        if (empty($_SESSION[\'user\'])) {
            header(\'Location: /user/login\');
            exit;
        }
        $next();  // 验证通过，继续执行
    }
}
</code></pre>

<h2>在控制器中声明</h2>
<pre><code>class main extends \Lib\Core
{
    protected array $middleware = [\'AdminAuth\'];

    public function index(): void
    {
        // 只有通过 AdminAuth 验证的用户才能到达这里
    }
}
</code></pre>

<h3>在 H2CMS 中的应用</h3>
<p>后台所有控制器（dashboard/posts/categories/pages/comments）都声明了 <code>AdminAuth</code> 中间件，未登录用户访问后台会自动跳转到登录页。</p>',
        'excerpt' => '中间件在控制器之前执行，用于登录检查、权限验证等。声明 $middleware 数组即可启用。',
    ],
    [
        'title' => '第7课：文件上传 — 安全的文件处理',
        'slug'  => 'tutorial-07-upload',
        'body'  => '<h2>基本用法</h2>
<pre><code>$file = $this-&gt;upload(\'featured_image\', \'static/uploads\');

if ($file-&gt;fails()) {
    $this-&gt;flash(\'error\', \'上传失败: \' . $file-&gt;error());
} else {
    $path = $file-&gt;path();  // \'static/uploads/xxx.jpg\'
}
</code></pre>

<h2>链式配置</h2>
<pre><code>$file = $this-&gt;upload(\'photo\', \'static/uploads\')
    -&gt;maxSize(5 * 1024 * 1024)          // 最大 5MB
    -&gt;allowTypes([\'jpg\', \'png\', \'webp\']) // 限制类型
    -&gt;rename(\'timestamp\');               // 用时间戳重命名
</code></pre>

<h3>在 H2CMS 中的应用</h3>
<p>后台文章编辑页支持上传特色图片（featured_image），上传路径为 <code>static/uploads/</code>，限制图片格式和大小。</p>',
        'excerpt' => '框架内置文件上传类，支持链式配置大小限制、文件类型、命名策略，一行代码完成上传。',
    ],
    [
        'title' => '第8课：软删除与回收站 — 数据保护机制',
        'slug'  => 'tutorial-08-soft-delete',
        'body'  => '<h2>什么是软删除？</h2>
<p>软删除不真正删除数据库记录，而是设置 <code>deleted_at</code> 字段。这样数据可以恢复，避免误删。</p>

<h2>使用方式</h2>
<pre><code>// 软删除（设置 deleted_at 为当前时间）
$this-&gt;db-&gt;table(\'posts\')
    -&gt;softDeletes()
    -&gt;where(\'id=?\', [$id])
    -&gt;softDelete();

// 恢复（清除 deleted_at）
$this-&gt;db-&gt;table(\'posts\')
    -&gt;softDeletes()
    -&gt;where(\'id=?\', [$id])
    -&gt;restore();

// 查看已删除的（回收站）
$trashed = $this-&gt;db-&gt;table(\'posts\')
    -&gt;softDeletes()
    -&gt;onlyTrashed()
    -&gt;fetchAll();

// 彻底删除
$this-&gt;db-&gt;table(\'posts\')
    -&gt;where(\'id=?\', [$id])
    -&gt;delete();
</code></pre>

<h3>在 H2CMS 中的应用</h3>
<p>后台文章管理使用了完整的软删除流程：删除→回收站→恢复/彻底删除。文章表有 <code>deleted_at</code> 字段，前台查询自动过滤已删除文章。</p>',
        'excerpt' => '软删除通过 deleted_at 字段实现数据保护，支持回收站、恢复、彻底删除完整流程。',
    ],
    [
        'title' => '第9课：事务 — 保证数据一致性',
        'slug'  => 'tutorial-09-transaction',
        'body'  => '<h2>为什么需要事务？</h2>
<p>当一个操作涉及多个数据库变更时（如删除文章同时删除评论），需要保证要么全部成功，要么全部回滚。</p>

<h2>使用方式</h2>
<pre><code>$this-&gt;db-&gt;transaction(function($db) use ($id) {
    // 先删除文章的所有评论
    $db-&gt;table(\'comments\')
       -&gt;where(\'post_id=?\', [$id])
       -&gt;delete();

    // 再删除文章本身
    $db-&gt;table(\'posts\')
       -&gt;where(\'id=?\', [$id])
       -&gt;delete();
});
// 任何一步失败都会自动回滚
</code></pre>

<h3>在 H2CMS 中的应用</h3>
<p>后台彻底删除文章时使用事务：先删评论、再删文章，保证数据一致性。</p>',
        'excerpt' => '事务保证多个数据库操作要么全部成功，要么全部回滚。一个闭包搞定。',
    ],
    [
        'title' => '第10课：事件系统 — 解耦业务逻辑',
        'slug'  => 'tutorial-10-events',
        'body'  => '<h2>什么是事件系统？</h2>
<p>事件系统让你在特定时机触发通知，其他模块可以监听并响应。这样核心逻辑和附加操作（发邮件、写日志）解耦。</p>

<h2>触发事件</h2>
<pre><code>// 文章被浏览时触发
$this-&gt;fire(\'post.viewed\', $post);

// 文章创建成功后触发
$this-&gt;fire(\'post.created\', [
    \'id\'    =&gt; $id,
    \'title\' =&gt; $title
]);
</code></pre>

<h2>监听事件</h2>
<pre><code>$this-&gt;on(\'post.created\', function($data) {
    // 发送邮件通知管理员
    // 写入操作日志
    // 清除相关缓存
});
</code></pre>

<h3>在 H2CMS 中的应用</h3>
<p>文章详情页触发 <code>post.viewed</code> 事件，文章创建时触发 <code>post.created</code> 事件。</p>',
        'excerpt' => '事件系统通过 fire() 触发、on() 监听，实现业务逻辑解耦，便于扩展功能。',
    ],
    [
        'title' => '第11课：日志系统 — 记录运行轨迹',
        'slug'  => 'tutorial-11-logging',
        'body'  => '<h2>日志级别</h2>
<p>支持 info、warning、error 三个级别，日志文件按日期分割存储在 <code>logs/</code> 目录。</p>

<h2>使用方式</h2>
<pre><code>$this-&gt;log(\'info\',    \'用户登录\', [\'user_id\' =&gt; $id, \'ip\' =&gt; $ip]);
$this-&gt;log(\'warning\', \'登录失败\', [\'username\' =&gt; $name]);
$this-&gt;log(\'error\',   \'支付异常\', [\'order_id\' =&gt; $oid]);
</code></pre>

<h2>日志格式</h2>
<pre><code>[2024-02-25 10:30:15] INFO: 用户登录 {"user_id":1,"ip":"127.0.0.1"}
[2024-02-25 10:30:20] WARNING: 登录失败 {"username":"hacker"}
</code></pre>

<h3>在 H2CMS 中的应用</h3>
<p>用户登录成功/失败、文章 CRUD 操作、评论提交都记录了日志，方便排查问题和安全审计。</p>',
        'excerpt' => '日志系统支持 info/warning/error 级别，按日期分割存储，记录关键操作便于排查问题。',
    ],
    [
        'title' => '第12课：Flash 消息 — 一次性操作提示',
        'slug'  => 'tutorial-12-flash',
        'body'  => '<h2>什么是 Flash 消息？</h2>
<p>Flash 消息是存储在 Session 中的一次性消息，通常用于操作后的反馈提示（如"保存成功"）。消息显示一次后自动清除。</p>

<h2>设置消息</h2>
<pre><code>$this-&gt;flash(\'success\', \'文章已创建\');
$this-&gt;flash(\'error\', \'操作失败，请重试\');
$this-&gt;redirect(\'/admin/posts\');
</code></pre>

<h2>在布局中渲染</h2>
<pre><code>&lt;?php if (!empty($_SESSION[\'_flash\'])): ?&gt;
    &lt;?php $flashes = $_SESSION[\'_flash\']; unset($_SESSION[\'_flash\']); ?&gt;
    &lt;?php foreach ($flashes as $type =&gt; $msg): ?&gt;
    &lt;div class="alert alert-&lt;?= $type ?&gt;"&gt;
        &lt;?= htmlspecialchars($msg) ?&gt;
    &lt;/div&gt;
    &lt;?php endforeach; ?&gt;
&lt;?php endif; ?&gt;
</code></pre>

<h3>在 H2CMS 中的应用</h3>
<p>所有后台操作（创建/编辑/删除文章、审核评论）成功或失败后都通过 Flash 消息反馈，前后台布局统一渲染。</p>',
        'excerpt' => 'Flash 消息用于操作后的一次性反馈提示，存储在 Session 中，显示后自动清除。',
    ],
    [
        'title' => '第13课：布局与模板 — 页面结构复用',
        'slug'  => 'tutorial-13-layout',
        'body'  => '<h2>布局系统</h2>
<p>布局文件放在 <code>views/_layouts/</code> 目录，通过 <code>$content</code> 变量注入页面主体内容。</p>

<pre><code>// 控制器中指定布局
$this-&gt;layout(\'front\');   // 使用 views/_layouts/front.html
$this-&gt;layout(\'admin\');   // 使用 views/_layouts/admin.html
$this-&gt;layout(null);      // 不使用布局

// 布局文件结构
&lt;header&gt;&lt;?php $this-&gt;partial(\'front-nav\') ?&gt;&lt;/header&gt;
&lt;main&gt;&lt;?= $content ?&gt;&lt;/main&gt;
&lt;footer&gt;&lt;?php $this-&gt;partial(\'footer\') ?&gt;&lt;/footer&gt;
</code></pre>

<h2>局部模板（Partial）</h2>
<p>可复用的页面片段放在 <code>views/_partials/</code> 目录：</p>
<pre><code>$this-&gt;partial(\'nav\');     // 引入导航栏
$this-&gt;partial(\'footer\');  // 引入页脚
$this-&gt;partial(\'sidebar\', [\'categories\' =&gt; $cats]); // 带变量
</code></pre>

<h3>在 H2CMS 中的应用</h3>
<p>前台使用 <code>front.html</code> 布局（导航+内容+页脚），后台使用 <code>admin.html</code> 布局（侧边栏+顶栏+内容区）。导航、页脚都是 Partial 模板。</p>',
        'excerpt' => '布局通过 $content 注入页面内容，Partial 实现导航、页脚等片段复用。',
    ],
    [
        'title' => '第14课：自动时间戳 — 省心的时间管理',
        'slug'  => 'tutorial-14-timestamps',
        'body'  => '<h2>使用方式</h2>
<pre><code>// insert 自动填充 created_at 和 updated_at
$this-&gt;db-&gt;table(\'posts\')
    -&gt;timestamps()
    -&gt;insert([
        \'title\' =&gt; \'新文章\',
        \'body\'  =&gt; \'内容...\',
    ]);

// update 自动更新 updated_at
$this-&gt;db-&gt;table(\'posts\')
    -&gt;timestamps()
    -&gt;where(\'id=?\', [$id])
    -&gt;update([
        \'title\' =&gt; \'修改后的标题\',
    ]);
</code></pre>

<h2>前提条件</h2>
<p>数据库表需要有 <code>created_at</code> 和 <code>updated_at</code> 字段（DATETIME 类型）。</p>

<h3>在 H2CMS 中的应用</h3>
<p>文章、页面、评论的创建和更新都使用了 <code>timestamps()</code>，无需手动管理时间字段。</p>',
        'excerpt' => 'timestamps() 自动为 insert 填充 created_at，为 update 更新 updated_at。',
    ],
    [
        'title' => '第15课：配置与部署 — 从开发到生产',
        'slug'  => 'tutorial-15-config-deploy',
        'body'  => '<h2>配置系统</h2>
<pre><code>// config/config.php — 全局配置
return [
    \'db\' =&gt; [...],
    \'default\' =&gt; [\'a\' =&gt; \'home\', \'b\' =&gt; \'index\', \'c\' =&gt; \'index\'],
    \'base_path\' =&gt; \'\',  // 子目录部署时设置
    \'debug\' =&gt; true,
    \'cache\' =&gt; [\'driver\' =&gt; \'file\', \'path\' =&gt; ROOT.\'/cache\'],
];

// config/config.local.php — 本地覆盖（不提交 Git）
return [
    \'db\' =&gt; [\'password\' =&gt; \'my_local_pwd\'],
    \'debug\' =&gt; true,
];
</code></pre>
<p>两个配置文件通过 <code>array_replace_recursive</code> 深度合并。</p>

<h2>部署方式</h2>
<h3>Apache</h3>
<p>将项目放入 Web 根目录，<code>.htaccess</code> 已包含重写规则，开箱即用。</p>

<h3>Nginx</h3>
<p>参考项目中的 <code>nginx.conf.example</code> 配置 URL 重写。</p>

<h3>PHP 内置服务器（开发调试）</h3>
<pre><code>php -S localhost:8080 index.php</code></pre>
<p>路由同时支持 <code>/path</code> 和 <code>?path</code> 两种格式，与生产环境一致。</p>

<h3>子目录部署</h3>
<p>如果部署在 <code>http://localhost/myapp/</code>，只需在 config.php 中设置：</p>
<pre><code>\'base_path\' =&gt; \'/myapp\'</code></pre>
<p>框架会自动处理所有 URL 前缀。</p>',
        'excerpt' => '配置系统支持全局配置+本地覆盖，部署支持 Apache/Nginx/PHP内置服务器，含子目录部署。',
    ],
];

$stmt = $pdo->prepare("INSERT INTO posts (title, slug, body, excerpt, category_id, user_id, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 1, 'published', NOW(), NOW())");

$count = 0;
foreach ($tutorials as $t) {
    // 跳过已存在的（按 slug 判断）
    $exists = $pdo->prepare("SELECT id FROM posts WHERE slug=?");
    $exists->execute([$t['slug']]);
    if ($exists->fetch()) {
        echo "SKIP: {$t['slug']} (already exists)\n";
        continue;
    }
    $stmt->execute([$t['title'], $t['slug'], $t['body'], $t['excerpt'], $catId]);
    $count++;
    echo "OK: {$t['title']}\n";
}

echo "\nDone! Inserted {$count} tutorial posts.\n";
