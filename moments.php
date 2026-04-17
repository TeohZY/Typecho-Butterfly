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
$likeActionUrl = getMomentLikeActionUrl();
$commentActionUrl = getMomentCommentActionUrl();
$commentMap = fetchMomentCommentsMap(array_column($moments, 'id'));
$commentUserName = $this->user->hasLogin() ? trim((string) $this->user->screenName) : '';
$commentUserMail = $this->user->hasLogin() ? trim((string) $this->user->mail) : '';
$this->need('page_header.php');
?>
<style>
  #aside-content,
  #page-site-info {
    display: none !important;
  }

  #page-header {
    min-height: 0 !important;
    height: auto !important;
    background: transparent !important;
  }

  #page-header #nav {
    background: rgba(255, 255, 255, 0.92) !important;
    backdrop-filter: saturate(180%) blur(16px);
    -webkit-backdrop-filter: saturate(180%) blur(16px);
    box-shadow: 0 10px 30px rgba(46, 61, 66, 0.08);
  }

  #page-header #nav a,
  #page-header #nav #site-name,
  #page-header #nav #toggle-menu,
  #page-header #nav .site-page,
  #page-header #nav .menus_items .menus_item a,
  #page-header #nav #search-button,
  #page-header #nav #nav-right .nav-button {
    color: var(--font-color) !important;
    text-shadow: none !important;
  }

  #page-header #nav ::placeholder,
  #page-header #nav input {
    color: var(--font-color) !important;
    text-shadow: none !important;
  }

  [data-theme='dark'] #page-header #nav {
    background: rgba(22, 28, 32, 0.88) !important;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
  }

  [data-theme='dark'] #page-header #nav a,
  [data-theme='dark'] #page-header #nav #site-name,
  [data-theme='dark'] #page-header #nav #toggle-menu,
  [data-theme='dark'] #page-header #nav .site-page,
  [data-theme='dark'] #page-header #nav .menus_items .menus_item a,
  [data-theme='dark'] #page-header #nav #search-button,
  [data-theme='dark'] #page-header #nav #nav-right .nav-button,
  [data-theme='dark'] #page-header #nav ::placeholder,
  [data-theme='dark'] #page-header #nav input {
    color: #e8f3ee !important;
  }

  #content-inner.layout {
    max-width: min(1160px, calc(100% - 32px));
    width: min(1160px, calc(100% - 32px));
    margin: 0 auto;
    padding-top: 24px;
  }

  .page-moments {
    width: 100%;
    max-width: 1080px;
    margin: 0 auto;
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
                <span class="moments-toolbar-stat"><strong><?php echo $momentCount; ?></strong><small>条动态</small></span>
                <?php if ($this->user->hasLogin()): ?>
                    <a class="moments-publish-btn" href="<?php echo htmlspecialchars(hasMomentsAdminPanel() ? $this->options->adminUrl('extending.php?panel=ButterflyMoments/write.php', true) : $this->options->adminUrl('plugins.php', true), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo hasMomentsAdminPanel() ? '发布动态' : '启用插件'; ?></a>
                <?php else: ?>
                    <a class="moments-publish-btn secondary" href="<?php $this->options->adminUrl('login.php'); ?>" target="_blank" rel="noopener noreferrer">登录后发布</a>
                <?php endif; ?>
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
                    $images = getMomentImages($moment['images']);
                    $imageCount = count($images);
                    $dayNum = date('d', $moment['created']);
                    $monthLabel = date('m月', $moment['created']);
                    $year = date('Y', $moment['created']);
                    $liked = hasMomentLiked($moment['id'], $this->user->hasLogin() ? (int) $this->user->uid : 0, $this->request->getIp());
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
                                    <div class="moments-card-meta-strip">
                                        <div class="moments-card-meta-main">
                                            <button type="button" class="moments-like-btn<?php echo $liked ? ' is-liked' : ''; ?>" data-id="<?php echo (int) $moment['id']; ?>" data-url="<?php echo htmlspecialchars($likeActionUrl, ENT_QUOTES, 'UTF-8'); ?>">
                                                <i class="far fa-heart"></i>
                                                <span class="moments-like-label"><?php echo $liked ? ('已赞 · ' . (int) $moment['like_count']) : ((int) $moment['like_count'] . ' 赞'); ?></span>
                                            </button>
                                            <span class="moments-comment-count-text"><i class="far fa-comment-dots"></i><span class="moments-comment-count"><?php echo (int) $moment['comment_count']; ?> 条评论</span></span>
                                        </div>
                                        <button type="button" class="moments-comment-toggle" data-id="<?php echo (int) $moment['id']; ?>" aria-expanded="false">
                                            <i class="far fa-comment-dots"></i>
                                            <span class="moments-comment-toggle-label">评论</span>
                                        </button>
                                    </div>

                                    <div class="moments-comments" id="moment-comments-<?php echo (int) $moment['id']; ?>">
                                        <form class="moments-comment-form moments-comment-form-main" data-id="<?php echo (int) $moment['id']; ?>" data-url="<?php echo htmlspecialchars($commentActionUrl, ENT_QUOTES, 'UTF-8'); ?>" hidden>
                                            <input type="hidden" name="parent_id" value="0">
                                            <input type="hidden" name="reply_author_name" value="">
                                            <?php if (!$this->user->hasLogin()): ?>
                                                <div class="moments-comment-form-meta">
                                                    <input type="text" name="author_name" placeholder="昵称" maxlength="40" required>
                                                    <input type="email" name="author_mail" placeholder="邮箱（可选）" maxlength="120">
                                                </div>
                                            <?php endif; ?>
                                            <textarea name="content" rows="3" placeholder="<?php echo $this->user->hasLogin() ? '写下你的评论...' : '写下你的评论，昵称必填'; ?>" required></textarea>
                                            <div class="moments-comment-form-actions">
                                                <span class="moments-comment-form-tip"><?php echo $this->user->hasLogin() ? (htmlspecialchars($commentUserName, ENT_QUOTES, 'UTF-8') . '，评论会直接显示') : '独立评论，不会进入文章评论区；游客评论需审核后显示'; ?></span>
                                                <button type="submit">发布评论</button>
                                            </div>
                                        </form>

                                        <div class="moments-comments-list">
                                            <?php if (!empty($commentMap[(int) $moment['id']])): ?>
                                                <?php foreach ($commentMap[(int) $moment['id']] as $comment): ?>
                                                    <div class="moments-comment-item" id="moment-comment-<?php echo (int) $comment['id']; ?>">
                                                        <div class="moments-comment-head">
                                                            <div class="moments-comment-author-line">
                                                                <strong><?php echo htmlspecialchars($comment['author_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                                                <?php if (!empty($comment['reply_author_name'])): ?>
                                                                    <span class="moments-comment-reply-target">回复 @<?php echo htmlspecialchars($comment['reply_author_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="moments-comment-head-actions">
                                                                <span><?php echo date('m月d日 H:i', (int) $comment['created']); ?></span>
                                                                <button type="button" class="moments-comment-reply" data-moment-id="<?php echo (int) $moment['id']; ?>" data-comment-id="<?php echo (int) $comment['id']; ?>" data-author="<?php echo htmlspecialchars($comment['author_name'], ENT_QUOTES, 'UTF-8'); ?>">回复</button>
                                                            </div>
                                                        </div>
                                                        <div class="moments-comment-body"><?php echo nl2br(htmlspecialchars($comment['content'], ENT_QUOTES, 'UTF-8')); ?></div>
                                                        <form class="moments-comment-form moments-comment-reply-form" data-id="<?php echo (int) $moment['id']; ?>" data-url="<?php echo htmlspecialchars($commentActionUrl, ENT_QUOTES, 'UTF-8'); ?>" hidden>
                                                            <input type="hidden" name="parent_id" value="<?php echo (int) $comment['id']; ?>">
                                                            <input type="hidden" name="reply_author_name" value="<?php echo htmlspecialchars($comment['author_name'], ENT_QUOTES, 'UTF-8'); ?>">
                                                            <?php if (!$this->user->hasLogin()): ?>
                                                                <div class="moments-comment-form-meta">
                                                                    <input type="text" name="author_name" placeholder="昵称" maxlength="40" required>
                                                                    <input type="email" name="author_mail" placeholder="邮箱（可选）" maxlength="120">
                                                                </div>
                                                            <?php endif; ?>
                                                            <textarea name="content" rows="2" placeholder="回复 @<?php echo htmlspecialchars($comment['author_name'], ENT_QUOTES, 'UTF-8'); ?>" required></textarea>
                                                            <div class="moments-comment-form-actions">
                                                                <span class="moments-comment-form-tip">回复 @<?php echo htmlspecialchars($comment['author_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                                                <button type="submit">发送回复</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <div class="moments-comment-empty">还没有评论</div>
                                            <?php endif; ?>
                                        </div>
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
<script>
  (function () {
    function renderCommentItem(comment) {
      return '<div class="moments-comment-item">'
        + '<div class="moments-comment-head">'
        + '<div class="moments-comment-author-line"><strong>' + comment.author_name + '</strong>'
        + (comment.reply_author_name ? '<span class="moments-comment-reply-target">回复 @' + comment.reply_author_name + '</span>' : '')
        + '</div>'
        + '<div class="moments-comment-head-actions"><span>' + comment.created_label + '</span>'
        + '<button type="button" class="moments-comment-reply" data-moment-id="' + comment.moment_id + '" data-comment-id="' + comment.id + '" data-author="' + comment.author_name + '">回复</button></div>'
        + '</div>'
        + '<div class="moments-comment-body">' + comment.content + '</div>'
        + '<form class="moments-comment-form moments-comment-reply-form" data-id="' + comment.moment_id + '" data-url="<?php echo htmlspecialchars($commentActionUrl, ENT_QUOTES, 'UTF-8'); ?>" hidden>'
        + '<input type="hidden" name="parent_id" value="' + comment.id + '">'
        + '<input type="hidden" name="reply_author_name" value="' + comment.author_name + '">'
        + <?php echo json_encode(!$this->user->hasLogin() ? '<div class="moments-comment-form-meta"><input type="text" name="author_name" placeholder="昵称" maxlength="40" required><input type="email" name="author_mail" placeholder="邮箱（可选）" maxlength="120"></div>' : ''); ?>
        + '<textarea name="content" rows="2" placeholder="回复 @' + comment.author_name + '" required></textarea>'
        + '<div class="moments-comment-form-actions"><span class="moments-comment-form-tip">回复 @' + comment.author_name + '</span><button type="submit">发送回复</button></div>'
        + '</form>'
        + '</div>';
    }

    function setCommentCount(momentId, count) {
      document.querySelectorAll('.moments-card-footer').forEach(function (wrap) {
        var button = wrap.querySelector('.moments-comment-toggle');
        if (button && button.getAttribute('data-id') === String(momentId)) {
          wrap.querySelectorAll('.moments-comment-count').forEach(function (node) {
            node.textContent = count + ' 条评论';
          });
        }
      });
    }

    document.addEventListener('click', function (event) {
      var button = event.target.closest('.moments-like-btn');
      if (button && !button.disabled) {
        var momentId = button.getAttribute('data-id');
        var actionUrl = button.getAttribute('data-url');
        var labelNode = button.querySelector('.moments-like-label');

        button.disabled = true;

        fetch(actionUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: 'moment_id=' + encodeURIComponent(momentId)
        }).then(function (response) {
          if (!response.ok) {
            throw new Error(response.statusText);
          }
          return response.json();
        }).then(function (data) {
          if (!data || !data.success) {
            throw new Error('like failed');
          }

          button.classList.toggle('is-liked', !!data.liked);
          if (labelNode) {
            labelNode.textContent = data.liked ? ('已赞 · ' + data.like_count) : (data.like_count + ' 赞');
          }
        }).catch(function () {
          window.alert('点赞失败，请稍后重试。');
        }).finally(function () {
          button.disabled = false;
        });
      }

      var toggle = event.target.closest('.moments-comment-toggle');
      if (!toggle) {
        return;
      }

      var panel = document.getElementById('moment-comments-' + toggle.getAttribute('data-id'));
      if (!panel) {
        return;
      }

      var formWrap = panel.querySelector('.moments-comment-form-main');
      if (!formWrap) {
        return;
      }

      var hidden = formWrap.hasAttribute('hidden');
      if (hidden) {
        formWrap.removeAttribute('hidden');
        toggle.classList.add('is-open');
        toggle.setAttribute('aria-expanded', 'true');
        var label = toggle.querySelector('.moments-comment-toggle-label');
        if (label) label.textContent = '取消';
        var form = formWrap.querySelector('textarea');
        if (form) {
          form.focus();
        }
      } else {
        formWrap.setAttribute('hidden', 'hidden');
        toggle.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        var closeLabel = toggle.querySelector('.moments-comment-toggle-label');
        if (closeLabel) closeLabel.textContent = '评论';
      }
    });

    document.addEventListener('click', function (event) {
      var replyButton = event.target.closest('.moments-comment-reply');
      if (!replyButton) {
        return;
      }

      var item = replyButton.closest('.moments-comment-item');
      var form = item ? item.querySelector('.moments-comment-reply-form') : null;
      if (!form) {
        return;
      }

      var hidden = form.hasAttribute('hidden');
      item.closest('.moments-comments-list').querySelectorAll('.moments-comment-reply-form').forEach(function (node) {
        if (node !== form) {
          node.setAttribute('hidden', 'hidden');
        }
      });
      item.closest('.moments-comments-list').querySelectorAll('.moments-comment-reply').forEach(function (node) {
        if (node !== replyButton) {
          node.textContent = '回复';
        }
      });

      if (hidden) {
        form.removeAttribute('hidden');
        replyButton.textContent = '取消';
        var textarea = form.querySelector('textarea[name="content"]');
        if (textarea) {
          textarea.focus();
        }
      } else {
        form.setAttribute('hidden', 'hidden');
        replyButton.textContent = '回复';
      }
    });

    document.addEventListener('submit', function (event) {
      var form = event.target.closest('.moments-comment-form');
      if (!form) {
        return;
      }

      event.preventDefault();

      var momentId = form.getAttribute('data-id');
      var actionUrl = form.getAttribute('data-url');
      var textarea = form.querySelector('textarea[name="content"]');
      var submitButton = form.querySelector('button[type="submit"]');
      var commentsRoot = form.closest('.moments-comments');
      var list = commentsRoot.querySelector('.moments-comments-list');
      var nameInput = form.querySelector('input[name="author_name"]');
      var mailInput = form.querySelector('input[name="author_mail"]');
      var payload = [
        'moment_id=' + encodeURIComponent(momentId),
        'content=' + encodeURIComponent(textarea ? textarea.value : '')
      ];

      if (nameInput) {
        payload.push('author_name=' + encodeURIComponent(nameInput.value));
      }

      if (mailInput) {
        payload.push('author_mail=' + encodeURIComponent(mailInput.value));
      }

      var parentInput = form.querySelector('input[name="parent_id"]');
      var replyAuthorInput = form.querySelector('input[name="reply_author_name"]');
      if (parentInput) {
        payload.push('parent_id=' + encodeURIComponent(parentInput.value || '0'));
      }
      if (replyAuthorInput) {
        payload.push('reply_author_name=' + encodeURIComponent(replyAuthorInput.value || ''));
      }

      submitButton.disabled = true;

      fetch(actionUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: payload.join('&')
      }).then(function (response) {
        return response.json().then(function (data) {
          return { ok: response.ok, data: data };
        });
      }).then(function (result) {
        if (!result.ok || !result.data || !result.data.success) {
          throw new Error((result.data && result.data.message) || 'comment failed');
        }

        if (result.data.pending) {
          window.alert('评论已提交，等待审核后显示。');
        } else {
          var empty = list.querySelector('.moments-comment-empty');
          if (empty) {
            empty.remove();
          }

          list.insertAdjacentHTML('beforeend', renderCommentItem(result.data.comment));
          setCommentCount(momentId, result.data.comment_count);
        }

        if (textarea) {
          textarea.value = '';
        }
        if (form.classList.contains('moments-comment-form-main')) {
          form.setAttribute('hidden', 'hidden');
          var mainToggle = commentsRoot.closest('.moments-card-footer').querySelector('.moments-comment-toggle');
          if (mainToggle) {
            mainToggle.classList.remove('is-open');
            mainToggle.setAttribute('aria-expanded', 'false');
            var toggleLabel = mainToggle.querySelector('.moments-comment-toggle-label');
            if (toggleLabel) {
              toggleLabel.textContent = '评论';
            }
          }
        }
        if (form.classList.contains('moments-comment-reply-form')) {
          form.setAttribute('hidden', 'hidden');
          var replyButton = form.closest('.moments-comment-item').querySelector('.moments-comment-reply');
          if (replyButton) {
            replyButton.textContent = '回复';
          }
        }

        if (nameInput && !<?php echo $this->user->hasLogin() ? 'true' : 'false'; ?>) {
          nameInput.value = nameInput.value.trim();
        }
      }).catch(function (error) {
        window.alert(error.message || '评论失败，请稍后重试。');
      }).finally(function () {
        submitButton.disabled = false;
      });
    });
  })();
</script>
<?php $this->need('footer.php'); ?>
