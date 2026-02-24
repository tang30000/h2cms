<?php
/**
 * 前台首页 — 最新文章列表 + 分页 + 分类侧边栏
 *
 * 使用 Pagination 独立分页组件
 */
use Lib\Pagination;

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

        // 使用 Pagination 独立分页器
        $total = $query->count();
        $pager = new Pagination($total, $page, $perPage);

        $posts = $query->order('id ASC')
            ->limit($perPage, $pager->offset())
            ->cache(60)
            ->fetchAll();

        $pager->setItems($posts);

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

        // 构建分页 URL
        $urlPattern = $keyword ? "/home/index/index/{page}?q={$keyword}" : ($catId ? "/home/index/index/{page}?cat={$catId}" : '/home/index/index/{page}');

        $this->layout('front');
        $this->set('title', $keyword ? "搜索: {$keyword}" : '首页');
        $this->setMulti([
            'posts'      => $posts,
            'categories' => $categories,
            'pager'      => $pager,
            'pagerLinks' => $pager->links($urlPattern),
            'keyword'    => $keyword,
            'catId'      => $catId,
            // 兼容旧视图
            'page'       => $pager->currentPage(),
            'totalPages' => $pager->totalPages(),
        ]);
        $this->render();
    }
}
