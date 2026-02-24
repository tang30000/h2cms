<?php
/**
 * AdminAuth 中间件 — 后台登录检查
 *
 * 使用 Auth 组件替代直接操作 $_SESSION
 */
use Lib\Auth;

class AdminAuth
{
    public function handle(callable $next): void
    {
        // 使用 Auth::check() 替代直接检查 $_SESSION
        if (!Auth::check()) {
            header('Location: /user/login');
            exit;
        }

        // 只允许 admin 和 editor 角色
        $role = Auth::user()['role'] ?? '';
        if (!in_array($role, ['admin', 'editor'])) {
            http_response_code(403);
            echo '<h1>403 - 权限不足</h1><p>您没有后台管理权限。</p>';
            exit;
        }

        $next();
    }
}
