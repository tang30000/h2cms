<?php
$pdo = new PDO('mysql:host=localhost;dbname=h2cms;charset=utf8mb4', 'root', 'usbw');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$body = <<<'HTML'
<h2>Composer 是什么？</h2>
<p>Composer 是 PHP 的包管理器，类似 Node.js 的 npm、Python 的 pip。它负责下载、更新、自动加载第三方库。</p>

<h2>安装第三方包</h2>
<pre><code># 在项目根目录执行
composer require 包名

# 例如
composer require phpmailer/phpmailer
composer require nesbot/carbon
composer require ramsey/uuid
</code></pre>

<h2>目录结构</h2>
<p>安装后的文件全部放在根目录的 <code>vendor/</code> 文件夹下，按 <code>厂商名/包名</code> 分级：</p>
<pre><code>d:\prg\web\h2php\
├── app/                    ← 你的业务代码
├── lib/                    ← 框架内置组件（20个）
├── config/
├── vendor/                 ← Composer 管理（自动生成，不要手动修改）
│   ├── autoload.php        ← 自动加载入口
│   ├── composer/           ← Composer 自身的加载器
│   ├── nesbot/
│   │   └── carbon/         ← Carbon 日期库源码
│   ├── ramsey/
│   │   └── uuid/           ← UUID 库源码
│   ├── phpmailer/
│   │   └── phpmailer/      ← PHPMailer 源码
│   └── ...                 ← 包的依赖也在这里
├── composer.json           ← 声明依赖（你编辑这个）
├── composer.lock           ← 锁定版本（自动生成，提交到 Git）
└── index.php               ← require vendor/autoload.php
</code></pre>

<h3>重要规则</h3>
<table border="1" cellpadding="8" style="border-collapse:collapse; margin:1rem 0; width:100%">
    <tr><th>规则</th><th>说明</th></tr>
    <tr><td>不要手动修改 vendor/</td><td>Composer 管理的文件，更新时会被覆盖</td></tr>
    <tr><td>vendor/ 加入 .gitignore</td><td>不提交到 Git（H2PHP 已默认配置）</td></tr>
    <tr><td>composer.lock 要提交</td><td>保证团队成员和服务器安装相同版本</td></tr>
    <tr><td>用 composer remove 卸载</td><td>不要手动删除 vendor 里的文件夹</td></tr>
</table>

<h2>加载原理</h2>
<p>H2PHP 的入口 <code>index.php</code> 包含如下代码：</p>
<pre><code>// 加载 Composer 自动加载器
if (file_exists(ROOT . '/vendor/autoload.php')) {
    require ROOT . '/vendor/autoload.php';
}
</code></pre>
<p>这一行让所有 Composer 包自动可用  。安装后直接 <code>use 类名</code> 即可，无需手动 <code>require</code>。</p>

<h2>部署流程</h2>
<pre><code># 1. 服务器拉取代码
git pull

# 2. 安装依赖（根据 composer.lock 精确还原）
composer install --no-dev

# 3. 完成！所有第三方包自动恢复
</code></pre>
<p><code>--no-dev</code> 表示不安装开发依赖（如 PHPUnit），减小生产环境体积。</p>

<h2>常用命令</h2>
<table border="1" cellpadding="8" style="border-collapse:collapse; margin:1rem 0; width:100%">
    <tr><th>命令</th><th>作用</th></tr>
    <tr><td>composer require 包名</td><td>安装新包</td></tr>
    <tr><td>composer remove 包名</td><td>卸载包</td></tr>
    <tr><td>composer update</td><td>更新所有包到最新兼容版本</td></tr>
    <tr><td>composer update 包名</td><td>只更新指定包</td></tr>
    <tr><td>composer install</td><td>根据 lock 文件安装（部署用）</td></tr>
    <tr><td>composer show</td><td>列出已安装的所有包</td></tr>
    <tr><td>composer dump-autoload</td><td>重新生成自动加载文件</td></tr>
</table>

<h2>实战一：Carbon — 日期时间处理</h2>
<pre><code># 安装
composer require nesbot/carbon
</code></pre>
<pre><code>use Carbon\Carbon;

// 当前时间
$now = Carbon::now();
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
$mail->Body    = '&lt;h1&gt;Hi！&lt;/h1&gt;&lt;p&gt;感谢注册。&lt;/p&gt;';
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
<p>Laravel 的很多组件是<strong>独立包</strong>，可以脱离 Laravel 在任何 PHP 项目中使用：</p>

<h3>illuminate/support — Collection 集合</h3>
<pre><code># 安装
composer require illuminate/support
</code></pre>
<pre><code>use Illuminate\Support\Collection;

$posts = $this->db->table('posts')->fetchAll();
$collection = collect($posts);

// 链式操作
$result = $collection
    ->where('status', 'published')
    ->sortByDesc('created_at')
    ->groupBy('category_id')
    ->map(function($group) { return $group->count(); });

// 聚合
$collection->sum('views');           // 总浏览量
$collection->pluck('title');         // 提取标题列
$collection->unique('category_id'); // 去重
$collection->take(5);               // 前5条
</code></pre>

<h3>illuminate/validation — 验证器</h3>
<pre><code># 安装
composer require illuminate/validation illuminate/translation
</code></pre>
<pre><code>use Illuminate\Validation\Factory;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;

$factory   = new Factory(new Translator(new ArrayLoader(), 'zh'));
$validator = $factory->make($_POST, [
    'email'    => 'required|email',
    'password' => 'required|min:6|max:20',
]);

if ($validator->fails()) {
    $errors = $validator->errors()->all();
}
</code></pre>

<h3>其他 Laravel 独立组件</h3>
<pre><code>composer require illuminate/cache       # 多驱动缓存
composer require illuminate/events      # 事件调度
composer require illuminate/filesystem  # 文件系统(S3/本地)
composer require illuminate/pagination  # 分页
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

<h2>H2PHP 内置 vs 第三方包</h2>
<table border="1" cellpadding="8" style="border-collapse:collapse; margin:1rem 0">
    <tr><th></th><th>内置库 (Lib\)</th><th>Composer 包</th></tr>
    <tr><td>安装方式</td><td>框架自带</td><td>composer require</td></tr>
    <tr><td>存放位置</td><td>lib/ 目录</td><td>vendor/ 目录</td></tr>
    <tr><td>体积</td><td>极小，单文件</td><td>可能较大</td></tr>
    <tr><td>依赖</td><td>零依赖</td><td>可能引入更多依赖</td></tr>
    <tr><td>适合</td><td>轻量项目</td><td>需要专业功能时</td></tr>
    <tr><td>提交到 Git?</td><td>是，框架一部分</td><td>否，.gitignore 忽略</td></tr>
    <tr><td>共存</td><td colspan="2">完全兼容，可同时使用</td></tr>
</table>
HTML;

$pdo->prepare("UPDATE posts SET body=? WHERE slug='tutorial-28-composer'")->execute([$body]);
echo "Updated!\n";
