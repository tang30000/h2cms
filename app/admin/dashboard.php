<?php
/**
 * 后台仪表盘
 */
class main extends \Lib\Core
{
    protected array $middleware = ['AdminAuth'];

    public function index(): void
    {
        $stats = [
            'posts'      => $this->db->table('posts')->softDeletes()->count(),
            'categories' => $this->db->table('categories')->count(),
            'comments'   => $this->db->table('comments')->count(),
            'pending'    => $this->db->table('comments')->where('approved=0')->count(),
            'users'      => $this->db->table('users')->count(),
            'pages'      => $this->db->table('pages')->count(),
        ];

        $recentPosts = $this->db->table('posts')->softDeletes()->order('created_at DESC')->limit(5)->fetchAll();
        $recentComments = $this->db->table('comments')->order('created_at DESC')->limit(5)->fetchAll();

        $this->layout('admin');
        $this->setMulti([
            'pageTitle'      => '仪表盘',
            '_path'          => 'admin/dashboard',
            'stats'          => $stats,
            'recentPosts'    => $recentPosts,
            'recentComments' => $recentComments,
        ]);
        $this->render();
    }
}
