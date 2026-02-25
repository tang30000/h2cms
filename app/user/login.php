<?php
/**
 * 用户登录/注册/登出
 *
 * 使用 Auth 组件：密码哈希、Session 管理
 */
use Lib\Auth;

class main extends \Lib\Core
{
    protected array $skipBefore = ['index', 'submit', 'register', 'doRegister'];

    public function index(): void
    {
        // 已登录直接跳后台
        if (Auth::check()) {
            $this->redirect('/admin/dashboard');
            return;
        }

        $this->layout('front');
        $this->setMulti([
            'title'     => '登录',
            'csrfField' => $this->csrfField(),
        ]);
        $this->render();
    }

    public function submit(): void
    {
        $this->csrfVerify();

        $v = $this->validate($_POST, [
            'username' => 'required',
            'password' => 'required',
        ], [
            'username' => '用户名',
            'password' => '密码',
        ]);

        if ($v->fails()) {
            $this->flash('error', $v->firstError());
            $this->redirect('/user/login');
            return;
        }

        $user = $this->db->table('users')->where('username=?', [$_POST['username']])->fetchOne();

        // 使用 Auth::verifyPassword() 替代手写 password_verify
        if (!$user || !Auth::verifyPassword($_POST['password'], $user['password'])) {
            $this->log('warning', '登录失败', ['username' => $_POST['username'], 'ip' => $this->request->ip()]);
            $this->flash('error', '用户名或密码错误');
            $this->redirect('/user/login');
            return;
        }

        // 使用 Auth::login() 写入 Session
        Auth::login([
            'id'       => $user['id'],
            'username' => $user['username'],
            'email'    => $user['email'],
            'role'     => $user['role'],
        ]);

        $this->log('info', '用户登录', ['user_id' => $user['id'], 'ip' => $this->request->ip()]);
        $this->flash('success', '欢迎回来，' . $user['username'] . '！');
        $this->redirect('/admin/dashboard');
    }

    public function register(): void
    {
        $this->layout('front');
        $this->setMulti([
            'title'     => '注册',
            'csrfField' => $this->csrfField(),
        ]);
        $this->render();
    }

    public function doRegister(): void
    {
        $this->csrfVerify();

        $v = $this->validate($_POST, [
            'username' => 'required|min_len:3|max_len:20|unique:users,username',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min_len:6|confirmed',
        ], [
            'username' => '用户名',
            'email'    => '邮箱',
            'password' => '密码',
        ]);

        if ($v->fails()) {
            $this->flash('error', $v->firstError());
            $this->redirect('/user/login/register');
            return;
        }

        // 使用 Auth::hashPassword() 替代手写 password_hash
        $this->db->table('users')->timestamps()->insert([
            'username' => $_POST['username'],
            'email'    => $_POST['email'],
            'password' => Auth::hashPassword($_POST['password']),
            'role'     => 'user',
        ]);

        $this->log('info', '新用户注册', ['username' => $_POST['username']]);
        $this->flash('success', '注册成功，请登录');
        $this->redirect('/user/login');
    }

    public function logout(): void
    {
        $this->log('info', '用户登出', ['user_id' => Auth::id()]);

        // 使用 Auth::logout() 替代手写 unset
        Auth::logout();

        $this->flash('success', '已退出登录');
        $this->redirect('/');
    }
}
