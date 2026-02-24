<?php
/**
 * 前台首页 — 最新文章列表 + 分页 + 分类侧边栏
 */
class main extends \Lib\Core
{
    public function index(int $page = 1): void
    {
        $page    = max(1, $page);
        $perPage = 5;
        $keyword = $this->request->get('q', '');
        $catId   = (int)$this->request->get('cat', 0);

        $query = $this->db->table('posts')->softDeletes()->where('status=?', ['published']);

        if ($keyword) {
            $query = $query->where("status='published' AND (title LIKE ? OR body LIKE ?)", ["%{$keyword}%", "%{$keyword}%"]);
        }
        if ($catId) {
            $query = $query->where("status='published' AND category_id=?", [$catId]);
        }

        $total = $query->count();
        $posts = $query->order('id ASC')
            ->limit($perPage, ($page - 1) * $perPage)
            ->cache(60)
            ->fetchAll();

        // 获取每篇文章的分类名
        foreach ($posts as &$p) {
            if ($p['category_id']) {
                $cat = $this->db->table('categories')->where('id=?', [$p['category_id']])->cache(300)->fetch();
                $p['category_name'] = $cat['name'] ?? '';
            }
        }
        unset($p);

        // 侧边栏分类
        $categories = $this->db->table('categories')->order('name')->cache(300)->fetchAll();

        $this->layout('front');
        $this->set('title', $keyword ? "搜索: {$keyword}" : '首页');
        $this->setMulti([
            'posts'      => $posts,
            'categories' => $categories,
            'page'       => $page,
            'totalPages' => max(1, ceil($total / $perPage)),
            'keyword'    => $keyword,
            'catId'      => $catId,
        ]);
        $this->render();
    }
}
