<?php
// H2CMS 数据库迁移：创建所有核心表

// users
$db->exec("CREATE TABLE IF NOT EXISTS `users` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username`   VARCHAR(50) NOT NULL UNIQUE,
    `email`      VARCHAR(100) NOT NULL UNIQUE,
    `password`   VARCHAR(255) NOT NULL,
    `role`       ENUM('admin','editor','user') NOT NULL DEFAULT 'user',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// categories
$db->exec("CREATE TABLE IF NOT EXISTS `categories` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`       VARCHAR(100) NOT NULL,
    `slug`       VARCHAR(100) NOT NULL UNIQUE,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// posts
$db->exec("CREATE TABLE IF NOT EXISTS `posts` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title`           VARCHAR(200) NOT NULL,
    `slug`            VARCHAR(191) NOT NULL UNIQUE,
    `body`            TEXT NOT NULL,
    `excerpt`         VARCHAR(500) DEFAULT '',
    `category_id`     INT UNSIGNED DEFAULT NULL,
    `user_id`         INT UNSIGNED NOT NULL,
    `featured_image`  VARCHAR(255) DEFAULT '',
    `status`          ENUM('draft','published') NOT NULL DEFAULT 'draft',
    `created_at`      DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`      DATETIME DEFAULT NULL,
    INDEX `idx_status` (`status`),
    INDEX `idx_category` (`category_id`),
    INDEX `idx_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// pages
$db->exec("CREATE TABLE IF NOT EXISTS `pages` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title`      VARCHAR(191) NOT NULL,
    `slug`       VARCHAR(191) NOT NULL UNIQUE,
    `body`       TEXT NOT NULL,
    `status`     ENUM('draft','published') NOT NULL DEFAULT 'draft',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// comments
$db->exec("CREATE TABLE IF NOT EXISTS `comments` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `post_id`      INT UNSIGNED NOT NULL,
    `author_name`  VARCHAR(100) NOT NULL,
    `author_email` VARCHAR(100) DEFAULT '',
    `body`         TEXT NOT NULL,
    `approved`     TINYINT(1) NOT NULL DEFAULT 0,
    `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_post` (`post_id`),
    INDEX `idx_approved` (`approved`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// queue jobs table
$db->exec("CREATE TABLE IF NOT EXISTS `queue_jobs` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `job`         VARCHAR(255) NOT NULL,
    `payload`     TEXT NOT NULL,
    `attempts`    TINYINT UNSIGNED DEFAULT 0,
    `available_at` INT UNSIGNED DEFAULT 0,
    `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// migrations table
$db->exec("CREATE TABLE IF NOT EXISTS `migrations` (
    `id`        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `file`      VARCHAR(255) NOT NULL,
    `batch`     INT UNSIGNED NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Seed default admin user (password: admin123)
$hash = password_hash('admin123', PASSWORD_DEFAULT);
$db->exec("INSERT IGNORE INTO `users` (username, email, password, role) VALUES ('admin', 'admin@h2cms.local', '$hash', 'admin')");

// Seed categories
$db->exec("INSERT IGNORE INTO `categories` (name, slug) VALUES
    ('技术', 'tech'),
    ('生活', 'life'),
    ('教程', 'tutorial')
");

// Seed sample posts
$db->exec("INSERT IGNORE INTO `posts` (title, slug, body, excerpt, category_id, user_id, status) VALUES
    ('欢迎使用 H2CMS', 'welcome', '<p>这是第一篇文章，H2CMS 是基于 H2PHP 框架构建的内容管理系统。</p><p>H2PHP 是一个极简的 PHP MVC 框架，零依赖、目录即路由、性能优先。</p>', '这是第一篇文章，H2CMS 是基于 H2PHP 框架构建的内容管理系统。', 1, 1, 'published'),
    ('H2PHP 路由系统详解', 'h2php-routing', '<p>H2PHP 的路由采用目录即路由的设计，URL 结构与文件目录一一对应。</p><p>例如访问 /user/login/submit 会自动映射到 app/user/login.php 文件的 submit() 方法。</p>', 'H2PHP 的路由采用目录即路由的设计。', 3, 1, 'published'),
    ('使用链式查询操作数据库', 'chainable-db', '<p>H2PHP 内置了轻量级的 PDO 封装，支持链式查询。</p><p>示例：\$this->db->table(\"users\")->where(\"status=?\", [1])->order(\"id DESC\")->fetchAll();</p>', 'H2PHP 内置了轻量级的 PDO 封装，支持链式查询。', 3, 1, 'published'),
    ('我的周末生活', 'my-weekend', '<p>周末去公园散步，天气非常好。</p>', '周末去公园散步。', 2, 1, 'published'),
    ('中间件系统介绍', 'middleware-intro', '<p>H2PHP 支持洋葱模型中间件管道，可以在控制器之前执行可复用的处理逻辑。</p>', 'H2PHP 支持洋葱模型中间件管道。', 1, 1, 'published')
");

// Seed sample page
$db->exec("INSERT IGNORE INTO `pages` (title, slug, body, status) VALUES
    ('关于我们', 'about', '<h2>关于 H2CMS</h2><p>H2CMS 是一个基于 H2PHP 框架构建的内容管理系统演示项目。</p><p>它展示了 H2PHP 框架的全部功能：路由、数据库、中间件、验证、缓存、队列、事件、日志、邮件等。</p>', 'published')
");

// Seed sample comments
$db->exec("INSERT IGNORE INTO `comments` (post_id, author_name, author_email, body, approved) VALUES
    (1, '张三', 'zhang@example.com', '很不错的文章，期待更多内容！', 1),
    (1, '李四', 'li@example.com', 'H2CMS 看起来很轻量。', 1),
    (2, '王五', 'wang@example.com', '路由设计很直观！', 1)
");
