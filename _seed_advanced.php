<?php
/**
 * H2CMS 教程：第 16~20 课 — 高级特色功能
 * 运行：php _seed_advanced.php
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

// ─── 第16课 ───
[
'title' => '第16课：缓存系统 — 多驱动高速缓存',
'slug'  => 'tutorial-16-cache',
'excerpt' => '支持 file/redis/memcache/memcached 四种驱动，API 统一：get/set/delete/flush。查询缓存一行搞定。',
'body'  => '
<h2>概述</h2>
<p>H2PHP 内置了多驱动缓存封装，能在不改业务代码的情况下切换缓存后端。</p>

<h2>配置</h2>
<pre><code>// config/config.php
\'cache\' => [
    \'driver\'  => \'file\',          // file | redis | memcache | memcached
    \'host\'    => \'127.0.0.1\',     // Redis/Memcache 服务器
    \'port\'    => 6379,            // Redis 6379, Memcache 11211
    \'prefix\'  => \'h2_\',           // key 前缀，防止多项目冲突
    \'dir\'     => ROOT.\'/cache\',   // file 驱动缓存目录
],
</code></pre>

<h2>独立使用 Cache 类</h2>
<pre><code>use Lib\Cache;
$cache = Cache::instance($this->config[\'cache\']);

// 写入（默认 3600 秒）
$cache->set(\'site_settings\', $settings);
$cache->set(\'hot_posts\', $posts, 600);   // 600 秒
$cache->set(\'forever_key\', $data, 0);    // 永不过期

// 读取
$val = $cache->get(\'site_settings\');      // 未命中返回 null

// 删除
$cache->delete(\'site_settings\');

// 清空全部缓存（谨慎！）
$cache->flush();
</code></pre>

<h2>查询缓存（推荐用法）</h2>
<p>直接在链式查询中加 <code>->cache()</code>，<strong>一行代码实现查询结果缓存</strong>：</p>
<pre><code>// 缓存 300 秒
$categories = $this->db->table(\'categories\')
    ->cache(300)
    ->fetchAll();

// 强制刷新缓存（写操作后主动更新热点数据）
$this->db->table(\'categories\')
    ->cache(300, true)       // true = 强制忽略旧缓存，重新查库
    ->fetchAll();
</code></pre>

<h2>四种驱动对比</h2>
<table border="1" cellpadding="8" style="border-collapse:collapse; margin:1rem 0">
    <tr><th>驱动</th><th>性能</th><th>依赖</th><th>适用场景</th></tr>
    <tr><td>file</td><td>★★★</td><td>无</td><td>本地开发、小项目</td></tr>
    <tr><td>redis</td><td>★★★★★</td><td>PHP redis 扩展</td><td>生产环境首选</td></tr>
    <tr><td>memcached</td><td>★★★★★</td><td>PHP memcached 扩展</td><td>分布式缓存</td></tr>
    <tr><td>memcache</td><td>★★★★</td><td>PHP memcache 扩展</td><td>旧版 PHP</td></tr>
</table>

<h3>在 H2CMS 中的应用</h3>
<p>首页分类列表使用 <code>->cache(300)</code> 缓存，避免每次请求都查数据库。开发环境使用 file 驱动零配置。</p>
'],

// ─── 第17课 ───
[
'title' => '第17课：Request 请求封装 — 安全获取用户输入',
'slug'  => 'tutorial-17-request',
'excerpt' => '统一封装 GET/POST/AJAX/IP/Method 等请求信息，避免直接操作超全局变量。',
'body'  => '
<h2>访问 Request 对象</h2>
<p>在控制器中通过 <code>$this->request</code> 访问（懒加载，首次使用时自动创建）：</p>

<h2>获取参数</h2>
<pre><code>// GET 参数
$page = $this->request->get(\'page\', 1);     // 默认值 1
$q    = $this->request->get(\'q\', \'\');

// POST 参数
$name  = $this->request->post(\'username\');
$email = $this->request->post(\'email\', \'\');

// GET 或 POST（POST 优先）
$id = $this->request->input(\'id\');

// 获取全部
$allGet  = $this->request->getAll();    // 等同 $_GET
$allPost = $this->request->postAll();   // 等同 $_POST
</code></pre>

<h2>请求判断</h2>
<pre><code>// 是否 POST 请求
if ($this->request->isPost()) {
    // 处理表单提交
}

// 是否 AJAX 请求
if ($this->request->isAjax()) {
    $this->json([\'status\' => \'ok\']);
    return;
}

// 获取请求方法
$method = $this->request->method(); // GET | POST | PUT | DELETE
</code></pre>

<h2>获取客户端信息</h2>
<pre><code>// 客户端 IP（自动处理代理）
$ip = $this->request->ip();
// 优先级：HTTP_CLIENT_IP > HTTP_X_FORWARDED_FOR > REMOTE_ADDR
</code></pre>

<h2>vs 直接用 $_GET/$_POST</h2>
<table border="1" cellpadding="8" style="border-collapse:collapse; margin:1rem 0">
    <tr><th></th><th>$_GET/$_POST</th><th>$this->request</th></tr>
    <tr><td>默认值</td><td>需 ?? 运算符</td><td>内置默认值参数</td></tr>
    <tr><td>未定义警告</td><td>可能触发 Notice</td><td>不会触发</td></tr>
    <tr><td>IP 代理</td><td>不处理</td><td>自动处理 X-Forwarded-For</td></tr>
    <tr><td>单元测试</td><td>难以 mock</td><td>可替换实例</td></tr>
</table>

<h3>在 H2CMS 中的应用</h3>
<p>分页查询通过 <code>$this->request->get(\'page\', 1)</code> 安全获取页码，日志记录客户端 IP。</p>
'],

// ─── 第18课 ───
[
'title' => '第18课：CLI 工具 h2 — 命令行开发利器',
'slug'  => 'tutorial-18-cli',
'excerpt' => 'Artisan 风格命令行工具，支持代码生成、数据库迁移、队列管理、定时任务、单元测试。',
'body'  => '
<h2>使用方式</h2>
<pre><code>php h2 &lt;命令&gt; [参数]</code></pre>

<h2>全部命令</h2>
<table border="1" cellpadding="8" style="border-collapse:collapse; margin:1rem 0; width:100%">
    <tr><th>类别</th><th>命令</th><th>说明</th></tr>
    <tr><td rowspan="4">代码生成</td>
        <td><code>make:controller user/profile</code></td><td>创建控制器 app/user/profile.php</td></tr>
    <tr><td><code>make:view user/profile/index</code></td><td>创建视图 views/user/profile/index.html</td></tr>
    <tr><td><code>make:job SendEmail</code></td><td>创建队列任务 app/jobs/SendEmail.php</td></tr>
    <tr><td><code>make:task CleanCache</code></td><td>创建定时任务 app/tasks/CleanCache.php</td></tr>

    <tr><td rowspan="3">数据库迁移</td>
        <td><code>migrate</code></td><td>执行所有未运行的迁移</td></tr>
    <tr><td><code>migrate:rollback</code></td><td>回滚上一批迁移</td></tr>
    <tr><td><code>migrate:status</code></td><td>查看迁移状态</td></tr>

    <tr><td rowspan="3">队列管理</td>
        <td><code>queue:work</code></td><td>启动 Worker 处理队列任务</td></tr>
    <tr><td><code>queue:status</code></td><td>查看队列状态</td></tr>
    <tr><td><code>queue:clear</code></td><td>清空队列</td></tr>

    <tr><td rowspan="2">定时任务</td>
        <td><code>schedule:run</code></td><td>执行所有到期的定时任务</td></tr>
    <tr><td><code>schedule:list</code></td><td>列出所有定时任务</td></tr>

    <tr><td>测试</td>
        <td><code>test [--filter=xxx]</code></td><td>运行单元测试（PHPUnit）</td></tr>
</table>

<h2>代码生成示例</h2>
<pre><code>$ php h2 make:controller admin/settings
✓ 已创建: app/admin/settings.php

$ php h2 make:view admin/settings/index
✓ 已创建: views/admin/settings/index.html

$ php h2 make:job SendWelcomeEmail
✓ 已创建: app/jobs/SendWelcomeEmail.php
</code></pre>
<p>生成的文件包含标准模板代码，直接编辑即可使用。</p>

<h2>数据库迁移</h2>
<pre><code>// 迁移文件放在 migrations/ 目录，按编号排序
// migrations/001_create_tables.php
// migrations/002_add_views_column.php

$ php h2 migrate
Running: 001_create_tables.php ... ✓
Running: 002_add_views_column.php ... ✓

$ php h2 migrate:status
+----+---------------------------+-------+
| #  | Migration                 | Batch |
+----+---------------------------+-------+
| 1  | 001_create_tables.php     |   1   |
| 2  | 002_add_views_column.php  |   1   |
+----+---------------------------+-------+

$ php h2 migrate:rollback
Rolled back: 002_add_views_column.php
</code></pre>

<h3>在 H2CMS 中的应用</h3>
<p>H2CMS 用 <code>php h2 migrate</code> 初始化数据库，用 <code>make:controller</code> 快速生成新的后台管理页面。</p>
'],

// ─── 第19课 ───
[
'title' => '第19课：定时任务调度 — 类 Laravel Schedule',
'slug'  => 'tutorial-19-scheduler',
'excerpt' => '只需一条系统 cron，即可管理所有定时任务。支持 Task 类、CLI 命令、闭包三种注册方式。',
'body'  => '
<h2>原理</h2>
<p>传统方式要为每个定时任务配置一条 cron。H2PHP 的 Scheduler 让你只需<strong>一条 cron</strong>，所有定时任务在 PHP 中管理：</p>
<pre><code># 系统 crontab，只需这一条
* * * * * php /path/to/h2 schedule:run</code></pre>

<h2>定义任务</h2>
<p>在 <code>app/schedules.php</code> 中注册：</p>

<h3>方式一：Task 类（推荐）</h3>
<pre><code>// app/schedules.php
$scheduler->call(\'CleanExpiredCache\')
    ->daily()
    ->description(\'清理过期缓存\');

// app/tasks/CleanExpiredCache.php
class CleanExpiredCache {
    public function handle(): void {
        // 清理逻辑...
    }
}
</code></pre>

<h3>方式二：CLI 命令</h3>
<pre><code>$scheduler->command(\'queue:clear\')
    ->weekly()
    ->description(\'每周清空已完成队列\');
</code></pre>

<h3>方式三：闭包</h3>
<pre><code>$scheduler->job(function() {
    file_put_contents(\'logs/heartbeat.log\', date(\'Y-m-d H:i:s\')."\n", FILE_APPEND);
}, \'heartbeat\')->everyMinutes(5)->description(\'心跳检测\');
</code></pre>

<h2>频率设置（链式）</h2>
<table border="1" cellpadding="8" style="border-collapse:collapse; margin:1rem 0">
    <tr><th>方法</th><th>频率</th><th>cron 表达式</th></tr>
    <tr><td>everyMinute()</td><td>每分钟</td><td>* * * * *</td></tr>
    <tr><td>everyMinutes(5)</td><td>每 5 分钟</td><td>*/5 * * * *</td></tr>
    <tr><td>hourly()</td><td>每小时整点</td><td>0 * * * *</td></tr>
    <tr><td>hourlyAt(30)</td><td>每小时 30 分</td><td>30 * * * *</td></tr>
    <tr><td>daily()</td><td>每天凌晨</td><td>0 0 * * *</td></tr>
    <tr><td>dailyAt(\'03:00\')</td><td>每天 3 点</td><td>0 3 * * *</td></tr>
    <tr><td>weekly()</td><td>每周一</td><td>0 0 * * 1</td></tr>
    <tr><td>monthly()</td><td>每月 1 日</td><td>0 0 1 * *</td></tr>
    <tr><td>cron(\'0 2 * * 0\')</td><td>自定义表达式</td><td>每周日凌晨 2 点</td></tr>
</table>

<h2>管理命令</h2>
<pre><code>$ php h2 schedule:list
+----+--------------------+-------------+----------------+
| #  | Task               | Expression  | Description    |
+----+--------------------+-------------+----------------+
| 1  | CleanExpiredCache  | 0 0 * * *   | 清理过期缓存    |
| 2  | queue:clear        | 0 0 * * 1   | 每周清空队列    |
| 3  | heartbeat          | */5 * * * * | 心跳检测        |
+----+--------------------+-------------+----------------+

$ php h2 schedule:run
✓ heartbeat
</code></pre>

<h3>在 H2CMS 中的应用</h3>
<p>可用于：每日清理过期缓存文件、每周发送数据统计邮件、定时清理过期 Session。</p>
'],

// ─── 第20课 ───
[
'title' => '第20课：插件机制 — 用事件系统打造可扩展架构',
'slug'  => 'tutorial-20-plugin',
'excerpt' => '利用 Event 系统实现类似 WordPress add_action/add_filter 的插件机制，解耦扩展功能。',
'body'  => '
<h2>灵感来源：WordPress 的插件系统</h2>
<p>WordPress 的灵魂是 <code>add_action</code> 和 <code>add_filter</code>，它们让第三方开发者无需修改核心代码就能扩展功能。H2PHP 的 Event 系统提供了相同的能力。</p>

<h2>类比对照</h2>
<table border="1" cellpadding="8" style="border-collapse:collapse; margin:1rem 0; width:100%">
    <tr><th>WordPress</th><th>H2PHP</th><th>用途</th></tr>
    <tr><td>add_action(\'hook\', fn)</td><td>Event::on(\'hook\', fn)</td><td>在某个时机执行代码</td></tr>
    <tr><td>do_action(\'hook\', data)</td><td>Event::fire(\'hook\', data)</td><td>触发钩子</td></tr>
    <tr><td>remove_action(\'hook\')</td><td>Event::forget(\'hook\')</td><td>移除监听</td></tr>
</table>

<h2>实战示例一：阅读量统计</h2>
<pre><code>// ── 插件文件：app/plugins/ViewCounter.php ──
class ViewCounter
{
    public static function register(): void
    {
        \Lib\Event::on(\'post.viewed\', function($post) {
            $db = new \Lib\DB($GLOBALS[\'config\'][\'db\']);
            $db->exec(
                \'UPDATE posts SET views = views + 1 WHERE id = ?\',
                [$post[\'id\']]
            );
        });
    }
}

// ── 注册（在 index.php 或 Bootstrap 中）──
ViewCounter::register();

// ── 控制器中触发 ──
public function view(int $id): void
{
    $post = $this->db->table(\'posts\')->where(\'id=?\', [$id])->fetch();
    $this->fire(\'post.viewed\', $post);  // 插件自动计数
    // ...
}
</code></pre>

<h2>实战示例二：关键词自动加链接</h2>
<pre><code>// ── app/plugins/AutoLink.php ──
class AutoLink
{
    private static array $keywords = [
        \'H2PHP\'   => \'/\',
        \'H2CMS\'   => \'https://github.com/tang30000/h2cms\',
        \'MVC\'     => \'/post/index/view/2\',
    ];

    public static function register(): void
    {
        \Lib\Event::on(\'post.render\', function(&$html) {
            foreach (self::$keywords as $word => $url) {
                $link = "&lt;a href=\"{$url}\" class=\"auto-link\"&gt;{$word}&lt;/a&gt;";
                $html = str_replace($word, $link, $html);
            }
        });
    }
}

// ── 控制器中 ──
$body = $post[\'body\'];
$this->fire(\'post.render\', $body);  // 插件自动处理
$this->set(\'body\', $body);
</code></pre>

<h2>实战示例三：新评论邮件通知</h2>
<pre><code>// ── app/plugins/CommentNotifier.php ──
class CommentNotifier
{
    public static function register(): void
    {
        \Lib\Event::on(\'comment.created\', function($data) {
            $core = new \Lib\Core();  // 或用 DI 注入
            $core->mail(
                \'admin@h2cms.local\',
                "新评论: {$data[\'author_name\']}\",
                "文章 #{$data[\'post_id\']} 收到新评论：\n{$data[\'body\']}"
            );
        });
    }
}
</code></pre>

<h2>插件管理模式</h2>
<pre><code>// index.php 或 app/bootstrap.php
// 统一加载所有插件
$plugins = [
    \'ViewCounter\',
    \'AutoLink\',
    \'CommentNotifier\',
];
foreach ($plugins as $p) {
    require_once APP . "/plugins/{$p}.php";
    $p::register();
}
</code></pre>

<h2>Event API 完整参考</h2>
<pre><code>use Lib\Event;

// 注册监听器（可注册多个）
Event::on(\'event.name\', function($payload) { ... });
Event::on(\'event.name\', [$object, \'method\']);

// 触发事件
Event::fire(\'event.name\', $data);

// 移除指定事件的所有监听器
Event::forget(\'event.name\');

// 清空全部监听器
Event::flushAll();

// 控制器快捷方法（效果相同）
$this->on(\'event.name\', fn($data) => ...);
$this->fire(\'event.name\', $data);
</code></pre>

<h3>在 H2CMS 中的应用</h3>
<ul>
    <li>文章详情页触发 <code>post.viewed</code> — 可挂载阅读量统计、推荐算法等</li>
    <li>评论提交时可触发 <code>comment.created</code> — 可挂载邮件通知、垃圾评论过滤等</li>
    <li>无需修改核心控制器代码，所有扩展通过插件文件实现</li>
</ul>
'],

]; // end tutorials

$insertStmt = $pdo->prepare("INSERT INTO posts (title, slug, body, excerpt, category_id, user_id, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 1, 'published', NOW(), NOW())");
$updateStmt = $pdo->prepare("UPDATE posts SET title=?, body=?, excerpt=? WHERE slug=?");

$inserted = 0; $updated = 0;
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
