<?php
/**
 * 文章详情 + 评论
 */
class main extends \Lib\Core
{
    public function view(int $id): void
    {
        $post = $this->db->table('posts')->softDeletes()->where("id=? AND status='published'", [$id])->fetch();
        if (!$post) {
            $this->abort(404, '文章不存在');
        }

        // 分类
        $category = null;
        if ($post['category_id']) {
            $category = $this->db->table('categories')->where('id=?', [$post['category_id']])->fetch();
        }

        // 评论（只显示已审核的）
        $comments = $this->db->table('comments')
            ->where('post_id=? AND approved=1', [$id])
            ->order('created_at DESC')
            ->fetchAll();

        // 事件：文章被浏览（演示事件系统）
        $this->fire('post.viewed', $post);

        $this->layout('front');
        $this->setMulti([
            'title'     => $post['title'],
            'post'      => $post,
            'category'  => $category,
            'comments'  => $comments,
            'csrfField' => $this->csrfField(),
        ]);
        $this->render();
    }

    /**
     * 提交评论
     */
    public function comment(int $postId): void
    {
        $this->csrfVerify();

        $v = $this->validate($_POST, [
            'author_name' => 'required|max_len:50',
            'body'        => 'required|min_len:3|max_len:1000',
        ], [
            'author_name' => '昵称',
            'body'        => '评论内容',
        ]);

        if ($v->fails()) {
            $this->flash('error', $v->firstError());
            $this->redirect("/post/index/view/{$postId}");
            return;
        }

        $this->db->table('comments')->timestamps()->insert([
            'post_id'      => $postId,
            'author_name'  => $_POST['author_name'],
            'author_email' => $_POST['author_email'] ?? '',
            'body'         => $_POST['body'],
            'approved'     => 0,
        ]);

        // 日志
        $this->log('info', '新评论提交', ['post_id' => $postId, 'author' => $_POST['author_name']]);

        $this->flash('success', '评论已提交，等待审核。');
        $this->redirect("/post/index/view/{$postId}");
    }
}
