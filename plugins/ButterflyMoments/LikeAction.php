<?php

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class ButterflyMoments_LikeAction extends \Widget\Base implements \Widget\ActionInterface
{
    protected function initComponents(int &$components)
    {
        $components = self::INIT_DB | self::INIT_USER | self::INIT_OPTIONS;
    }

    public function execute()
    {
    }

    public function action()
    {
        require_once __DIR__ . '/helpers.php';

        if (!$this->request->isPost()) {
            $this->response->throwJson(['success' => 0, 'message' => 'Method Not Allowed'], 405);
        }

        butterflyMomentsEnsureSchema();
        $momentId = $this->request->filter('int')->get('moment_id');
        $result = butterflyMomentsToggleLike(
            $momentId,
            $this->user->hasLogin() ? (int) $this->user->uid : 0,
            $this->request->getIp()
        );

        if (empty($result)) {
            $this->response->throwJson(['success' => 0, 'message' => 'Moment Not Found'], 404);
        }

        $this->response->throwJson([
            'success' => 1,
            'liked' => $result['liked'] ? 1 : 0,
            'like_count' => (int) $result['like_count'],
        ]);
    }
}
