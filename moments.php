<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
/**
 * 朋友圈
 *
 * @package custom
 */
$momentCategory = getMomentCategory();
$moments = fetchMomentsList();
$momentCount = count($moments);
$todayStart = strtotime(date('Y-m-d 00:00:00'));
$yesterdayStart = $todayStart - 86400;
$heroImage = !empty($moments) ? getMomentImages($moments[0]['text'], 1) : [];
$heroCover = !empty($heroImage) ? $heroImage[0] : $this->options->logoUrl;
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
                    <p>
                        <?php if ($momentCategory): ?>
                            读取分类“<?php echo htmlspecialchars($momentCategory['name'], ENT_QUOTES, 'UTF-8'); ?>”中的最新动态，像看朋友圈一样看博客更新。
                        <?php else: ?>
                            还没有找到朋友圈分类，先在后台创建并配置分类 slug。
                        <?php endif; ?>
                    </p>
                </div>
                <div class="moments-profile-card">
                    <strong><?php $this->options->title(); ?></strong>
                    <span><?php echo $momentCount; ?> 条动态</span>
                </div>
                <div class="moments-profile-avatar">
                    <img data-lazy-src="<?php $this->options->logoUrl() ?>" src="<?php echo GetLazyLoad(); ?>" alt="avatar">
                </div>
            </div>
        </header>

        <section class="moments-toolbar">
            <div class="moments-toolbar-copy">
                <strong>动态发布方式</strong>
                <p>
                    发布普通文章并归类到
                    <code><?php echo htmlspecialchars(getMomentCategorySlug(), ENT_QUOTES, 'UTF-8'); ?></code>
                    分类，这个页面会自动按时间流展示。
                </p>
            </div>
            <div class="moments-toolbar-actions">
                <span class="moments-chip"><i class="far fa-clock"></i>时间轴</span>
                <span class="moments-chip"><i class="far fa-images"></i>图片宫格</span>
                <?php if ($this->user->hasLogin()): ?>
                    <a class="moments-publish-btn" href="<?php $this->options->adminUrl(); ?>write-post.php" target="_blank" rel="noopener noreferrer">发布动态</a>
                <?php else: ?>
                    <a class="moments-publish-btn secondary" href="<?php $this->options->adminUrl('login.php'); ?>" target="_blank" rel="noopener noreferrer">登录后发布</a>
                <?php endif; ?>
            </div>
        </section>

        <section class="moments-status-bar">
            <div class="moments-status-item">
                <span>动态数</span>
                <strong><?php echo $momentCount; ?></strong>
            </div>
            <div class="moments-status-item">
                <span>当前分类</span>
                <strong><?php echo $momentCategory ? htmlspecialchars($momentCategory['name'], ENT_QUOTES, 'UTF-8') : '未配置'; ?></strong>
            </div>
            <div class="moments-status-item">
                <span>展示条数</span>
                <strong><?php echo (int) getMomentPageSize(); ?></strong>
            </div>
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
                    $images = getMomentImages($moment['text']);
                    $imageCount = count($images);
                    $monthDay = date('m/d', $moment['created']);
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
                            <strong><?php echo $monthDay; ?></strong>
                            <span><?php echo $year; ?></span>
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

                                <?php if (!empty($moment['title'])): ?>
                                    <a class="moments-card-title" href="<?php echo htmlspecialchars(getMomentPermalink($moment), ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($moment['title'], ENT_QUOTES, 'UTF-8'); ?>
                                    </a>
                                <?php endif; ?>

                                <div class="moments-card-text">
                                    <?php echo getMomentPreviewText($moment['text'], 260); ?>
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
                                        <a href="<?php echo htmlspecialchars(getMomentPermalink($moment), ENT_QUOTES, 'UTF-8'); ?>"><i class="far fa-comment-dots"></i>评论</a>
                                    </div>
                                    <div class="moments-card-meta-strip">
                                        <span><i class="far fa-comment-dots"></i><?php echo (int) $moment['commentsNum']; ?></span>
                                        <span><i class="far fa-eye"></i><?php echo (int) $moment['views']; ?></span>
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
                        先创建 slug 为 <code><?php echo htmlspecialchars(getMomentCategorySlug(), ENT_QUOTES, 'UTF-8'); ?></code> 的分类，
                        再把短内容发布到这个分类中，这里就会生成真正的朋友圈时间流。
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </section>
    <details class="moments-comments-wrap">
        <summary>
            <span>页面留言</span>
            <small>展开后可对这个朋友圈页面留言</small>
        </summary>
        <div class="moments-comments-inner">
            <?php $this->need('comments.php'); ?>
        </div>
    </details>
</div>
</main>
<?php $this->need('footer.php'); ?>
