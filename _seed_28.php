<?php
$pdo = new PDO('mysql:host=localhost;dbname=h2cms;charset=utf8mb4', 'root', 'usbw');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("SET NAMES utf8mb4");

$cat = $pdo->query("SELECT id FROM categories WHERE slug='tutorial'")->fetch(PDO::FETCH_ASSOC);
$catId = $cat['id'];

$title   = '第28课：Composer 生态 — 在 H2PHP 中使用第三方库';
$slug    = 'tutorial-28-composer';
$excerpt = '通过 Composer 安装 Laravel 组件、PHPMailer、图片处理等第三方库，让轻量框架也能拥有强大的生态。';

$body = <<<'HTML'
<h2>为什么能用 Composer？</h2>
<p>H2PHP 的 <code>composer.json</code> 已配置 PSR-4 自动加载，<code>index.php</code> 引入了 <code>vendor/autoload.php</code>。
所以任何通过 Composer 安装的包都能直接 <code>use</code>，<strong>零额外配置</strong>。</p>

<h2>安装第三方包</h2>
<pre><code># 在项目根目录执行
composer require 包名

# 例如
composer require phpmailer/phpmailer
composer require intervention/image
composer require nesbot/carbon
composer require ramsey/uuid
</code></pre>

<h2>实战一：Carbon — 日期时间处理</h2>
<pre><code># 安装
composer require nesbot/carbon
</code></pre>
<pre><code>// 控制器中使用
use Carbon\Carbon;

// 当前时间
$now = Carbon::now();                    // 2026-02-25 14:30:00
$now->format('Y年m月d日 H:i');           // 2026年02月25日 14:30

// 相对时间（中文）
Carbon::setLocale('zh');
$post['created_at'] = Carbon::parse($post['created_at'])->diffForHumans();
// "3小时前"、"2天前"、"1个月前"

// 日期计算
$tomorrow   = Carbon::tomorrow();
$nextWeek   = Carbon::now()->addDays(7);
$lastMonth  = Carbon::now()->subMonth();

// 日期比较
$deadline = Carbon::parse('2026-12-31');
$deadline->isPast();                     // false
$deadline->diffInDays(Carbon::now());    // 天数差
</code></pre>

<h2>实战二：Ramsey UUID — 唯一标识生成</h2>
<pre><code># 安装
composer require ramsey/uuid
</code></pre>
<pre><code>use Ramsey\Uuid\Uuid;

// 生成 UUID v4（随机）
$uuid = Uuid::uuid4()->toString();
// "550e8400-e29b-41d4-a716-446655440000"

// 作为订单号
$orderNo = 'ORD-' . str_replace('-', '', Uuid::uuid4()->toString());

// 用在数据库中
$this->db->table('orders')->insert([
    'uuid'    => Uuid::uuid4()->toString(),
    'user_id' => Auth::id(),
    'total'   => 99.00,
]);
</code></pre>

<h2>实战三：PHPMailer — 专业邮件发送</h2>
<pre><code># 安装
composer require phpmailer/phpmailer
</code></pre>
<pre><code>use PHPMailer\PHPMailer\PHPMailer;

$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host       = 'smtp.qq.com';
$mail->SMTPAuth   = true;
$mail->Username   = 'your@qq.com';
$mail->Password   = '授权码';
$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
$mail->Port       = 465;
$mail->CharSet    = 'UTF-8';

$mail->setFrom('your@qq.com', 'H2CMS');
$mail->addAddress('user@example.com', '用户名');
$mail->isHTML(true);
$mail->Subject = '欢迎注册';
$mail->Body    = '&lt;h1&gt;Hi！&lt;/h1&gt;&lt;p&gt;感谢注册 H2CMS。&lt;/p&gt;';

$mail->send();
</code></pre>

<h2>实战四：Intervention Image — 图片处理</h2>
<pre><code># 安装
composer require intervention/image
</code></pre>
<pre><code>use Intervention\Image\ImageManagerStatic as Image;

// 缩放头像
Image::make('uploads/avatar.jpg')
    ->resize(200, 200)
    ->save('uploads/avatar_thumb.jpg');

// 添加水印
Image::make('uploads/photo.jpg')
    ->insert('static/img/watermark.png', 'bottom-right', 10, 10)
    ->save();

// 裁剪 + 转 WebP
Image::make('uploads/banner.jpg')
    ->crop(800, 400)
    ->encode('webp', 80)
    ->save('uploads/banner.webp');
</code></pre>

<h2>实战五：Laravel 组件 — 单独使用</h2>
<p>Laravel 的很多组件是<strong>独立包</strong>，可以脱离 Laravel 在任何项目中使用：</p>

<h3>illuminate/support — Collection 集合操作</h3>
<pre><code># 安装
composer require illuminate/support
</code></pre>
<pre><code>use Illuminate\Support\Collection;

$posts = $this->db->table('posts')->fetchAll();
$collection = collect($posts);

// 链式操作
$result = $collection
    ->where('status', 'published')               // 过滤
    ->sortByDesc('created_at')                   // 排序
    ->groupBy('category_id')                     // 分组
    ->map(function($group) {                      // 转换
        return $group->count();
    });

// 聚合
$collection->sum('views');                        // 总浏览量
$collection->avg('views');                        // 平均浏览量
$collection->pluck('title');                      // 提取标题列
$collection->unique('category_id')->count();      // 分类数
$collection->take(5);                             // 前5条
$collection->chunk(10);                           // 每10条分组
</code></pre>

<h3>illuminate/validation — 验证器</h3>
<pre><code># 安装
composer require illuminate/validation illuminate/translation
</code></pre>
<pre><code>use Illuminate\Validation\Factory;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;

$loader     = new ArrayLoader();
$translator = new Translator($loader, 'zh');
$factory    = new Factory($translator);

$validator  = $factory->make($_POST, [
    'email'    => 'required|email',
    'password' => 'required|min:6|max:20',
    'name'     => 'required|between:2,20',
]);

if ($validator->fails()) {
    $errors = $validator->errors()->all();
}
</code></pre>

<h3>其他可单独使用的 Laravel 组件</h3>
<pre><code>composer require illuminate/cache          # 多驱动缓存
composer require illuminate/events         # 事件调度
composer require illuminate/filesystem     # 文件系统
composer require illuminate/pagination     # 分页
</code></pre>

<h2>常用第三方包推荐</h2>
<table border="1" cellpadding="8" style="border-collapse:collapse; margin:1rem 0; width:100%">
    <tr><th>包名</th><th>用途</th><th>安装命令</th></tr>
    <tr><td>nesbot/carbon</td><td>日期时间处理</td><td>composer require nesbot/carbon</td></tr>
    <tr><td>ramsey/uuid</td><td>UUID 生成</td><td>composer require ramsey/uuid</td></tr>
    <tr><td>phpmailer/phpmailer</td><td>邮件发送</td><td>composer require phpmailer/phpmailer</td></tr>
    <tr><td>intervention/image</td><td>图片处理</td><td>composer require intervention/image</td></tr>
    <tr><td>illuminate/support</td><td>Collection 集合</td><td>composer require illuminate/support</td></tr>
    <tr><td>guzzlehttp/guzzle</td><td>HTTP 客户端</td><td>composer require guzzlehttp/guzzle</td></tr>
    <tr><td>vlucas/phpdotenv</td><td>.env 环境变量</td><td>composer require vlucas/phpdotenv</td></tr>
    <tr><td>monolog/monolog</td><td>专业日志</td><td>composer require monolog/monolog</td></tr>
    <tr><td>mpdf/mpdf</td><td>PDF 生成</td><td>composer require mpdf/mpdf</td></tr>
    <tr><td>phpoffice/phpspreadsheet</td><td>Excel 导入导出</td><td>composer require phpoffice/phpspreadsheet</td></tr>
    <tr><td>endroid/qr-code</td><td>二维码生成</td><td>composer require endroid/qr-code</td></tr>
</table>

<h2>原理说明</h2>
<p>H2PHP 的入口 <code>index.php</code> 包含如下代码：</p>
<pre><code>// 加载 Composer 自动加载器
if (file_exists(ROOT . '/vendor/autoload.php')) {
    require ROOT . '/vendor/autoload.php';
}
</code></pre>
<p>安装任何 Composer 包后，Composer 自动更新 <code>vendor/autoload.php</code>，
你只需 <code>use 类名</code> 即可使用，无需手动 <code>require</code> 文件。</p>

<h3>H2PHP 内置 vs 第三方包</h3>
<table border="1" cellpadding="8" style="border-collapse:collapse; margin:1rem 0">
    <tr><th></th><th>内置库 (Lib\)</th><th>Composer 包</th></tr>
    <tr><td>安装方式</td><td>框架自带</td><td>composer require</td></tr>
    <tr><td>体积</td><td>极小，单文件</td><td>可能较大</td></tr>
    <tr><td>依赖</td><td>零依赖</td><td>可能引入更多依赖</td></tr>
    <tr><td>适合</td><td>轻量项目</td><td>需要专业功能时</td></tr>
    <tr><td>共存</td><td colspan="2">完全兼容，可同时使用</td></tr>
</table>
HTML;

$exists = $pdo->prepare("SELECT id FROM posts WHERE slug=?");
$exists->execute([$slug]);
if ($exists->fetch()) {
    $pdo->prepare("UPDATE posts SET title=?, body=?, excerpt=? WHERE slug=?")->execute([$title, $body, $excerpt, $slug]);
    echo "UPDATE: {$title}\n";
} else {
    $pdo->prepare("INSERT INTO posts (title, slug, body, excerpt, category_id, user_id, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 1, 'published', NOW(), NOW())")->execute([$title, $slug, $body, $excerpt, $catId]);
    echo "INSERT: {$title}\n";
}
echo "Done!\n";
