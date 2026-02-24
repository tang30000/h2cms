<?php
$pdo = new PDO('mysql:host=localhost;dbname=h2cms;charset=utf8mb4', 'root', 'usbw');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$cat = $pdo->query("SELECT id FROM categories WHERE slug='tutorial'")->fetch(PDO::FETCH_ASSOC);
$catId = $cat['id'];

// ─── 第29课 ───
$title29   = '第29课：Response 与 Pagination — 优雅的输出与分页';
$slug29    = 'tutorial-29-response-pagination';
$excerpt29 = 'Response 统一 HTTP 响应（JSON/下载/重定向），Pagination 独立分页器（一行分页 + HTML 链接 + API 输出）。';
$body29    = <<<'HTML'
<h2>Response — 统一响应</h2>
<p>控制器中通过 <code>$this->response</code> 获取，每次返回新实例：</p>

<h3>JSON 响应</h3>
<pre><code>// 基础 JSON
$this->response->json(['name' => 'Tom', 'age' => 25]);

// 指定状态码
$this->response->status(201)->json(['id' => 1, 'msg' => '创建成功']);

// 带自定义头
$this->response->header('X-Request-Id', uniqid())
    ->json($data);
</code></pre>

<h3>其他输出格式</h3>
<pre><code>// 纯文本
$this->response->text('Hello World');

// HTML
$this->response->html('&lt;h1&gt;Hi&lt;/h1&gt;');

// 无内容（204，常用于 DELETE 成功）
$this->response->noContent();
</code></pre>

<h3>文件下载</h3>
<pre><code>// 下载文件（自动设置 Content-Disposition）
$this->response->download('/path/to/report.pdf', '月度报表.pdf');
</code></pre>

<h3>重定向</h3>
<pre><code>// 302 临时重定向
$this->response->redirect('/home');

// 301 永久重定向
$this->response->redirect('/new-url', 301);
</code></pre>

<h3>Response 方法汇总</h3>
<table border="1" cellpadding="8" style="border-collapse:collapse; margin:1rem 0; width:100%">
    <tr><th>方法</th><th>说明</th></tr>
    <tr><td>status($code)</td><td>设置 HTTP 状态码</td></tr>
    <tr><td>header($name, $value)</td><td>设置响应头</td></tr>
    <tr><td>withHeaders($array)</td><td>批量设置响应头</td></tr>
    <tr><td>json($data)</td><td>输出 JSON</td></tr>
    <tr><td>text($content)</td><td>输出纯文本</td></tr>
    <tr><td>html($content)</td><td>输出 HTML</td></tr>
    <tr><td>download($path, $name)</td><td>下载文件</td></tr>
    <tr><td>redirect($url, $status)</td><td>重定向</td></tr>
    <tr><td>noContent()</td><td>204 无内容响应</td></tr>
</table>

<h2>Pagination — 独立分页器</h2>

<h3>方式一：从 DB 查询自动构建</h3>
<pre><code>use Lib\Pagination;

$page  = max(1, (int)$this->request->get('page', 1));
$query = $this->db->table('posts')->where('status=?', ['published']);

// 一行搞定：自动 count + limit + fetchAll
$pager = Pagination::fromQuery($query, $page, 10);

$this->set('posts', $pager->items());      // 当前页数据
$this->set('pager', $pager);               // 传到视图
$this->render('post/list');
</code></pre>

<h3>视图中输出分页链接</h3>
<pre><code>&lt;?php foreach ($posts as $post): ?&gt;
    &lt;h2&gt;&lt;?= $post['title'] ?&gt;&lt;/h2&gt;
&lt;?php endforeach; ?&gt;

&lt;!-- 输出分页 HTML --&gt;
&lt;?= $pager->links('/posts?page={page}') ?&gt;

&lt;!-- SEO 友好的 URL 格式 --&gt;
&lt;?= $pager->links('/posts/page/{page}') ?&gt;
</code></pre>
<p>生成的 HTML 示例：<code>&laquo; 上一页 1 2 [3] 4 5 下一页 &raquo;</code></p>

<h3>方式二：手动构建</h3>
<pre><code>$total = $this->db->table('posts')->count();
$pager = new Pagination($total, $page, 10);

$posts = $this->db->table('posts')
    ->order('id ASC')
    ->limit($pager->perPage(), $pager->offset())
    ->fetchAll();

$pager->setItems($posts);
</code></pre>

<h3>API 分页响应</h3>
<pre><code>$pager = Pagination::fromQuery($query, $page, 20);

// toArray() 返回标准分页结构
$this->response->json($pager->toArray());

// 输出：
// {
//   "data": [...],
//   "current_page": 2,
//   "per_page": 20,
//   "total": 156,
//   "total_pages": 8,
//   "has_prev": true,
//   "has_next": true
// }
</code></pre>

<h3>Pagination 方法汇总</h3>
<table border="1" cellpadding="8" style="border-collapse:collapse; margin:1rem 0; width:100%">
    <tr><th>方法</th><th>说明</th></tr>
    <tr><td>fromQuery($query, $page, $perPage)</td><td>从 DB 查询自动构建</td></tr>
    <tr><td>items()</td><td>当前页数据</td></tr>
    <tr><td>links($urlPattern)</td><td>生成 HTML 分页链接</td></tr>
    <tr><td>toArray()</td><td>转数组（API 响应）</td></tr>
    <tr><td>currentPage() / totalPages()</td><td>页码信息</td></tr>
    <tr><td>total() / perPage() / offset()</td><td>数量信息</td></tr>
    <tr><td>hasPrev() / hasNext()</td><td>是否有上/下一页</td></tr>
</table>
HTML;

// ─── 第30课 ───
$title30   = '第30课：Env 与 CORS — 环境变量与跨域配置';
$slug30    = 'tutorial-30-env-cors';
$excerpt30 = '.env 环境变量分离敏感配置，CORS 中间件一键解决跨域问题。';
$body30    = <<<'HTML'
<h2>Env — .env 环境变量</h2>
<p>将数据库密码、API 密钥等敏感信息从代码中分离出来，存在 <code>.env</code> 文件中，不提交到 Git。</p>

<h3>创建 .env 文件</h3>
<pre><code># 复制示例文件
cp .env.example .env

# 编辑 .env，填入实际值
APP_KEY=a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6
APP_DEBUG=true

DB_HOST=localhost
DB_NAME=myapp
DB_USER=root
DB_PASS=your_password

REDIS_HOST=127.0.0.1
REDIS_PORT=6379

MAIL_HOST=smtp.qq.com
MAIL_USER=your@qq.com
MAIL_PASS=smtp_authorization_code
</code></pre>

<h3>.env 语法规则</h3>
<pre><code># 这是注释
KEY=value
KEY="value with spaces"   # 支持引号
KEY='single quotes too'

# 特殊值自动转换
DEBUG=true     # 读取为 bool true
DEBUG=false    # 读取为 bool false
VALUE=null     # 读取为 PHP null
</code></pre>

<h3>在 config.php 中使用</h3>
<pre><code>// config/config.php
use Lib\Env;

return [
    'app_key' => Env::get('APP_KEY', 'default-key'),
    'debug'   => Env::get('APP_DEBUG', false),

    'db' => [
        'dsn'      => 'mysql:host=' . Env::get('DB_HOST', 'localhost')
                    . ';dbname=' . Env::get('DB_NAME', 'test')
                    . ';charset=utf8mb4',
        'user'     => Env::get('DB_USER', 'root'),
        'password' => Env::get('DB_PASS', ''),
    ],

    'redis' => [
        'host' => Env::get('REDIS_HOST', '127.0.0.1'),
        'port' => (int)Env::get('REDIS_PORT', 6379),
    ],
];
</code></pre>

<h3>在控制器中读取</h3>
<pre><code>use Lib\Env;

// 读取环境变量（带默认值）
$debug = Env::get('APP_DEBUG', false);
$key   = Env::get('APP_KEY');

// 框架启动时 Bootstrap 已自动加载 .env
// 所以 config.php 和控制器中直接用即可
</code></pre>

<h3>安全规则</h3>
<table border="1" cellpadding="8" style="border-collapse:collapse; margin:1rem 0; width:100%">
    <tr><th>文件</th><th>作用</th><th>提交到 Git?</th></tr>
    <tr><td>.env</td><td>实际配置（含密码）</td><td>❌ 不提交（.gitignore 已忽略）</td></tr>
    <tr><td>.env.example</td><td>配置模板（空值）</td><td>✅ 提交（给团队参考）</td></tr>
    <tr><td>config.php</td><td>读取 Env::get()</td><td>✅ 提交（不含真实密码）</td></tr>
</table>

<h2>CORS — 跨域中间件</h2>
<p>前后端分离开发时，前端（如 Vue/React）运行在不同端口，浏览器会拦截跨域请求。CORS 中间件解决这个问题。</p>

<h3>启用</h3>
<pre><code>// config/config.php
'middleware' => ['Cors'],  // 添加到中间件数组
</code></pre>

<h3>配置（可选）</h3>
<pre><code>// config/config.php
'cors' => [
    'origin'      => '*',        // 允许所有域名，生产环境改为具体域名
    'methods'     => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
    'headers'     => 'Content-Type, Authorization, X-Requested-With',
    'credentials' => false,      // 是否允许携带 Cookie
    'max_age'     => 86400,      // 预检请求缓存 24 小时
],
</code></pre>

<h3>生产环境建议</h3>
<pre><code>// 限制具体域名
'cors' => [
    'origin'      => 'https://www.example.com',
    'credentials' => true,   // 允许携带 Cookie
],
</code></pre>

<h3>工作原理</h3>
<p>中间件自动处理：</p>
<ul>
    <li>为所有响应添加 <code>Access-Control-Allow-*</code> 头</li>
    <li>自动响应 OPTIONS 预检请求（返回 204）</li>
    <li>放在 middleware 数组第一个，确保最先执行</li>
</ul>

<h3>中间件机制简介</h3>
<pre><code>// config/config.php
'middleware' => [
    'Cors',        // 跨域（最先执行）
    'AuthCheck',   // 登录检查（自己编写）
],

// 中间件文件放在 app/middleware/ 目录
// 每个中间件必须有 handle() 方法
// Bootstrap 会按顺序自动执行
</code></pre>
HTML;

$insertStmt = $pdo->prepare("INSERT INTO posts (title, slug, body, excerpt, category_id, user_id, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 1, 'published', NOW(), NOW())");
$updateStmt = $pdo->prepare("UPDATE posts SET title=?, body=?, excerpt=? WHERE slug=?");

foreach ([
    ['title' => $title29, 'slug' => $slug29, 'body' => $body29, 'excerpt' => $excerpt29],
    ['title' => $title30, 'slug' => $slug30, 'body' => $body30, 'excerpt' => $excerpt30],
] as $t) {
    $exists = $pdo->prepare("SELECT id FROM posts WHERE slug=?");
    $exists->execute([$t['slug']]);
    if ($exists->fetch()) {
        $updateStmt->execute([$t['title'], $t['body'], $t['excerpt'], $t['slug']]);
        echo "UPDATE: {$t['title']}\n";
    } else {
        $insertStmt->execute([$t['title'], $t['slug'], $t['body'], $t['excerpt'], $catId]);
        echo "INSERT: {$t['title']}\n";
    }
}
echo "Done!\n";
