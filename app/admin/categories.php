<?php
class main extends \Lib\Core
{
    protected array $middleware = ['AdminAuth'];

    public function index(): void
    {
        $categories = $this->db->table('categories')->order('id DESC')->fetchAll();
        foreach ($categories as &$c) {
            $c['post_count'] = $this->db->table('posts')->softDeletes()->where('category_id=?', [$c['id']])->count();
        }
        unset($c);

        $this->layout('admin');
        $this->setMulti([
            'pageTitle'  => '分类管理',
            '_path'      => 'admin/categories',
            'categories' => $categories,
            'csrfField'  => $this->csrfField(),
        ]);
        $this->render();
    }

    public function store(): void
    {
        $this->csrfVerify();
        $v = $this->validate($_POST, ['name' => 'required|max_len:100'], ['name' => '分类名']);
        if ($v->fails()) { $this->flash('error', $v->firstError()); $this->redirect('/admin/categories'); return; }

        $slug = preg_replace('/[^a-zA-Z0-9\x{4e00}-\x{9fa5}]+/u', '-', $_POST['name']);
        $this->db->table('categories')->insert(['name' => $_POST['name'], 'slug' => trim($slug, '-')]);
        $this->flash('success', '分类已创建');
        $this->redirect('/admin/categories');
    }

    public function delete(int $id): void
    {
        $this->db->table('categories')->where('id=?', [$id])->delete();
        $this->flash('success', '分类已删除');
        $this->redirect('/admin/categories');
    }
}
