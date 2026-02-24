<?php
class main extends \Lib\Core
{
    protected array $middleware = ['AdminAuth'];

    public function index(): void
    {
        $comments = $this->db->table('comments')->order('created_at DESC')->fetchAll();
        // 附加文章标题
        foreach ($comments as &$c) {
            $post = $this->db->table('posts')->where('id=?', [$c['post_id']])->fetch();
            $c['post_title'] = $post['title'] ?? '(已删除)';
        }
        unset($c);

        $this->layout('admin');
        $this->setMulti(['pageTitle' => '评论管理', '_path' => 'admin/comments', 'comments' => $comments]);
        $this->render();
    }

    public function approve(int $id): void
    {
        $this->db->table('comments')->where('id=?', [$id])->update(['approved' => 1]);
        $this->flash('success', '评论已审核通过');
        $this->redirect('/admin/comments');
    }

    public function delete(int $id): void
    {
        $this->db->table('comments')->where('id=?', [$id])->delete();
        $this->flash('success', '评论已删除');
        $this->redirect('/admin/comments');
    }
}
