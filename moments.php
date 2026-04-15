<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
/**
 * 朋友圈
 *
 * @package custom
 */
$moments = fetchMomentsList();
$momentCount = count($moments);
$todayStart = strtotime(date('Y-m-d 00:00:00'));
$yesterdayStart = $todayStart - 86400;
$momentsPlugin = null;
if (class_exists('ButterflyMoments_Plugin')) {
    try {
        $momentsPlugin = Helper::options()->plugin('ButterflyMoments');
    } catch (Throwable $e) {
        $momentsPlugin = null;
    }
}
$heroImage = !empty($moments) ? getMomentImages($moments[0]['images'], 1) : [];
$configuredCover = $momentsPlugin ? trim((string) $momentsPlugin->coverImage) : '';
$configuredSignature = $momentsPlugin ? trim((string) $momentsPlugin->signature) : '';
$heroCover = $configuredCover !== '' ? $configuredCover : (!empty($heroImage) ? $heroImage[0] : $this->options->logoUrl);
$heroSignature = $configuredSignature !== '' ? $configuredSignature : '独立于文章系统的短内容时间流，适合发日常、碎片想法、图片和轻互动。';
$this->need('page_header.php');
?>
<style>
  #page-header,
  #aside-content {
    display: none !important;
  }

  #content-inner.layout {
    max-width: min(980px, calc(100% - 24px));
    padding-top: 24px;
  }

  .page-moments {
    width: 100%;
  }

  .page-moments #comments {
    margin-top: 1.4rem;
  }
</style>
<main class="layout" id="content-inner">
<div id="page" class="page-moments">
    <section class="moments-shell">
        <header class="moments-hero">
            <div class="moments-cover" style="background-image: url('<?php echo htmlspecialchars($heroCover, ENT_QUOTES, 'UTF-8'); ?>');"></div>
            <div class="moments-profile">
                <div class="moments-profile-text">
                    <p class="moments-profile-label">Moments</p>
                    <h2>朋友圈</h2>
                    <p><?php echo htmlspecialchars($heroSignature, ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
            </div>
        </header>

        <section class="moments-toolbar">
            <div class="moments-toolbar-actions">
                <span class="moments-toolbar-stat"><strong><?php echo $momentCount; ?></strong><small>动态</small></span>
                <span class="moments-toolbar-stat"><strong><?php echo (int) getMomentPageSize(); ?></strong><small>展示</small></span>
                <span class="moments-chip"><i class="far fa-clock"></i>时间轴</span>
                <span class="moments-chip"><i class="far fa-images"></i>图片宫格</span>
                <?php if ($this->user->hasLogin()): ?>
                    <a class="moments-publish-btn" href="<?php echo htmlspecialchars(hasMomentsAdminPanel() ? $this->options->adminUrl('extending.php?panel=ButterflyMoments/write.php', true) : $this->options->adminUrl('plugins.php', true), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo hasMomentsAdminPanel() ? '发布动态' : '启用插件'; ?></a>
                <?php else: ?>
                    <a class="moments-publish-btn secondary" href="<?php $this->options->adminUrl('login.php'); ?>" target="_blank" rel="noopener noreferrer">登录后发布</a>
                <?php endif; ?>
            </div>
        </section>

        <section class="moments-status-bar">
            <span class="moments-status-item"><i class="far fa-calendar"></i><?php echo date('Y年m月d日'); ?></span>
            <span class="moments-status-item"><i class="far fa-layer-group"></i>独立 Moments</span>
            <span class="moments-status-item"><i class="far fa-stream"></i>按时间倒序</span>
        </section>

        <?php if (trim((string) $this->content) !== ''): ?>
            <article class="post-content moments-page-intro" id="article-container">
                <?php echo $this->content; ?>
            </article>
        <?php endif; ?>

        <div class="moments-timeline">
            <?php if (!empty($moments)): ?>
                <?php $currentGroupLabel = ''; ?>
                <?php foreach ($moments as $moment): ?>
                    <?php
                    $images = getMomentImages($moment['images']);
                    $imageCount = count($images);
                    $dayNum = date('d', $moment['created']);
                    $monthLabel = date('m月', $moment['created']);
                    $year = date('Y', $moment['created']);
                    if ($moment['created'] >= $todayStart) {
                        $groupLabel = '今天';
                    } elseif ($moment['created'] >= $yesterdayStart) {
                        $groupLabel = '昨天';
                    } elseif ($moment['created'] >= $todayStart - 7 * 86400) {
                        $groupLabel = '近一周';
                    } else {
                        $groupLabel = date('Y年m月', $moment['created']);
                    }
                    ?>
                    <?php if ($groupLabel !== $currentGroupLabel): ?>
                        <div class="moments-group-label">
                            <span><?php echo $groupLabel; ?></span>
                        </div>
                        <?php $currentGroupLabel = $groupLabel; ?>
                    <?php endif; ?>
                    <article class="moments-entry<?php echo $imageCount === 0 ? ' moments-entry-text-only' : ''; ?>">
                        <aside class="moments-date">
                            <strong><?php echo $dayNum; ?></strong>
                            <span><?php echo $monthLabel . ' · ' . $year; ?></span>
                        </aside>

                        <div class="moments-entry-main">
                            <div class="moments-entry-dot"></div>
                            <div class="moments-card<?php echo $imageCount === 0 ? ' moments-card-text-only' : ''; ?>">
                                <div class="moments-card-head">
                                    <div class="moments-card-avatar">
                                        <?php echo getGravatar($moment['mail'], $moment['screenName'], 'alt="' . htmlspecialchars($moment['screenName'], ENT_QUOTES, 'UTF-8') . '"'); ?>
                                    </div>
                                    <div class="moments-card-meta">
                                        <div class="moments-card-author-row">
                                            <a class="moments-card-author" href="<?php echo htmlspecialchars(getMomentPermalink($moment), ENT_QUOTES, 'UTF-8'); ?>">
                                                <?php echo htmlspecialchars($moment['screenName'], ENT_QUOTES, 'UTF-8'); ?>
                                            </a>
                                            <span class="moments-card-badge"><?php echo $imageCount > 0 ? '图文动态' : '文字动态'; ?></span>
                                        </div>
                                        <div class="moments-card-time">
                                            <span><?php echo formatMomentTime($moment['created']); ?></span>
                                            <span><?php echo date('m月d日 H:i', $moment['created']); ?></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="moments-card-text">
                                    <?php echo getMomentPreviewText($moment['content'], 260); ?>
                                </div>

                                <?php if (!empty($images)): ?>
                                    <div class="moments-gallery moments-gallery-count-<?php echo min($imageCount, 9); ?>">
                                        <?php foreach ($images as $image): ?>
                                            <a href="<?php echo htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
                                                <img data-lazy-src="<?php echo htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>" src="<?php echo GetLazyLoad(); ?>" alt="moment image">
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="moments-card-footer">
                                    <div class="moments-card-actions">
                                        <span><i class="far fa-heart"></i>赞</span>
                                        <a href="<?php echo htmlspecialchars(getMomentPermalink($moment), ENT_QUOTES, 'UTF-8'); ?>"><i class="far fa-comment-dots"></i>查看</a>
                                    </div>
                                    <div class="moments-card-meta-strip">
                                        <span><i class="far fa-heart"></i><?php echo (int) $moment['like_count']; ?></span>
                                        <span><i class="far fa-comment-dots"></i><?php echo (int) $moment['comment_count']; ?></span>
                                        <a href="<?php echo htmlspecialchars(getMomentPermalink($moment), ENT_QUOTES, 'UTF-8'); ?>">查看详情</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="moments-empty">
                    <h3>还没有动态</h3>
                    <p>
                        先创建一个使用“朋友圈发布”模板的独立页面，
                        再从发布页发第一条动态，这里就会生成真正的朋友圈时间流。
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>
</main>
<?php $this->need('footer.php'); ?>
