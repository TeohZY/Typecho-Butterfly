<?php

if (!defined('__TYPECHO_ROOT_DIR__') || !defined('__TYPECHO_ADMIN__')) {
    exit;
}

require_once __DIR__ . '/helpers.php';

$user->pass('editor');
butterflyMomentsEnsureSchema();

$momentId = (int) $request->get('id');
$editingMoment = $momentId > 0 ? butterflyMomentsFind($momentId) : null;
$errorMessage = '';

if ($request->isPost()) {
    $security->protect();

    $content = trim((string) $request->get('content'));
    $images = butterflyMomentsNormalizeImages($request->get('images'));
    $status = $request->get('status') === 'draft' ? 'draft' : 'publish';

    if ($content === '') {
        $errorMessage = '动态内容不能为空。';
    } else {
        if ($editingMoment) {
            butterflyMomentsUpdate($momentId, $content, $images, $status);
        } else {
            $momentId = butterflyMomentsCreate((int) $user->uid, $content, $images, $status);
        }

        $response->redirect(butterflyMomentsPanelUrl('manage.php', ['notice' => 'saved']));
    }
}

$contentValue = $editingMoment ? (string) $editingMoment['content'] : (string) $request->get('content');
$imageItems = $editingMoment ? butterflyMomentsNormalizeImages($editingMoment['images']) : butterflyMomentsNormalizeImages($request->get('images'));
$statusValue = $editingMoment ? (string) $editingMoment['status'] : ((string) $request->get('status') ?: 'publish');

$post = new class($contentValue) {
    public $text;
    public $isMarkdown = true;

    public function __construct($text)
    {
        $this->text = $text;
    }

    public function have()
    {
        return false;
    }
};

include __TYPECHO_ROOT_DIR__ . '/admin/header.php';
include __TYPECHO_ROOT_DIR__ . '/admin/menu.php';
?>
<main class="main">
    <div class="body container">
        <?php include __TYPECHO_ROOT_DIR__ . '/admin/page-title.php'; ?>
        <div class="row typecho-page-main" role="main">
            <div class="col-mb-12 col-tb-10 col-tb-offset-1">
                <?php if ($errorMessage !== ''): ?>
                    <div class="message error"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <form method="post" action="<?php echo htmlspecialchars($security->getAdminUrl('extending.php?panel=ButterflyMoments/write.php' . ($momentId > 0 ? '&id=' . $momentId : '')), ENT_QUOTES, 'UTF-8'); ?>" class="typecho-post-area" name="write_post">
                    <p class="moments-form-block">
                        <label class="typecho-label" for="text"><?php _e('动态内容'); ?></label>
                        <textarea id="text" name="content" class="w-100 mono" rows="14" style="height: 420px" placeholder="写点今天发生的事..."><?php echo htmlspecialchars($contentValue, ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </p>

                    <section class="moments-form-block moments-images-panel">
                        <div class="moments-images-head">
                            <div>
                                <label class="typecho-label" for="moments-upload"><?php _e('动态图片'); ?></label>
                                <p class="description"><?php _e('上传后可直接预览、删除和调整顺序。'); ?></p>
                            </div>
                            <div class="moments-images-actions">
                                <button type="button" class="btn btn-s" id="moments-upload-trigger"><?php _e('添加图片'); ?></button>
                                <input type="file" id="moments-upload" accept="image/*" multiple hidden>
                            </div>
                        </div>

                        <input type="hidden" name="images" id="images-data" value="<?php echo htmlspecialchars(json_encode($imageItems, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>">

                        <ul id="moments-image-list" class="moments-image-list"></ul>
                        <p id="moments-image-empty" class="description"<?php echo !empty($imageItems) ? ' style="display:none;"' : ''; ?>><?php _e('还没有添加图片'); ?></p>
                    </section>

                    <p class="moments-form-block">
                        <label class="typecho-label" for="status"><?php _e('状态'); ?></label>
                        <select id="status" name="status">
                            <option value="publish"<?php echo $statusValue === 'publish' ? ' selected' : ''; ?>><?php _e('发布'); ?></option>
                            <option value="draft"<?php echo $statusValue === 'draft' ? ' selected' : ''; ?>><?php _e('草稿'); ?></option>
                        </select>
                    </p>

                    <section class="submit clearfix">
                        <div class="right">
                            <a class="btn" href="<?php echo butterflyMomentsPanelUrl('manage.php'); ?>"><?php _e('返回列表'); ?></a>
                            <button type="submit" class="btn primary"><?php echo $editingMoment ? '保存动态' : '发布动态'; ?></button>
                        </div>
                    </section>
                </form>
            </div>
        </div>
    </div>
</main>

<?php
include __TYPECHO_ROOT_DIR__ . '/admin/common-js.php';
?>
<style>
    .moments-form-block {
        margin-bottom: 1.4em;
    }

    .moments-form-block .typecho-label {
        display: block;
        margin-bottom: 0.6em;
    }

    .moments-images-panel {
        padding: 16px;
        background: #fff;
        border: 1px solid #f0f0ec;
        border-radius: 2px;
    }

    .moments-images-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 12px;
    }

    .moments-image-list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 12px;
    }

    .moments-image-item {
        background: #fff;
        border: 1px solid #e9e9e6;
        border-radius: 2px;
        overflow: hidden;
    }

    .moments-image-preview {
        aspect-ratio: 1 / 1;
        background: #f6f6f3;
        overflow: hidden;
    }

    .moments-image-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .moments-image-meta {
        padding: 8px;
    }

    .moments-image-title {
        display: block;
        margin-bottom: 8px;
        color: #666;
        font-size: 12px;
        word-break: break-all;
    }

    .moments-image-tools {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    .moments-image-tools button {
        padding: 0 8px;
    }

    #wmd-button-bar {
        margin-bottom: 0;
    }

    #wmd-preview {
        padding: 12px;
        border: 1px solid #d9d9d6;
        border-top: 0;
        background: #fff;
    }
</style>
<script>
    Typecho.savePost = function (cb) {
        document.forms.write_post.submit();
        if (cb) {
            cb();
        }
    };
</script>
<?php
include __TYPECHO_ROOT_DIR__ . '/admin/editor-js.php';
?>
<script>
    (function () {
        const list = document.getElementById('moments-image-list');
        const input = document.getElementById('images-data');
        const empty = document.getElementById('moments-image-empty');
        const uploadInput = document.getElementById('moments-upload');
        const uploadTrigger = document.getElementById('moments-upload-trigger');
        const uploadUrl = <?php echo json_encode($security->getIndex('/action/upload')); ?>;
        let images = [];

        function parseImages() {
            try {
                const value = JSON.parse(input.value || '[]');
                images = Array.isArray(value) ? value : [];
            } catch (e) {
                images = [];
            }
        }

        function sync() {
            input.value = JSON.stringify(images);
            empty.style.display = images.length > 0 ? 'none' : '';
        }

        function render() {
            list.innerHTML = '';

            images.forEach(function (item, index) {
                const li = document.createElement('li');
                li.className = 'moments-image-item';
                li.innerHTML = ''
                    + '<div class="moments-image-preview"><img src="' + item.url + '" alt=""></div>'
                    + '<div class="moments-image-meta">'
                    + '<span class="moments-image-title">' + (item.title || ('image-' + (index + 1))) + '</span>'
                    + '<div class="moments-image-tools">'
                    + '<button type="button" class="btn btn-xs" data-action="up" data-index="' + index + '">上移</button>'
                    + '<button type="button" class="btn btn-xs" data-action="down" data-index="' + index + '">下移</button>'
                    + '<button type="button" class="btn btn-xs btn-warn" data-action="remove" data-index="' + index + '">删除</button>'
                    + '</div></div>';
                list.appendChild(li);
            });

            sync();
        }

        function uploadFile(file) {
            const data = new FormData();
            data.append('file', file);

            fetch(uploadUrl, {
                method: 'POST',
                body: data
            }).then(function (response) {
                if (!response.ok) {
                    throw new Error(response.statusText);
                }
                return response.json();
            }).then(function (payload) {
                const attachment = payload[1];
                images.push({
                    cid: attachment.cid || 0,
                    url: attachment.url,
                    title: attachment.title || file.name
                });
                render();
            }).catch(function () {
                alert('图片上传失败，请重试。');
            });
        }

        uploadTrigger.addEventListener('click', function () {
            uploadInput.click();
        });

        uploadInput.addEventListener('change', function () {
            Array.prototype.forEach.call(uploadInput.files, function (file) {
                uploadFile(file);
            });
            uploadInput.value = '';
        });

        list.addEventListener('click', function (event) {
            const button = event.target.closest('button[data-action]');
            if (!button) {
                return;
            }

            const index = parseInt(button.getAttribute('data-index'), 10);
            const action = button.getAttribute('data-action');

            if (action === 'remove') {
                images.splice(index, 1);
            } else if (action === 'up' && index > 0) {
                const current = images[index];
                images[index] = images[index - 1];
                images[index - 1] = current;
            } else if (action === 'down' && index < images.length - 1) {
                const current = images[index];
                images[index] = images[index + 1];
                images[index + 1] = current;
            }

            render();
        });

        parseImages();
        render();
    })();
</script>
<?php
include __TYPECHO_ROOT_DIR__ . '/admin/footer.php';
