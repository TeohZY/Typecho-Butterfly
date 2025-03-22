<?php
// 自定义编辑器
Typecho_Plugin::factory('admin/write-post.php')->bottom = array('VditorEditor', 'render');
Typecho_Plugin::factory('admin/write-page.php')->bottom = array('VditorEditor', 'render');
// 修改编辑器
Typecho_Plugin::factory('admin/write-post.php')->richEditor = array('VditorEditor', 'render');
Typecho_Plugin::factory('admin/write-page.php')->richEditor = array('VditorEditor', 'render');

class VditorEditor
{

    public static function render()
    {
        // 获取当前文章内容
        $content = Typecho_Widget::widget('Widget_Abstract_Contents')->content;

        // 引入 Vditor 的 CSS 和 JS 文件
        echo "<link rel='stylesheet' href='" . Helper::options()->themeUrl . '/lib/Vditor/css/index.css' . "' />";
        echo "<script src='" . Helper::options()->themeUrl . '/lib/Vditor/js/index.main.js' . "'></script>";

        // 渲染 Vditor 编辑器
       
        echo '<div id="vditor"></div>';
        echo '<script>
            const vditor = new Vditor("vditor", {
                height: 600,
                value: ' . json_encode($content) . ',
                counter:true,
                after: function() {
                    // 在编辑器内容变化时更新隐藏的 textarea
                    vditor.setValue(document.getElementById("text").value);
                },
                input: function(value) {
                    // 将编辑器内容同步到 Typecho 的 textarea
                    document.getElementById("text").value = value;
                },
                cache: {
                    enable: false, // 禁用本地缓存
                },
            });
        </script>';
echo '<style>#wmd-button-bar, #text { display: none; }</style>';
echo '<script>
    // 获取所有 id 为 "text" 的元素
    let textElement = document.getElementById("text");
    
    // 创建一个新元素，并设置 d 属性
    let newElement = document.getElementById("vditor");
    
    // 将新元素替换原来的 text 元素
    textElement.parentNode.appendChild(newElement, textElement);
</script>';
    }
}


?>