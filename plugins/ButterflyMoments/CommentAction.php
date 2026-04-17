<?php

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class ButterflyMoments_CommentAction extends \Widget\Base implements \Widget\ActionInterface
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
        $parentId = $this->request->filter('int')->get('parent_id');
        $replyAuthorName = trim((string) $this->request->get('reply_author_name'));
        $content = trim((string) $this->request->get('content'));
        $authorName = '';
        $authorMail = '';
        $authorId = 0;
        $clientIp = trim((string) $this->request->getIp());
        $isGuest = !$this->user->hasLogin();

        if ($this->user->hasLogin()) {
            $authorId = (int) $this->user->uid;
            $authorName = trim((string) $this->user->screenName);
            $authorMail = trim((string) $this->user->mail);
        } else {
            $authorName = trim((string) $this->request->get('author_name'));
            $authorMail = trim((string) $this->request->get('author_mail'));
        }

        if ($content === '') {
            $this->response->throwJson(['success' => 0, 'message' => '评论内容不能为空'], 422);
        }

        if ($authorName === '') {
            $this->response->throwJson(['success' => 0, 'message' => '请填写昵称'], 422);
        }

        if ($isGuest && $authorMail !== '' && !filter_var($authorMail, FILTER_VALIDATE_EMAIL)) {
            $this->response->throwJson(['success' => 0, 'message' => '邮箱格式不正确'], 422);
        }

        if ($isGuest) {
            $recent = butterflyMomentsGetRecentGuestComment($momentId, $clientIp, 30);
            if (!empty($recent)) {
                if (trim((string) $recent['content']) === $content) {
                    $this->response->throwJson(['success' => 0, 'message' => '请勿重复提交相同评论'], 429);
                }

                $this->response->throwJson(['success' => 0, 'message' => '评论太快了，请稍后再试'], 429);
            }
        }

        $result = butterflyMomentsCreateComment(
            $momentId,
            $authorId,
            $authorName,
            $authorMail,
            $content,
            $isGuest ? 'pending' : 'approved',
            $clientIp,
            $parentId,
            $replyAuthorName
        );
        if (empty($result)) {
            $this->response->throwJson(['success' => 0, 'message' => '动态不存在'], 404);
        }

        $this->response->throwJson([
            'success' => 1,
            'pending' => $result['status'] === 'pending' ? 1 : 0,
            'comment_count' => (int) $result['comment_count'],
            'comment' => [
                'id' => (int) $result['id'],
                'moment_id' => (int) $result['moment_id'],
                'author_name' => $result['author_name'],
                'author_mail' => $result['author_mail'],
                'parent_id' => (int) $result['parent_id'],
                'reply_author_name' => $result['reply_author_name'],
                'content' => nl2br(htmlspecialchars($result['content'], ENT_QUOTES, 'UTF-8')),
                'created_label' => date('m月d日 H:i', (int) $result['created']),
            ],
        ]);
    }
}
