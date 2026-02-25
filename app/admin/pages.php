<?php
class main extends \Lib\Core
{
    protected array $middleware = ['AdminAuth'];

    public function index(): void
    {
        $pages = $this->db->table('pages')->order('id DESC')->fetchAll();
        $this->layout('admin');
        $this->setMulti(['pageTitle' => '页面管理', '_path' => 'admin/pages', 'pages' => $pages]);
        $this->render();
    }

    public function create(): void
    {
        $this->layout('admin');
        $this->setMulti(['pageTitle' => '新建页面', '_path' => 'admin/pages', 'csrfField' => $this->csrfField()]);
        $this->render();
    }

    public function store(): void
    {
        $this->csrfVerify();
        $v = $this->validate($_POST, ['title' => 'required', 'body' => 'required'], ['title' => '标题', 'body' => '内容']);
        if ($v->fails()) { $this->flash('error', $v->firstError()); $this->redirect('/admin/pages/create'); return; }

        $slug = preg_replace('/[^a-zA-Z0-9\x{4e00}-\x{9fa5}]+/u', '-', $_POST['title']);
        $this->db->table('pages')->timestamps()->insert([
            'title'  => $_POST['title'],
            'slug'   => trim($slug, '-') . '-' . time(),
            'body'   => $_POST['body'],
            'status' => $_POST['status'] ?? 'draft',
        ]);
        $this->flash('success', '页面已创建');
        $this->redirect('/admin/pages');
    }

    public function edit(int $id): void
    {
        $page = $this->db->table('pages')->where('id=?', [$id])->fetchOne();
        if (!$page) $this->abort(404);
        $this->layout('admin');
        $this->setMulti(['pageTitle' => '编辑页面', '_path' => 'admin/pages', 'pageData' => $page, 'csrfField' => $this->csrfField()]);
        $this->render();
    }

    public function update(int $id): void
    {
        $this->csrfVerify();
        $this->db->table('pages')->timestamps()->where('id=?', [$id])->update([
            'title'  => $_POST['title'],
            'body'   => $_POST['body'],
            'status' => $_POST['status'] ?? 'draft',
        ]);
        $this->flash('success', '页面已更新');
        $this->redirect('/admin/pages');
    }

    public function delete(int $id): void
    {
        $this->db->table('pages')->where('id=?', [$id])->delete();
        $this->flash('success', '页面已删除');
        $this->redirect('/admin/pages');
    }
}
