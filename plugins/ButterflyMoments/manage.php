<?php

if (!defined('__TYPECHO_ROOT_DIR__') || !defined('__TYPECHO_ADMIN__')) {
    exit;
}

require_once __DIR__ . '/helpers.php';

$user->pass('editor');
butterflyMomentsEnsureSchema();

if ($request->isPost() && $request->get('do') === 'delete') {
    $security->protect();
    butterflyMomentsDelete((int) $request->get('id'));
    $response->redirect(butterflyMomentsPanelUrl('manage.php', ['notice' => 'deleted']));
}

$moments = butterflyMomentsFetchList(100);

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
                <?php endif; ?>

                <div class="typecho-list-operate">
                    <div class="operate">
                        <a class="btn primary btn-s" href="<?php echo butterflyMomentsPanelUrl('write.php'); ?>">发动态</a>
                    </div>
                </div>

                <table class="typecho-list-table">
                    <thead>
                    <tr>
                        <th><?php _e('内容'); ?></th>
                        <th><?php _e('作者'); ?></th>
                        <th><?php _e('状态'); ?></th>
                        <th><?php _e('互动'); ?></th>
                        <th><?php _e('时间'); ?></th>
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
                                    <div class="description">
                                        <a href="<?php echo butterflyMomentsPanelUrl('write.php', ['id' => (int) $moment['id']]); ?>"><?php _e('编辑'); ?></a>
                                        <span> / </span>
                                        <form method="post" action="<?php echo htmlspecialchars($security->getAdminUrl('extending.php?panel=ButterflyMoments/manage.php'), ENT_QUOTES, 'UTF-8'); ?>" style="display:inline;">
                                            <input type="hidden" name="do" value="delete">
                                            <input type="hidden" name="id" value="<?php echo (int) $moment['id']; ?>">
                                            <button type="submit" class="btn-link operate-delete" onclick="return confirm('确认删除这条动态吗？');"><?php _e('删除'); ?></button>
                                        </form>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($moment['screenName'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo $moment['status'] === 'draft' ? '草稿' : '已发布'; ?></td>
                                <td>
                                    <span>赞 <?php echo (int) $moment['like_count']; ?></span>
                                    <span> / 评 <?php echo (int) $moment['comment_count']; ?></span>
                                </td>
                                <td><?php echo date('Y-m-d H:i', (int) $moment['created']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="none"><?php _e('还没有任何朋友圈动态'); ?></td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
<?php
include __TYPECHO_ROOT_DIR__ . '/admin/copyright.php';
include __TYPECHO_ROOT_DIR__ . '/admin/common-js.php';
include __TYPECHO_ROOT_DIR__ . '/admin/footer.php';
