<?php
/**
 * 后台文章管理 — CRUD + 上传 + 软删除 + 事务
 *
 * 使用 Pagination 分页器、Str 字符串工具
 */
use Lib\Pagination;
use Lib\Str;

class main extends \Lib\Core
{
    protected array $middleware = ['AdminAuth'];

    /** 文章列表 */
    public function index(): void
    {
        $page    = max(1, (int)($this->request->get('page', 1)));
        $perPage = 10;
        $status  = $this->request->get('status', '');

        $query = $this->db->table('posts')->softDeletes();
        if ($status) {
            $query = $query->where('status=?', [$status]);
        }

        // 使用 Pagination 独立分页器
        $total = $query->count();
        $pager = new Pagination($total, $page, $perPage);
        $posts = $query->order('created_at DESC')->limit($perPage, $pager->offset())->fetchAll();

        // 附加分类名
        foreach ($posts as &$p) {
            if ($p['category_id']) {
                $cat = $this->db->table('categories')->where('id=?', [$p['category_id']])->fetchOne();
                $p['category_name'] = $cat['name'] ?? '-';
            }
        }
        unset($p);

        $urlPattern = $status ? "/admin/posts?page={page}&status={$status}" : '/admin/posts?page={page}';

        $this->layout('admin');
        $this->setMulti([
            'pageTitle'  => '文章管理',
            '_path'      => 'admin/posts',
            'posts'      => $posts,
            'pager'      => $pager,
            'pagerLinks' => $pager->links($urlPattern),
            'page'       => $pager->currentPage(),
            'totalPages' => $pager->totalPages(),
            'status'     => $status,
        ]);
        $this->render();
    }

    /** 新建文章页面 */
    public function create(): void
    {
        $categories = $this->db->table('categories')->order('name')->fetchAll();

        $this->layout('admin');
        $this->setMulti([
            'pageTitle'   => '新建文章',
            '_path'       => 'admin/posts',
            'categories'  => $categories,
            'csrfField'   => $this->csrfField(),
        ]);
        $this->render();
    }

    /** 保存新文章 */
    public function store(): void
    {
        $this->csrfVerify();

        $v = $this->validate($_POST, [
            'title' => 'required|max_len:200',
            'body'  => 'required',
        ], [
            'title' => '标题',
            'body'  => '内容',
        ]);

        if ($v->fails()) {
            $this->flash('error', $v->firstError());
            $this->redirect('/admin/posts/create');
            return;
        }

        $data = [
            'title'       => $_POST['title'],
            'slug'        => $this->makeSlug($_POST['title']),
            'body'        => $_POST['body'],
            'excerpt'     => mb_substr(strip_tags($_POST['body']), 0, 200),
            'category_id' => (int)($_POST['category_id'] ?? 0) ?: null,
            'user_id'     => $_SESSION['user']['id'],
            'status'      => $_POST['status'] ?? 'draft',
        ];

        // 特色图片上传
        if (!empty($_FILES['featured_image']['name'])) {
            $file = $this->upload('featured_image', 'static/uploads')
                ->maxSize(5 * 1024 * 1024)
                ->allowTypes(['jpg', 'jpeg', 'png', 'gif', 'webp']);
            if ($file->fails()) {
                $this->flash('error', '图片上传失败: ' . $file->error());
                $this->redirect('/admin/posts/create');
                return;
            }
            $data['featured_image'] = $file->path();
        }

        $id = $this->db->table('posts')->timestamps()->insert($data);

        // 触发事件
        $this->fire('post.created', ['id' => $id, 'title' => $data['title']]);
        $this->log('info', '文章创建', ['post_id' => $id, 'title' => $data['title']]);

        $this->flash('success', '文章已创建');
        $this->redirect('/admin/posts');
    }

    /** 编辑文章 */
    public function edit(int $id): void
    {
        $post = $this->db->table('posts')->softDeletes()->withTrashed()->where('id=?', [$id])->fetchOne();
        if (!$post) $this->abort(404);

        $categories = $this->db->table('categories')->order('name')->fetchAll();

        $this->layout('admin');
        $this->setMulti([
            'pageTitle'  => '编辑文章',
            '_path'      => 'admin/posts',
            'post'       => $post,
            'categories' => $categories,
            'csrfField'  => $this->csrfField(),
        ]);
        $this->render();
    }

    /** 更新文章 */
    public function update(int $id): void
    {
        $this->csrfVerify();

        $v = $this->validate($_POST, [
            'title' => 'required|max_len:200',
            'body'  => 'required',
        ], ['title' => '标题', 'body' => '内容']);

        if ($v->fails()) {
            $this->flash('error', $v->firstError());
            $this->redirect("/admin/posts/edit/{$id}");
            return;
        }

        $data = [
            'title'       => $_POST['title'],
            'body'        => $_POST['body'],
            'excerpt'     => mb_substr(strip_tags($_POST['body']), 0, 200),
            'category_id' => (int)($_POST['category_id'] ?? 0) ?: null,
            'status'      => $_POST['status'] ?? 'draft',
        ];

        if (!empty($_FILES['featured_image']['name'])) {
            $file = $this->upload('featured_image', 'static/uploads')
                ->maxSize(5 * 1024 * 1024)
                ->allowTypes(['jpg', 'jpeg', 'png', 'gif', 'webp']);
            if (!$file->fails()) {
                $data['featured_image'] = $file->path();
            }
        }

        $this->db->table('posts')->timestamps()->where('id=?', [$id])->update($data);
        $this->log('info', '文章更新', ['post_id' => $id]);

        $this->flash('success', '文章已更新');
        $this->redirect('/admin/posts');
    }

    /** 软删除 */
    public function delete(int $id): void
    {
        $this->db->table('posts')->softDeletes()->where('id=?', [$id])->softDelete();
        $this->log('info', '文章软删除', ['post_id' => $id]);
        $this->flash('success', '文章已移至回收站');
        $this->redirect('/admin/posts');
    }

    /** 恢复 */
    public function restore(int $id): void
    {
        $this->db->table('posts')->softDeletes()->where('id=?', [$id])->restore();
        $this->flash('success', '文章已恢复');
        $this->redirect('/admin/posts');
    }

    /** 回收站 */
    public function trash(): void
    {
        $posts = $this->db->table('posts')->softDeletes()->onlyTrashed()->order('deleted_at DESC')->fetchAll();

        $this->layout('admin');
        $this->setMulti([
            'pageTitle' => '回收站',
            '_path'     => 'admin/posts',
            'posts'     => $posts,
        ]);
        $this->render();
    }

    /** 彻底删除（含事务：同时删评论） */
    public function forceDelete(int $id): void
    {
        $this->db->transaction(function($db) use ($id) {
            $db->table('comments')->where('post_id=?', [$id])->delete();
            $db->table('posts')->where('id=?', [$id])->delete();
        });

        $this->log('warning', '文章彻底删除', ['post_id' => $id]);
        $this->flash('success', '文章已彻底删除');
        $this->redirect('/admin/posts/trash');
    }

    /** 生成 slug — 使用 Str::slug() */
    private function makeSlug(string $title): string
    {
        return Str::slug($title) . '-' . time();
    }
}
