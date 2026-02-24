<?php
/**
 * 静态页面
 */
class main extends \Lib\Core
{
    public function view(string $slug): void
    {
        $page = $this->db->table('pages')->where("slug=? AND status='published'", [$slug])->fetch();
        if (!$page) {
            $this->abort(404, '页面不存在');
        }

        $this->layout('front');
        $this->setMulti([
            'title'   => $page['title'],
            'pageData' => $page,
        ]);
        $this->render();
    }
}
