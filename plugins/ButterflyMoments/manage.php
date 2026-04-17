<?php

if (!defined('__TYPECHO_ROOT_DIR__') || !defined('__TYPECHO_ADMIN__')) {
    exit;
}

require_once __DIR__ . '/helpers.php';

$user->pass('editor');
butterflyMomentsEnsureSchema();
$view = in_array($request->get('view'), ['comments', 'likes'], true) ? $request->get('view') : 'moments';
$commentStatus = in_array($request->get('status'), ['approved', 'pending'], true) ? $request->get('status') : 'all';
$filterMomentId = (int) $request->get('moment_id');
$page = max(1, (int) $request->get('page'));
$perPage = 20;

if ($request->isPost() && $request->get('do') === 'delete') {
    $security->protect();
    butterflyMomentsDelete((int) $request->get('id'));
    $response->redirect(butterflyMomentsPanelUrl('manage.php', ['notice' => 'deleted']));
}

if ($request->isPost() && $request->get('do') === 'delete-comment') {
    $security->protect();
    butterflyMomentsDeleteComment((int) $request->get('id'));
    $response->redirect(butterflyMomentsPanelUrl('manage.php', ['view' => 'comments', 'status' => $commentStatus, 'moment_id' => $filterMomentId ?: null, 'notice' => 'comment-deleted']));
}

if ($request->isPost() && $request->get('do') === 'approve-comment') {
    $security->protect();
    butterflyMomentsUpdateCommentStatus((int) $request->get('id'), 'approved');
    $response->redirect(butterflyMomentsPanelUrl('manage.php', ['view' => 'comments', 'status' => $commentStatus, 'moment_id' => $filterMomentId ?: null, 'notice' => 'comment-approved']));
}

if ($request->isPost() && $request->get('do') === 'pending-comment') {
    $security->protect();
    butterflyMomentsUpdateCommentStatus((int) $request->get('id'), 'pending');
    $response->redirect(butterflyMomentsPanelUrl('manage.php', ['view' => 'comments', 'status' => $commentStatus, 'moment_id' => $filterMomentId ?: null, 'notice' => 'comment-pending']));
}

if ($request->isPost() && $request->get('do') === 'delete-like') {
    $security->protect();
    butterflyMomentsDeleteLike((int) $request->get('id'));
    $response->redirect(butterflyMomentsPanelUrl('manage.php', ['view' => 'likes', 'moment_id' => $filterMomentId ?: null, 'notice' => 'like-deleted']));
}

$stats = butterflyMomentsFetchStats();
$totalRows = $view === 'comments'
    ? butterflyMomentsCountComments($commentStatus, $filterMomentId)
    : ($view === 'likes' ? butterflyMomentsCountLikes($filterMomentId) : butterflyMomentsCountList());
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$moments = $view === 'moments' ? butterflyMomentsFetchList($perPage, $page) : [];
$comments = $view === 'comments' ? butterflyMomentsFetchComments($perPage, $commentStatus, $filterMomentId, $page) : [];
$likes = $view === 'likes' ? butterflyMomentsFetchLikes($perPage, $filterMomentId, $page) : [];

function butterflyMomentsRenderPager($totalPages, $currentPage, array $query = [])
{
    $totalPages = (int) $totalPages;
    $currentPage = (int) $currentPage;
    if ($totalPages <= 1) {
        return;
    }

    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    if ($end - $start < 4) {
        if ($start === 1) {
            $end = min($totalPages, $start + 4);
        } elseif ($end === $totalPages) {
            $start = max(1, $end - 4);
        }
    }

    echo '<ul class="typecho-pager">';
    if ($currentPage > 1) {
        echo '<li><a href="', htmlspecialchars(butterflyMomentsPanelUrl('manage.php', array_merge($query, ['page' => $currentPage - 1])), ENT_QUOTES, 'UTF-8'), '">&laquo;</a></li>';
    }

    for ($i = $start; $i <= $end; $i++) {
        $class = $i === $currentPage ? ' class="current"' : '';
        echo '<li', $class, '><a href="', htmlspecialchars(butterflyMomentsPanelUrl('manage.php', array_merge($query, ['page' => $i])), ENT_QUOTES, 'UTF-8'), '">', $i, '</a></li>';
    }

    if ($currentPage < $totalPages) {
        echo '<li><a href="', htmlspecialchars(butterflyMomentsPanelUrl('manage.php', array_merge($query, ['page' => $currentPage + 1])), ENT_QUOTES, 'UTF-8'), '">&raquo;</a></li>';
    }
    echo '</ul>';
}

include __TYPECHO_ROOT_DIR__ . '/admin/header.php';
include __TYPECHO_ROOT_DIR__ . '/admin/menu.php';
?>
<main class="main">
    <div class="body container">
        <?php include __TYPECHO_ROOT_DIR__ . '/admin/page-title.php'; ?>
        <div class="row typecho-page-main" role="main">
            <div class="col-mb-12">
                <?php if ($request->get('notice') === 'saved'): ?>
                    <div class="message success"><?php _e('动态已保存'); ?></div>
                <?php elseif ($request->get('notice') === 'deleted'): ?>
                    <div class="message notice"><?php _e('动态已删除'); ?></div>
                <?php elseif ($request->get('notice') === 'comment-deleted'): ?>
                    <div class="message notice"><?php _e('评论已删除'); ?></div>
                <?php elseif ($request->get('notice') === 'comment-approved'): ?>
                    <div class="message success"><?php _e('评论已通过'); ?></div>
                <?php elseif ($request->get('notice') === 'comment-pending'): ?>
                    <div class="message notice"><?php _e('评论已改为待审'); ?></div>
                <?php elseif ($request->get('notice') === 'like-deleted'): ?>
                    <div class="message notice"><?php _e('点赞记录已删除'); ?></div>
                <?php endif; ?>

                <div class="typecho-list-operate">
                    <div class="operate">
                        <a class="btn primary btn-s" href="<?php echo butterflyMomentsPanelUrl('write.php'); ?>">发动态</a>
                    </div>
                </div>
                <div class="bf-moments-overview">
                    <div class="bf-moments-stat-card">
                        <strong><?php echo (int) $stats['moments']; ?></strong>
                        <span><?php _e('动态总数'); ?></span>
                    </div>
                    <div class="bf-moments-stat-card">
                        <strong><?php echo (int) $stats['published_moments']; ?></strong>
                        <span><?php _e('已发布动态'); ?></span>
                    </div>
                    <div class="bf-moments-stat-card">
                        <strong><?php echo (int) $stats['likes']; ?></strong>
                        <span><?php _e('点赞总数'); ?></span>
                    </div>
                    <div class="bf-moments-stat-card">
                        <strong><?php echo (int) $stats['approved_comments']; ?>/<?php echo (int) $stats['pending_comments']; ?></strong>
                        <span><?php _e('已通过/待审评论'); ?></span>
                    </div>
                </div>
                <div class="bf-moments-tabs">
                    <a class="bf-moments-tab<?php echo $view === 'moments' ? ' active' : ''; ?>" href="<?php echo butterflyMomentsPanelUrl('manage.php'); ?>"><?php _e('动态'); ?></a>
                    <a class="bf-moments-tab<?php echo $view === 'comments' ? ' active' : ''; ?>" href="<?php echo butterflyMomentsPanelUrl('manage.php', ['view' => 'comments']); ?>"><?php _e('评论'); ?></a>
                    <a class="bf-moments-tab<?php echo $view === 'likes' ? ' active' : ''; ?>" href="<?php echo butterflyMomentsPanelUrl('manage.php', ['view' => 'likes']); ?>"><?php _e('点赞'); ?></a>
                </div>

                <?php if ($view === 'comments'): ?>
                    <div class="bf-moments-subtabs">
                        <a class="bf-moments-subtab<?php echo $commentStatus === 'all' ? ' active' : ''; ?>" href="<?php echo butterflyMomentsPanelUrl('manage.php', ['view' => 'comments', 'moment_id' => $filterMomentId ?: null]); ?>"><?php _e('全部'); ?></a>
                        <a class="bf-moments-subtab<?php echo $commentStatus === 'pending' ? ' active' : ''; ?>" href="<?php echo butterflyMomentsPanelUrl('manage.php', ['view' => 'comments', 'status' => 'pending', 'moment_id' => $filterMomentId ?: null]); ?>"><?php _e('待审核'); ?></a>
                        <a class="bf-moments-subtab<?php echo $commentStatus === 'approved' ? ' active' : ''; ?>" href="<?php echo butterflyMomentsPanelUrl('manage.php', ['view' => 'comments', 'status' => 'approved', 'moment_id' => $filterMomentId ?: null]); ?>"><?php _e('已通过'); ?></a>
                    </div>
                    <?php if ($filterMomentId > 0): ?>
                        <div class="description" style="margin: 0 0 12px;">
                            <?php echo '当前只看动态 #' . $filterMomentId . ' 的评论'; ?>
                            <a href="<?php echo butterflyMomentsPanelUrl('manage.php', ['view' => 'comments', 'status' => $commentStatus === 'all' ? null : $commentStatus]); ?>"><?php _e('清除筛选'); ?></a>
                        </div>
                    <?php endif; ?>
                    <table class="typecho-list-table">
                        <thead>
                        <tr>
                            <th><?php _e('评论内容'); ?></th>
                            <th><?php _e('评论人'); ?></th>
                            <th><?php _e('所属动态'); ?></th>
                            <th><?php _e('状态'); ?></th>
                            <th><?php _e('时间'); ?></th>
                            <th><?php _e('操作'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($comments)): ?>
                            <?php foreach ($comments as $comment): ?>
                                <tr>
                                    <td>
                                        <div><?php echo nl2br(htmlspecialchars(butterflyMomentsPreview($comment['content'], 120), ENT_QUOTES, 'UTF-8')); ?></div>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($comment['author_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <?php if (!empty($comment['author_mail'])): ?>
                                            <div class="description"><?php echo htmlspecialchars($comment['author_mail'], ENT_QUOTES, 'UTF-8'); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong>#<?php echo (int) $comment['moment_id']; ?></strong>
                                        <div><?php echo htmlspecialchars(butterflyMomentsPreview((string) $comment['moment_content']), ENT_QUOTES, 'UTF-8'); ?></div>
                                        <?php if (!empty($comment['screenName'])): ?>
                                            <div class="description"><?php echo htmlspecialchars($comment['screenName'], ENT_QUOTES, 'UTF-8'); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $comment['status'] === 'approved' ? '已通过' : '待审核'; ?></td>
                                    <td><?php echo date('Y-m-d H:i', (int) $comment['created']); ?></td>
                                    <td class="bf-moments-actions">
                                        <?php if ($comment['status'] !== 'approved'): ?>
                                            <form method="post" action="<?php echo htmlspecialchars($security->getAdminUrl('extending.php?panel=ButterflyMoments/manage.php&view=comments&status=' . $commentStatus . ($filterMomentId > 0 ? '&moment_id=' . $filterMomentId : '')), ENT_QUOTES, 'UTF-8'); ?>" style="display:inline;">
                                                <input type="hidden" name="do" value="approve-comment">
                                                <input type="hidden" name="id" value="<?php echo (int) $comment['id']; ?>">
                                                <button type="submit" class="btn-link"><?php _e('通过'); ?></button>
                                            </form>
                                        <?php else: ?>
                                            <form method="post" action="<?php echo htmlspecialchars($security->getAdminUrl('extending.php?panel=ButterflyMoments/manage.php&view=comments&status=' . $commentStatus . ($filterMomentId > 0 ? '&moment_id=' . $filterMomentId : '')), ENT_QUOTES, 'UTF-8'); ?>" style="display:inline;">
                                                <input type="hidden" name="do" value="pending-comment">
                                                <input type="hidden" name="id" value="<?php echo (int) $comment['id']; ?>">
                                                <button type="submit" class="btn-link"><?php _e('设为待审'); ?></button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="post" action="<?php echo htmlspecialchars($security->getAdminUrl('extending.php?panel=ButterflyMoments/manage.php&view=comments&status=' . $commentStatus . ($filterMomentId > 0 ? '&moment_id=' . $filterMomentId : '')), ENT_QUOTES, 'UTF-8'); ?>" style="display:inline;">
                                            <input type="hidden" name="do" value="delete-comment">
                                            <input type="hidden" name="id" value="<?php echo (int) $comment['id']; ?>">
                                            <button type="submit" class="btn-link operate-delete" onclick="return confirm('确认删除这条评论吗？');"><?php _e('删除'); ?></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="none"><?php _e('还没有任何评论'); ?></td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                    <?php butterflyMomentsRenderPager($totalPages, $page, ['view' => 'comments', 'status' => $commentStatus === 'all' ? null : $commentStatus, 'moment_id' => $filterMomentId ?: null]); ?>
                <?php elseif ($view === 'likes'): ?>
                    <?php if ($filterMomentId > 0): ?>
                        <div class="description" style="margin: 0 0 12px;">
                            <?php echo '当前只看动态 #' . $filterMomentId . ' 的点赞'; ?>
                            <a href="<?php echo butterflyMomentsPanelUrl('manage.php', ['view' => 'likes']); ?>"><?php _e('清除筛选'); ?></a>
                        </div>
                    <?php endif; ?>
                    <table class="typecho-list-table">
                        <thead>
                        <tr>
                            <th><?php _e('点赞人'); ?></th>
                            <th><?php _e('所属动态'); ?></th>
                            <th><?php _e('来源'); ?></th>
                            <th><?php _e('时间'); ?></th>
                            <th><?php _e('操作'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($likes)): ?>
                            <?php foreach ($likes as $like): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo !empty($like['screenName']) ? htmlspecialchars($like['screenName'], ENT_QUOTES, 'UTF-8') : '游客'; ?></strong>
                                    </td>
                                    <td>
                                        <strong>#<?php echo (int) $like['moment_id']; ?></strong>
                                        <div><?php echo htmlspecialchars(butterflyMomentsPreview((string) $like['moment_content']), ENT_QUOTES, 'UTF-8'); ?></div>
                                    </td>
                                    <td>
                                        <?php if ((int) $like['user_id'] > 0): ?>
                                            <span><?php _e('登录用户'); ?></span>
                                        <?php else: ?>
                                            <span><?php _e('游客'); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($like['ip'])): ?>
                                            <div class="description"><?php echo htmlspecialchars($like['ip'], ENT_QUOTES, 'UTF-8'); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('Y-m-d H:i', (int) $like['created']); ?></td>
                                    <td class="bf-moments-actions">
                                        <form method="post" action="<?php echo htmlspecialchars($security->getAdminUrl('extending.php?panel=ButterflyMoments/manage.php&view=likes' . ($filterMomentId > 0 ? '&moment_id=' . $filterMomentId : '')), ENT_QUOTES, 'UTF-8'); ?>" style="display:inline;">
                                            <input type="hidden" name="do" value="delete-like">
                                            <input type="hidden" name="id" value="<?php echo (int) $like['id']; ?>">
                                            <button type="submit" class="btn-link operate-delete" onclick="return confirm('确认删除这条点赞记录吗？');"><?php _e('删除'); ?></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="none"><?php _e('还没有任何点赞记录'); ?></td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                    <?php butterflyMomentsRenderPager($totalPages, $page, ['view' => 'likes', 'moment_id' => $filterMomentId ?: null]); ?>
                <?php else: ?>
                    <table class="typecho-list-table">
                        <thead>
                        <tr>
                            <th><?php _e('内容'); ?></th>
                            <th><?php _e('作者'); ?></th>
                            <th><?php _e('状态'); ?></th>
                            <th><?php _e('互动'); ?></th>
                            <th><?php _e('时间'); ?></th>
                            <th><?php _e('操作'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($moments)): ?>
                            <?php foreach ($moments as $moment): ?>
                                <tr>
                                    <td>
                                        <strong>
                                            <a href="<?php echo butterflyMomentsPanelUrl('write.php', ['id' => (int) $moment['id']]); ?>">
                                                #<?php echo (int) $moment['id']; ?>
                                            </a>
                                        </strong>
                                        <div><?php echo htmlspecialchars(butterflyMomentsPreview($moment['content']), ENT_QUOTES, 'UTF-8'); ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars($moment['screenName'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo $moment['status'] === 'draft' ? '草稿' : '已发布'; ?></td>
                                    <td>
                                        <span>赞 <?php echo (int) $moment['like_count']; ?></span>
                                        <span> / 评 <?php echo (int) $moment['comment_count']; ?></span>
                                    </td>
                                    <td><?php echo date('Y-m-d H:i', (int) $moment['created']); ?></td>
                                    <td class="bf-moments-actions">
                                        <a href="<?php echo butterflyMomentsPanelUrl('write.php', ['id' => (int) $moment['id']]); ?>"><?php _e('编辑'); ?></a>
                                        <a href="<?php echo butterflyMomentsPanelUrl('manage.php', ['view' => 'comments', 'moment_id' => (int) $moment['id']]); ?>"><?php _e('查看评论'); ?></a>
                                        <a href="<?php echo butterflyMomentsPanelUrl('manage.php', ['view' => 'likes', 'moment_id' => (int) $moment['id']]); ?>"><?php _e('查看点赞'); ?></a>
                                        <form method="post" action="<?php echo htmlspecialchars($security->getAdminUrl('extending.php?panel=ButterflyMoments/manage.php'), ENT_QUOTES, 'UTF-8'); ?>" style="display:inline;">
                                            <input type="hidden" name="do" value="delete">
                                            <input type="hidden" name="id" value="<?php echo (int) $moment['id']; ?>">
                                            <button type="submit" class="btn-link operate-delete" onclick="return confirm('确认删除这条动态吗？');"><?php _e('删除'); ?></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="none"><?php _e('还没有任何朋友圈动态'); ?></td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                    <?php butterflyMomentsRenderPager($totalPages, $page); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
<style>
    .bf-moments-overview {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        margin: 0 0 16px;
    }

    .bf-moments-stat-card {
        padding: 14px 16px;
        border: 1px solid #ecece8;
        border-radius: 6px;
        background: #fff;
    }

    .bf-moments-stat-card strong {
        display: block;
        margin-bottom: 6px;
        color: #2f684f;
        font-size: 22px;
        line-height: 1;
    }

    .bf-moments-stat-card span {
        color: #777;
        font-size: 12px;
    }

    .bf-moments-tabs {
        display: flex;
        gap: 8px;
        margin: 12px 0 16px;
    }

    .bf-moments-tab {
        display: inline-block;
        padding: 6px 12px;
        border: 1px solid #d9d9d6;
        border-radius: 999px;
        color: #666;
        text-decoration: none;
    }

    .bf-moments-tab.active {
        border-color: #467b63;
        background: #467b63;
        color: #fff;
    }

    .bf-moments-subtabs {
        display: flex;
        gap: 8px;
        margin: 0 0 16px;
    }

    .bf-moments-subtab {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 999px;
        background: #f4f4f1;
        color: #666;
        text-decoration: none;
    }

    .bf-moments-subtab.active {
        background: #e4f0ea;
        color: #2f684f;
    }

    .bf-moments-actions {
        white-space: nowrap;
    }

    .bf-moments-actions a,
    .bf-moments-actions form {
        display: inline-block;
        margin-right: 8px;
    }

    .bf-moments-actions a:last-child,
    .bf-moments-actions form:last-child {
        margin-right: 0;
    }

    .typecho-list-table th:last-child,
    .typecho-list-table td:last-child {
        width: 210px;
    }

    @media (max-width: 900px) {
        .bf-moments-overview {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
</style>
<?php
include __TYPECHO_ROOT_DIR__ . '/admin/copyright.php';
include __TYPECHO_ROOT_DIR__ . '/admin/common-js.php';
include __TYPECHO_ROOT_DIR__ . '/admin/footer.php';
