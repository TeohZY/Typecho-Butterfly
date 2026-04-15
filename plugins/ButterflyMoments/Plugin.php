<?php
/**
 * 朋友圈后台管理
 *
 * @package ButterflyMoments
 * @author TeohZY
 * @version 0.1.0
 * @link https://github.com/TeohZY/Typecho-Butterfly
 */
if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class ButterflyMoments_Plugin implements Typecho_Plugin_Interface
{
    private static function defaultConfig()
    {
        return [
            'coverImage' => '',
            'signature' => '独立于文章系统的短内容时间流，适合发日常、碎片想法、图片和轻互动。',
        ];
    }

    private static function ensureConfigExists()
    {
        try {
            Helper::options()->plugin('ButterflyMoments');
        } catch (Throwable $e) {
            Helper::configPlugin('ButterflyMoments', self::defaultConfig());
        }
    }

    public static function activate()
    {
        require_once __DIR__ . '/helpers.php';
        butterflyMomentsEnsureSchema();
        self::ensureConfigExists();

        Helper::addPanel(
            3,
            'ButterflyMoments/manage.php',
            '朋友圈',
            '管理朋友圈动态',
            'editor',
            false,
            'extending.php?panel=ButterflyMoments/write.php'
        );

        Helper::addPanel(
            3,
            'ButterflyMoments/write.php',
            '发动态',
            '发布朋友圈动态',
            'editor',
            true
        );

        return _t('朋友圈后台面板已启用');
    }

    public static function deactivate()
    {
        Helper::removePanel(3, 'ButterflyMoments/manage.php');
        Helper::removePanel(3, 'ButterflyMoments/write.php');

        return _t('朋友圈后台面板已禁用');
    }

    public static function config(Typecho_Widget_Helper_Form $form)
    {
        self::ensureConfigExists();

        $security = Typecho_Widget::widget('Widget_Security');
        $uploadUrl = htmlspecialchars($security->getIndex('/action/upload'), ENT_QUOTES, 'UTF-8');
        $mediaUrl = htmlspecialchars(Helper::options()->adminUrl('manage-medias.php', true), ENT_QUOTES, 'UTF-8');
        $coverHelper = <<<HTML
留空时会自动使用最新动态首图。<br>
<div class="bf-moments-config">
  <div class="bf-moments-config-actions">
    <button type="button" class="btn btn-s" id="bf-moments-cover-upload-btn">上传背景图</button>
    <button type="button" class="btn btn-s" id="bf-moments-cover-clear-btn">清空</button>
    <a class="btn btn-s" href="{$mediaUrl}" target="_blank" rel="noopener noreferrer">打开媒体库</a>
    <input type="file" id="bf-moments-cover-file" accept="image/*" hidden>
  </div>
  <div class="bf-moments-cover-preview" id="bf-moments-cover-preview">未设置背景图</div>
</div>
<style>
  .bf-moments-config { margin-top: 10px; }
  .bf-moments-config-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 10px; }
  .bf-moments-cover-preview {
    min-height: 140px;
    border: 1px solid #d9d9d6;
    border-radius: 4px;
    background: #f6f6f3 center / contain no-repeat;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #999;
    overflow: hidden;
  }
</style>
<script>
  (function () {
    function initButterflyMomentsConfig() {
      var input = document.querySelector('input[name="coverImage"]');
      var preview = document.getElementById('bf-moments-cover-preview');
      var uploadBtn = document.getElementById('bf-moments-cover-upload-btn');
      var clearBtn = document.getElementById('bf-moments-cover-clear-btn');
      var fileInput = document.getElementById('bf-moments-cover-file');
      if (!input || !preview || !uploadBtn || !clearBtn || !fileInput || uploadBtn.dataset.ready === '1') {
        return;
      }

      function renderPreview() {
        var value = (input.value || '').trim();
        if (value) {
          preview.style.backgroundImage = 'url("' + value.replace(/"/g, '&quot;') + '")';
          preview.textContent = '';
        } else {
          preview.style.backgroundImage = 'none';
          preview.textContent = '未设置背景图';
        }
      }

      function uploadFile(file) {
        var data = new FormData();
        data.append('file', file);

        fetch('{$uploadUrl}', {
          method: 'POST',
          body: data
        }).then(function (response) {
          if (!response.ok) {
            throw new Error(response.statusText);
          }
          return response.json();
        }).then(function (payload) {
          if (!payload || !payload[1] || !payload[1].url) {
            throw new Error('empty attachment');
          }

          input.value = payload[1].url;
          input.dispatchEvent(new Event('input', { bubbles: true }));
          input.dispatchEvent(new Event('change', { bubbles: true }));
          renderPreview();
        }).catch(function () {
          alert('背景图上传失败，请重试。');
        });
      }

      uploadBtn.dataset.ready = '1';
      renderPreview();
      input.addEventListener('input', renderPreview);
      uploadBtn.addEventListener('click', function () { fileInput.click(); });
      clearBtn.addEventListener('click', function () {
        input.value = '';
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
        renderPreview();
      });
      fileInput.addEventListener('change', function () {
        if (fileInput.files && fileInput.files[0]) {
          uploadFile(fileInput.files[0]);
        }
        fileInput.value = '';
      });
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initButterflyMomentsConfig);
    } else {
      initButterflyMomentsConfig();
    }
  })();
</script>
HTML;

        $cover = new Typecho_Widget_Helper_Form_Element_Text(
            'coverImage',
            null,
            self::defaultConfig()['coverImage'],
            _t('朋友圈背景图'),
            $coverHelper
        );
        $cover->input->setAttribute('class', 'w-100');
        $cover->input->setAttribute('placeholder', 'https://example.com/moments-cover.jpg');
        $form->addInput($cover);

        $signature = new Typecho_Widget_Helper_Form_Element_Textarea(
            'signature',
            null,
            self::defaultConfig()['signature'],
            _t('朋友圈签名'),
            _t('用于替换朋友圈页顶部说明文案。')
        );
        $signature->input->setAttribute('rows', '4');
        $signature->input->setAttribute('class', 'w-100');
        $form->addInput($signature);
    }

    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
    }
}
