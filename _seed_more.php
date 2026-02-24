<?php
/**
 * H2CMS 教程：第 21~27 课 — 新增组件
 * 运行：php _seed_more.php
 */
$pdo = new PDO('mysql:host=localhost;dbname=h2cms;charset=utf8mb4', 'root', 'usbw');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("SET NAMES utf8mb4");

$cat = $pdo->query("SELECT id FROM categories WHERE slug='tutorial'")->fetch(PDO::FETCH_ASSOC);
$catId = $cat ? $cat['id'] : null;

$tutorials = [

// ─── 第21课：Redis 封装 ───
[
'title' => '第21课：Redis 封装 — 高性能数据操作',
'slug'  => 'tutorial-21-redis',
'excerpt' => '完整封装 Redis 七大数据结构+分布式锁+发布订阅+管道，控制器 $this->redis 一键调用。',
'body'  => '
<h2>配置</h2>
<pre><code>// config/config.php
\'redis\' => [
    \'host\'     => \'127.0.0.1\',
    \'port\'     => 6379,
    \'password\' => \'\',
    \'database\' => 0,
    \'prefix\'   => \'h2_\',
    \'timeout\'  => 2.0,
],
</code></pre>

<h2>控制器中使用</h2>
<p>通过 <code>$this->redis</code> 懒加载，首次访问自动创建连接：</p>

<h3>字符串</h3>
<pre><code>$this->redis->set(\'user:token:5\', $token, 7200);
$val = $this->redis->get(\'user:token:5\');
$this->redis->del(\'user:token:5\');
$this->redis->exists(\'user:token:5\');   // false
$this->redis->expire(\'key\', 3600);
$this->redis->ttl(\'key\');              // 剩余秒数
</code></pre>

<h3>计数器</h3>
<pre><code>$this->redis->incr(\'post:views:3\');       // +1
$this->redis->incr(\'post:views:3\', 5);    // +5
$this->redis->decr(\'stock:item:10\');      // -1
</code></pre>

<h3>哈希（用户信息、配置等）</h3>
<pre><code>$this->redis->hMSet(\'user:1\', [\'name\' => \'Tom\', \'age\' => 25]);
$this->redis->hGet(\'user:1\', \'name\');        // \'Tom\'
$this->redis->hGetAll(\'user:1\');             // [\'name\'=>\'Tom\', \'age\'=>25]
$this->redis->hIncr(\'user:1\', \'age\');        // 26
$this->redis->hDel(\'user:1\', \'age\');
$this->redis->hExists(\'user:1\', \'name\');     // true
</code></pre>

<h3>列表（消息队列、最新动态）</h3>
<pre><code>$this->redis->rPush(\'queue:email\', json_encode($job));   // 入队
$job = $this->redis->lPop(\'queue:email\');                 // 出队
$this->redis->lLen(\'queue:email\');                        // 队列长度
$this->redis->lRange(\'queue:email\', 0, -1);              // 全部元素
</code></pre>

<h3>集合（标签、好友关系）</h3>
<pre><code>$this->redis->sAdd(\'user:1:tags\', \'php\', \'mysql\', \'redis\');
$this->redis->sMembers(\'user:1:tags\');          // [\'php\',\'mysql\',\'redis\']
$this->redis->sIsMember(\'user:1:tags\', \'php\');  // true
$this->redis->sRem(\'user:1:tags\', \'mysql\');
$this->redis->sCard(\'user:1:tags\');             // 成员数
</code></pre>

<h3>有序集合（排行榜）</h3>
<pre><code>$this->redis->zAdd(\'leaderboard\', 100, \'player1\');
$this->redis->zAdd(\'leaderboard\', 200, \'player2\');
$this->redis->zIncrBy(\'leaderboard\', 50, \'player1\');    // 150

// Top 10（从高到低）
$top10 = $this->redis->zRevRange(\'leaderboard\', 0, 9, true);
// [\'player2\' => 200, \'player1\' => 150]

$this->redis->zRank(\'leaderboard\', \'player1\');  // 排名
$this->redis->zScore(\'leaderboard\', \'player1\'); // 分数
</code></pre>

<h3>分布式锁</h3>
<pre><code>// 获取锁（10 秒自动释放，防死锁）
$token = $this->redis->lock(\'order:create\', 10);
if ($token) {
    // 执行互斥操作（如扣库存）
    $this->redis->unlock(\'order:create\', $token);
} else {
    $this->json([\'error\' => \'操作太频繁\']);
}
</code></pre>

<h3>管道（批量操作减少网络往返）</h3>
<pre><code>$results = $this->redis->pipeline(function($pipe) {
    $pipe->set(\'a\', \'1\');
    $pipe->set(\'b\', \'2\');
    $pipe->get(\'a\');
});
// $results[2] = \'1\'
</code></pre>

<h3>发布/订阅</h3>
<pre><code>// 发布
$this->redis->publish(\'chat\', json_encode([\'msg\' => \'hello\']));

// 订阅（阻塞，通常在 CLI 模式使用）
$this->redis->subscribe([\'chat\'], function($redis, $channel, $msg) {
    echo "频道 {$channel}: {$msg}\n";
});
</code></pre>
'],

// ─── 第22课：Http 客户端 ───
[
'title' => '第22课：Http 客户端 — 调用第三方 API',
'slug'  => 'tutorial-22-http',
'excerpt' => '轻量级 cURL 封装，支持 GET/POST/PUT/DELETE、Bearer Token、文件上传、响应对象。',
'body'  => '
<h2>基本请求</h2>
<pre><code>use Lib\Http;
$http = new Http();

// GET
$res = $http->get(\'https://api.example.com/users\', [\'page\' => 1]);
$users = $res->json();   // 自动解析 JSON

// POST（自动编码为 JSON）
$res = $http->post(\'https://api.example.com/users\', [
    \'name\'  => \'Tom\',
    \'email\' => \'tom@example.com\',
]);
if ($res->ok()) {
    $newUser = $res->json();
}
</code></pre>

<h2>链式配置</h2>
<pre><code>$res = (new Http())
    ->baseUrl(\'https://api.example.com\')  // 后续只传路径
    ->timeout(10)                          // 超时 10 秒
    ->withToken(\'your-bearer-token\')       // Authorization: Bearer xxx
    ->withHeaders([\'X-App-Id\' => \'123\'])   // 自定义头
    ->get(\'/users/1\');
</code></pre>

<h2>全部请求方法</h2>
<pre><code>$http->get($url, $queryParams);
$http->post($url, $data);
$http->put($url, $data);
$http->patch($url, $data);
$http->delete($url, $data);
</code></pre>

<h2>文件上传</h2>
<pre><code>$res = $http->upload(
    \'https://api.example.com/upload\',
    \'/path/to/photo.jpg\',   // 文件路径
    \'avatar\',              // 字段名
    [\'user_id\' => 1]       // 附加参数
);
</code></pre>

<h2>响应对象 HttpResponse</h2>
<table border="1" cellpadding="8" style="border-collapse:collapse; margin:1rem 0; width:100%">
    <tr><th>方法</th><th>返回</th><th>说明</th></tr>
    <tr><td>status()</td><td>int</td><td>HTTP 状态码</td></tr>
    <tr><td>ok()</td><td>bool</td><td>2xx 返回 true</td></tr>
    <tr><td>failed()</td><td>bool</td><td>非 2xx 返回 true</td></tr>
    <tr><td>body()</td><td>string</td><td>原始响应体</td></tr>
    <tr><td>json()</td><td>array|null</td><td>解析 JSON</td></tr>
    <tr><td>headers()</td><td>array</td><td>全部响应头</td></tr>
    <tr><td>header(\'key\')</td><td>string|null</td><td>单个响应头</td></tr>
    <tr><td>error()</td><td>string</td><td>cURL 错误信息</td></tr>
</table>

<h2>实战：微信 Access Token</h2>
<pre><code>$res = (new Http())->get(\'https://api.weixin.qq.com/cgi-bin/token\', [
    \'grant_type\' => \'client_credential\',
    \'appid\'      => $config[\'wx_appid\'],
    \'secret\'     => $config[\'wx_secret\'],
]);
$token = $res->json()[\'access_token\'] ?? null;
</code></pre>
'],

// ─── 第23课：Auth 鉴权 ───
[
'title' => '第23课：Auth 鉴权 — 密码、Session 与 JWT',
'slug'  => 'tutorial-23-auth',
'excerpt' => '统一封装密码哈希(bcrypt)、Session 登录管理、JWT Token 生成验证，三合一。',
'body'  => '
<h2>密码哈希</h2>
<pre><code>use Lib\Auth;

// 注册时加密密码
$hash = Auth::hashPassword(\'123456\');
// 存入数据库：$2y$10$xxxx...

// 登录时验证
if (Auth::verifyPassword($inputPassword, $dbHash)) {
    // 密码正确
}

// 检查是否需要重新哈希（算法升级时）
if (Auth::needsRehash($dbHash)) {
    $newHash = Auth::hashPassword($inputPassword);
    // 更新数据库
}
</code></pre>

<h2>Session 登录管理</h2>
<pre><code>// 登录：存入 Session + 重新生成 Session ID（防固定攻击）
Auth::login([\'id\' => 1, \'username\' => \'admin\', \'role\' => \'admin\']);

// 检查是否登录
if (Auth::check()) {
    $user = Auth::user();       // [\'id\'=>1, \'username\'=>\'admin\', ...]
    $id   = Auth::id();         // 1
}

// 登出
Auth::logout();
</code></pre>

<h2>JWT Token（无状态鉴权）</h2>
<p>适用于 API 接口、前后端分离、移动端：</p>
<pre><code>$secret = \'your-secret-key-at-least-32-chars\';

// 生成 Token（2 小时有效）
$token = Auth::jwtEncode([
    \'user_id\'  => 1,
    \'username\' => \'admin\',
], $secret, 7200);

// 客户端携带 Token 请求
// Authorization: Bearer eyJhbGci...

// 验证 Token
$payload = Auth::jwtDecode($token, $secret);
if ($payload) {
    // $payload[\'user_id\'] = 1
    // 验证通过
} else {
    // Token 无效或已过期
    $this->abort(401);
}
</code></pre>

<h2>Session vs JWT 对比</h2>
<table border="1" cellpadding="8" style="border-collapse:collapse; margin:1rem 0; width:100%">
    <tr><th></th><th>Session</th><th>JWT</th></tr>
    <tr><td>存储位置</td><td>服务端</td><td>客户端</td></tr>
    <tr><td>适合场景</td><td>传统网站</td><td>API / 移动端</td></tr>
    <tr><td>扩展性</td><td>需共享 Session</td><td>天然无状态</td></tr>
    <tr><td>注销</td><td>删除即可</td><td>需黑名单机制</td></tr>
</table>
'],

// ─── 第24课：Encryption 加解密 ───
[
'title' => '第24课：Encryption 加解密 — AES-256 数据保护',
'slug'  => 'tutorial-24-encryption',
'excerpt' => 'AES-256-CBC 加密 + HMAC 防篡改，保护敏感数据。支持实例和静态两种用法。',
'body'  => '
<h2>配置</h2>
<pre><code>// config/config.php
\'app_key\' => \'your-32-character-secret-key!!\',  // 必须 32 字节
</code></pre>

<h2>实例用法</h2>
<pre><code>use Lib\Encryption;
$enc = new Encryption($config[\'app_key\']);

// 加密
$cipher = $enc->encrypt(\'用户身份证号：123456789\');
// 输出 Base64 密文：aGVsbG8gd29ybGQ...

// 解密
$plain = $enc->decrypt($cipher);
// \'用户身份证号：123456789\'

// 篡改检测
$tampered = $cipher . \'xxx\';
$enc->decrypt($tampered);  // 返回 null（HMAC 校验失败）
</code></pre>

<h2>静态快捷方式</h2>
<pre><code>// 启动时设置全局密钥
Encryption::setKey($config[\'app_key\']);

// 任何地方使用
$cipher = Encryption::enc(\'sensitive data\');
$plain  = Encryption::dec($cipher);
</code></pre>

<h2>安全特性</h2>
<ul>
    <li><strong>AES-256-CBC</strong> — 军事级加密算法</li>
    <li><strong>随机 IV</strong> — 每次加密生成不同密文</li>
    <li><strong>HMAC-SHA256</strong> — 检测数据是否被篡改</li>
    <li><strong>Base64 输出</strong> — 安全存储在数据库或 Cookie 中</li>
</ul>

<h2>实战场景</h2>
<pre><code>// 加密存储 API 密钥
$this->db->table(\'settings\')->insert([
    \'key\'   => \'wechat_secret\',
    \'value\' => Encryption::enc($wxSecret),
]);

// 读取时解密
$row    = $this->db->table(\'settings\')->where(\'key=?\',[\'wechat_secret\'])->fetch();
$secret = Encryption::dec($row[\'value\']);
</code></pre>
'],

// ─── 第25课：RateLimiter 限流 ───
[
'title' => '第25课：RateLimiter 限流 — 防止接口被刷',
'slug'  => 'tutorial-25-ratelimiter',
'excerpt' => '滑动窗口限流器，支持 Redis 和文件双后端。保护登录、API、短信接口。',
'body'  => '
<h2>创建限流器</h2>
<pre><code>use Lib\RateLimiter;

// 有 Redis 配置时自动用 Redis（滑动窗口，更精确）
// 否则自动降级为文件存储（固定窗口）
$limiter = new RateLimiter($this->config);
</code></pre>

<h2>基本用法</h2>
<pre><code>// tooMany(标识, 最大次数, 时间窗口秒数)
// 返回 true = 已超限，应拒绝

// 每个 IP 每分钟最多 60 次请求
if ($limiter->tooMany(\'api:\' . $this->request->ip(), 60, 60)) {
    $this->json([\'error\' => \'请求过于频繁，请稍后再试\'], 429);
    return;
}
</code></pre>

<h2>登录失败限制</h2>
<pre><code>$key = \'login:\' . $username;

// 每个用户名每小时最多 5 次失败
if ($limiter->tooMany($key, 5, 3600)) {
    $this->flash(\'error\', \'登录失败次数过多，请 1 小时后再试\');
    $this->redirect(\'/user/login\');
    return;
}

// 验证密码...
if ($passwordCorrect) {
    $limiter->reset($key);  // 登录成功，清除计数
    Auth::login($user);
}
</code></pre>

<h2>短信验证码限制</h2>
<pre><code>$phone = $this->request->post(\'phone\');

// 同一手机号每天最多 5 条
if ($limiter->tooMany(\'sms:\' . $phone, 5, 86400)) {
    $this->json([\'error\' => \'今日验证码发送已达上限\']);
    return;
}

// 同一 IP 每小时最多 10 条（防止换号刷）
if ($limiter->tooMany(\'sms_ip:\' . $this->request->ip(), 10, 3600)) {
    $this->json([\'error\' => \'操作太频繁\']);
    return;
}
</code></pre>

<h2>API 方法</h2>
<table border="1" cellpadding="8" style="border-collapse:collapse; margin:1rem 0">
    <tr><th>方法</th><th>说明</th></tr>
    <tr><td>tooMany($key, $max, $sec)</td><td>检查+计数，返回 bool</td></tr>
    <tr><td>hit($key, $sec)</td><td>仅计数，返回当前次数</td></tr>
    <tr><td>remaining($key, $max, $sec)</td><td>剩余可用次数</td></tr>
    <tr><td>reset($key)</td><td>清除计数</td></tr>
</table>
'],

// ─── 第26课：Cookie 与 Str 工具 ───
[
'title' => '第26课：Cookie 与 Str — 安全存储与字符串利器',
'slug'  => 'tutorial-26-cookie-str',
'excerpt' => 'Cookie 安全封装（HttpOnly/SameSite/加密）+ Str 字符串工具（slug/uuid/mask 等 18 个方法）。',
'body'  => '
<h2>Cookie 封装</h2>
<pre><code>use Lib\Cookie;

// 自动从 config 读取安全属性
$cookie = new Cookie($this->config);

// 基础操作
$cookie->set(\'theme\', \'dark\', 86400);    // 1天
$cookie->get(\'theme\');                     // \'dark\'
$cookie->has(\'theme\');                     // true
$cookie->delete(\'theme\');

// 加密存储（需要 config 中配置 app_key）
$cookie->setEncrypted(\'token\', $sensitiveData, 3600);
$plain = $cookie->getEncrypted(\'token\');   // 自动解密
</code></pre>

<h3>安全属性</h3>
<table border="1" cellpadding="8" style="border-collapse:collapse; margin:1rem 0">
    <tr><th>属性</th><th>默认值</th><th>作用</th></tr>
    <tr><td>HttpOnly</td><td>true</td><td>JS 无法读取（防 XSS）</td></tr>
    <tr><td>Secure</td><td>自动检测</td><td>仅 HTTPS 传输</td></tr>
    <tr><td>SameSite</td><td>Lax</td><td>防 CSRF 攻击</td></tr>
</table>

<h2>Str 字符串工具</h2>
<pre><code>use Lib\Str;

// 生成 URL 友好的 slug
Str::slug(\'Hello World!\');        // \'hello-world\'
Str::slug(\'第一篇文章\');          // \'第一篇文章\'

// 安全随机字符串
Str::random(32);                  // \'a1B2c3D4e5F6...\'
Str::random(16, \'hex\');           // \'3a7f2b1c...\'

// UUID
Str::uuid();                      // \'550e8400-e29b-41d4-a716-446655440000\'

// 截断
Str::limit(\'很长的文本...\', 50);   // 保留 50 字+省略号

// 命名转换
Str::camel(\'user_name\');          // \'userName\'
Str::snake(\'userName\');           // \'user_name\'
Str::studly(\'user_name\');         // \'UserName\'
Str::kebab(\'userName\');           // \'user-name\'

// 敏感信息遮罩
Str::mask(\'13812345678\', 3, 4);   // \'138****5678\'
Str::mask(\'admin@qq.com\', 2, 4);  // \'ad****qq.com\'

// 判断
Str::contains(\'hello\', \'ell\');    // true
Str::startsWith(\'hello\', \'he\');   // true
Str::endsWith(\'hello\', \'lo\');     // true
Str::isEmail(\'test@foo.com\');     // true
Str::isUrl(\'https://foo.com\');    // true
Str::isJson(\'{"a":1}\');           // true

// 提取数字
Str::digits(\'电话 138-1234\');      // \'1381234\'

// 单词数（支持中文）
Str::wordCount(\'Hello 世界\');      // 2
</code></pre>
'],

// ─── 第27课：多数据库支持 ───
[
'title' => '第27课：多数据库支持 — MySQL / PostgreSQL / SQLite',
'slug'  => 'tutorial-27-multi-db',
'excerpt' => 'DB 类自动检测驱动，标识符引用自动适配。只改 DSN 配置即可切换数据库。',
'body'  => '
<h2>支持的数据库</h2>
<table border="1" cellpadding="8" style="border-collapse:collapse; margin:1rem 0; width:100%">
    <tr><th>数据库</th><th>DSN 格式</th><th>标识符引用</th></tr>
    <tr><td>MySQL / MariaDB</td><td>mysql:host=localhost;dbname=myapp</td><td>反引号 `name`</td></tr>
    <tr><td>PostgreSQL</td><td>pgsql:host=localhost;dbname=myapp</td><td>双引号 "name"</td></tr>
    <tr><td>SQLite</td><td>sqlite:/path/to/database.db</td><td>双引号 "name"</td></tr>
</table>

<h2>配置切换</h2>
<pre><code>// config/config.php — MySQL
\'db\' => [
    \'dsn\'      => \'mysql:host=localhost;dbname=myapp;charset=utf8mb4\',
    \'user\'     => \'root\',
    \'password\' => \'123456\',
],

// PostgreSQL — 只改 DSN
\'db\' => [
    \'dsn\'      => \'pgsql:host=localhost;dbname=myapp\',
    \'user\'     => \'postgres\',
    \'password\' => \'123456\',
],

// SQLite — 无需用户名密码
\'db\' => [
    \'dsn\' => \'sqlite:\' . ROOT . \'/database.db\',
],
</code></pre>

<h2>自动适配原理</h2>
<p>DB 类在构造时从 DSN 自动检测驱动类型：</p>
<pre><code>// 内部自动处理：
// MySQL:      SELECT * FROM `posts` WHERE `id` = ?
// PostgreSQL: SELECT * FROM "posts" WHERE "id" = ?
// SQLite:     SELECT * FROM "posts" WHERE "id" = ?

// INSERT 返回 ID 的差异也自动处理：
// MySQL:      lastInsertId()
// PostgreSQL: INSERT ... RETURNING id
</code></pre>

<h2>业务代码无需修改</h2>
<p>所有链式查询 API 在三种数据库上完全一致：</p>
<pre><code>// 这段代码在 MySQL、PostgreSQL、SQLite 上都能运行
$posts = $this->db->table(\'posts\')
    ->where(\'status=?\', [\'published\'])
    ->order(\'created_at DESC\')
    ->limit(10)
    ->fetchAll();

$id = $this->db->table(\'posts\')->timestamps()->insert([
    \'title\' => \'Hello\',
    \'body\'  => \'World\',
]);
</code></pre>

<h2>注意事项</h2>
<ul>
    <li>MySQL 的 <code>AUTO_INCREMENT</code> 在 PostgreSQL 中用 <code>SERIAL</code> 或 <code>GENERATED ALWAYS AS IDENTITY</code></li>
    <li>迁移文件中的建表 SQL 需根据目标数据库调整（框架不做 DDL 抽象）</li>
    <li>原生 SQL（<code>query()</code>、<code>exec()</code>）需注意方言差异</li>
    <li>SQLite 不需要 user 和 password 参数</li>
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
