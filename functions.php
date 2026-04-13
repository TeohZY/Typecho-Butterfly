<?php
use Typecho\Plugin;
if (!defined('__TYPECHO_ROOT_DIR__'))
    exit;

require_once('libs/api.php');
require_once('libs/search.php');
require_once('libs/custom_config.php');
require_once('libs/core.php');
require_once('libs/content_helpers.php');
require_once('libs/comment_helpers.php');
require_once('libs/site_helpers.php');
require_once('libs/Vditor/index.php');

/**
 * XSS 过滤函数
 * 移除危险的 HTML 标签和属性，防止 XSS 攻击
 */
function xss_clean($html) {
    // 允许的 HTML 标签白名单
    $allowed_tags = '<h1><h2><h3><h4><h5><h6><p><br><hr><ul><ol><li><blockquote><pre><code><span><div><img><a><strong><em><del><sup><sub>';

    // 先转义所有 HTML
    $html = htmlspecialchars($html, ENT_QUOTES, 'UTF-8');

    // 还原代码块中的内容（因为代码块内容需要保持原样）
    // 匹配 ```code``` 格式
    $html = preg_replace_callback('/`{3}(\w*)(.*?)`{3}/s', function($matches) {
        $lang = htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8');
        $code = $matches[2];
        return '<pre><code class="lang-' . $lang . '">' . $code . '</code></pre>';
    }, $html);

    // 匹配 `code` 格式
    $html = preg_replace_callback('/`([^`]+)`/', function($matches) {
        return '<code>' . $matches[1] . '</code>';
    }, $html);

    return $html;
}

/* 格式化标签 */
function ParseCode($text)
{
    // 首先保存 <pre>...</pre> 区块内容，避免被自定义标签解析干扰。
    preg_match_all('!(<pre[^>]*>.*?</pre>)!is', $text, $pre_blocks);
    $placeholders = [];

    foreach ($pre_blocks[0] as $index => $pre_block) {
        $placeholder = "<!--placeholder{$index}-->";
        $placeholders[] = $placeholder;
        $text = str_replace($pre_block, $placeholder, $text);
    }
    $original_content = $text;

    $text = Short_Tabs($text);
    $text = Note_Fsm($text);
    $text = Note_Ico($text);
    $text = Hide_Lnline($text);
    $text = Hide_Block($text);
    $text = Hide_Toggle($text);
    $text = Button($text);
    $text = Cheak_Box($text);
    $text = inline_Tag($text);
    $text = Bf_Radio($text);
    $text = Bf_Mark($text);
    $text = Font($text);
    $text = timeLine($text);
    $text = ArtPlayer($text);
    $text = PostImage($text);
    $text = customLink($text);
    $text = add_hybrid_lazyload($text);

    // 在所有自定义解析完成后，还原保存的 <pre></pre> 区块内容。
    $text = str_replace($placeholders, $pre_blocks[0], $text);
    $text = codeHightLight($text);
    return $text;
}
function add_hybrid_lazyload($content) {
    $content = preg_replace_callback(
        '/<img(.*?)src=["\'](.*?)["\'](.*?)>/i',
        function($matches) {
            $lazySrc = GetLazyLoad();
            return '<img' . $matches[1] . 'data-lazy-src="' . $matches[2] . '" src="' . $lazySrc . '" class="lazyload" loading="lazy"' . $matches[3] . '>';
        },
        $content
    );
    return $content;
}
// 自定义链接样式
function customLink($text){
    $text = preg_replace_callback('/\{%\slink\s(.*?),(.*?),(.*?)\s%\}/s', function ($matches) {
        // 提取$matches[3]中的链接
        if (preg_match('/href="([^"]+)"/', $matches[3], $linkMatches)) {
            $url = $linkMatches[1];
        } else {
            // 如果没有找到链接，可以设置一个默认值或进行错误处理
            $url = $matches[3]; // 请根据需要替换为合适的URL
        }

        // 检测提取出的链接是否包含http://或https://
        if (!preg_match('~https?://~', $url)) {
            $url = 'https://' . $url;
        }

        // 从URL中解析出主机名用于构造favicon图标的URL
        $host = parse_url($url, PHP_URL_HOST);
        if ($host) {
            $imgUrl = "https://api.teohzy.com/favicon/" . $host . ".png";
        } else {
            // 处理无法解析主机名的情况
            $imgUrl = "placeholder_image_url"; // 替换为合适的占位图标URL
        }

        // 构建HTML结构，并返回
        return "<div><a class=\"tag-Link\" target=\"_blank\" href=\"{$url}\">
            <div class=\"tag-link-tips\">引用站外地址</div>
            <div class=\"tag-link-bottom\">
                <div class=\"tag-link-left\" style=\"background-image: url({$imgUrl});\"></div>
                <div class=\"tag-link-right\">
                    <div class=\"tag-link-title\">{$matches[1]}</div>
                    <div class=\"tag-link-sitename\">{$matches[2]}</div>
                </div>
                <i class=\"fa-solid fa-angle-right\"></i>
            </div>
        </a></div>";
    }, $text);
    return $text;
}
// 标签外挂-Tabs
function Short_Tabs($text)
{
    // tables 
    $text = preg_replace_callback(
        '/{%\s*tabs\s*(.*?),?\s*([\d-]*)?\s*%}([\s\S]*?){%\s*endtabs\s*%}/',
        function ($matches) {
            $id = $matches[1];
            $defaultActiveTab = !empty($matches[2]) ? (int)$matches[2] : 0;
            $tabsBlock = $matches[3];

            preg_match_all(
                '/<!--\s*tab\s*(.*?)\s*-->([\s\S]*?)<!--\s*endtab\s*-->/',
                $tabsBlock,
                $tabs_matches
            );
            $tabTitles = $tabs_matches[1];
            $tabContents = $tabs_matches[2];

            $html = '<div class="tabs" id="' . $id . '"><ul class="nav-tabs">';

            foreach ($tabTitles as $i => $title) {
                $index = $i + 1;
                $active = $i === ($defaultActiveTab - 1) ? ' active' : '';
                if (strpos($title, '@') !== false) {
                    $titleParts = explode('@', $title, 2);
                    $iconClass = isset($titleParts[1]) ? '<i class="' . trim($titleParts[1]) . '" style="text-align:center"></i>' : '';
                    $title = isset($titleParts[0]) && !empty(trim($titleParts[0])) ? $iconClass . ' ' . trim($titleParts[0]) : $iconClass;
                } else {
                    $title = !empty($title) ? $title : $id . ' ' . $index;
                }
                $html .= '<button type="button" class="tab' . $active . '" data-href="' . $id . '-' . $index . '">' . $title . '</button>';
            }

            $html .= '</ul><div class="tab-contents">';

            foreach ($tabContents as $i => $content) {
                $index = $i + 1;
                $active = $i === ($defaultActiveTab - 1) ? ' active' : '';
                $html .= '<div class="tab-item-content' . $active . '" id="' . $id . '-' . $index . '"><p>' . $content . '</p></div>';
            }

            $html .= '</div><div class="tab-to-top"><button type="button" aria-label="scroll to top"><i class="fas fa-arrow-up"></i></button></div></div>';
            return $html;
        },
        $text
    );
    return $text;
}
// 标签外挂-btn
function Button($text)
{
    $text = preg_replace_callback('/\[btn href=\"(.*?)\" type=\"(.*?)\".*?\ ico=\"(.*?)\"](.*?)\[\/btn\]/ism', function ($text) {
        return '<a href="' . $text[1] . '" class="btn-beautify button--animated ' . $text[2] . '">
        <i class=" ' . $text[3] . '"></i><span>' . $text[4] . '</span></a>';
    }, $text);
    return $text;
}

// 标签外挂-note
function Note_Fsm($text)
{
    $notePattern = '/{%\s*note\s+([\w\s]+?)\s*%}(.*?)\{%\s*endnote\s*%}/su';

    $text = preg_replace_callback('/{%\s*note\s+([\w\s]+?)\s*%}(.*?)\{%\s*endnote\s*%}/su', function ($matches) {
        // 在此处，$matches[1] 是属性，$matches[2] 是内容
        $classString = htmlspecialchars(trim('note') . ' ' . trim($matches[1]));
        $textContent = trim($matches[2]);
        $textContent = preg_replace('/<br\s*\/?>/', '', $textContent);
        return "<div class=\"{$classString}\"><p>{$textContent}</p></div>";
    }, $text);
    return $text;
}
// 标签外挂-note_ico
function Note_Ico($text)
{
    $text = preg_replace_callback('/\[note-ico type=\"(.*?)\".*?\ ico=\"(.*?)\"](.*?)\[\/note-ico\]/ism', function ($text) {
        return '<div class="note ' . $text[1] . '"><i class="' . $text[2] . '"></i><p>' . $text[3] . '</p></div>';
    }, $text);
    return $text;
}
// hide-inline
function Hide_Lnline($text)
{
    $text = preg_replace_callback('/\[hide-inline name=\"(.*?)\".*?\](.*?)\[\/hide-inline\]/ism', function ($text) {
        return '<span class="hide-inline"><button type="button" class="hide-button button--animated">' . $text[1] . '</button><span class="hide-content">' . $text[2] . '</span></span>';
    }, $text);
    return $text;
}
// hide-block
function Hide_Block($text)
{
    $text = preg_replace_callback('/\[hide-block name=\"(.*?)\".*?\](.*?)\[\/hide-block\]/ism', function ($text) {
        return '<div class="hide-block"><button type="button" class="hide-button button--animated">' . $text[1] . '</button><div class="hide-content">' . $text[2] . '</div></div>';
    }, $text);
    return $text;
}

// hide-toggle
function Hide_Toggle($text)
{
    $text = preg_replace_callback('/\{%\s*hideToggle\s+(.*?)\s*%\}(.*?)\{%\s*endhideToggle\s*%\}/su', function ($matches) {
        $title = $matches[1];
        $text = $matches[2];
        // Convert each line of text into a paragraph
        $contentLines = explode("\n", $text);
        $contentHtml = "";

        foreach ($contentLines as $line) {
            $line = trim($line);
            if ($line !== '') {
                $contentHtml .= "<p>$line</p>";
            }
        }

        return "<details class=\"toggle\"><summary class=\"toggle-button\">$title</summary><div class=\"toggle-content\">$contentHtml</div></details>";
    }, $text);
    return $text;
}
// 复选框
function Cheak_Box($text)
{
    $text = preg_replace_callback('/\[cb type=\"(.*?)\".*?\ checked=\"(.*?)"\](.*?)\[\/cb\]/ism', function ($text) {
        return '<div class="checkbox ' . $text[1] . ' checked"><input type="checkbox" ' . $text[2] . '>' . $text[3] . '</div>';
    }, $text);
    return $text;
}
// 行内标签
function inline_Tag($text)
{
    $text = preg_replace_callback('/\[in-tag color=\"(.*?)\"](.*?)\[\/in-tag\]/ism', function ($text) {
        return '<span class="inline-tag ' . $text[1] . '">' . $text[2] . '</span>';
    }, $text);
    return $text;
}
// 单选框-radio
function Bf_Radio($text)
{
    $text = preg_replace_callback('/\[radio color=\"(.*?)\".*?\ checked=\"(.*?)"\](.*?)\[\/radio\]/ism', function ($text) {
        return '<div class="checkbox ' . $text[1] . ' checked"><input type="radio" ' . $text[2] . '>' . $text[3] . '</div>';
    }, $text);
    return $text;
}

function Bf_Mark($text)
{
    $text = preg_replace_callback(
        '/{%\s*label\s+(\S+?)(?:\s+(\S+))?\s*%}/',
        function ($matches) {
            $content = $matches[1]; // 获取文本内容
            // 如果指定了颜色类，则使用，否则默认为'default'
            $color_class = isset($matches[2]) ? $matches[2] : 'default';

            return '<mark class="hl-label ' . $color_class . '">' . $content . '</mark>';
        },
        $text
    );
    return $text;
}

function Font($text)
{
    $text = preg_replace_callback('/\[font size=\"(.*?)"\ color=\"(.*?)"\](.*?)\[\/font\]/ism', function ($text) {
        return '<font style="font-size: ' . $text[1] . 'px;color:' . $text[2] . '">' . $text[3] . '</font>';
    }, $text);
    return $text;
}

function codeHightLight($text){
    // 使用正则表达式将 <pre><code class="lang-xxx">...</code></pre> 或 <pre><code>...</code></pre> 转换为自定义的 HTML 结构
    $text = preg_replace_callback(
        '/<pre><code(?: class="lang-(.*?)")?>(.*?)<\/code><\/pre>/s',
        function ($matches) {
            // 如果没有匹配到语言类型，默认使用 'plaintext'
            $language = isset($matches[1]) && !empty($matches[1]) ? $matches[1] : 'plaintext';
            $code = $matches[2]; // 获取代码并转义 HTML 实体
            
            // 将代码按行分割
            $lines = explode("\n", $code);
            $line_numbers = '';
            
            // 生成行号和代码行
            foreach ($lines as $index => $line) {
                $line_number = $index + 1;
                $line_numbers .= "<span class=\"line\">{$line_number}</span><br>";
            }
    
            // 返回自定义的 HTML 结构
            return '
            <figure class="highlight ' . $language . '">
                <table>
                    <tbody>
                        <tr>
                            <td class="gutter"><pre>' . $line_numbers . '</pre></td>
                            <td class="code"><pre class="lang-' . $language . '"><code>' . $code . '</code></pre></td>
                        </tr>
                    </tbody>
                </table>
            </figure>';
        },
        $text
    );
    return $text;
}

function timeLine($text){
    $text = preg_replace_callback(
        '/{%\s*timeline\s*(.*?)\s*%}(.*?){%\s*endtimeline\s*%}/s',
        function ($matches) {
            // 分解并处理模板标签参数
            $params = explode(',', $matches[1]);
            $year = trim($params[0]);
            $color_class = isset($params[1]) ? trim($params[1]) : 'undefined';

            // 分解并处理时间线内容
            $timeline_contents = '';
            preg_match_all('#<!--\s*timeline\s*(.*?)\s*-->(.*?)<!--\s*endtimeline\s*-->#is', $matches[2], $timeline_contents_template);
            for ($i = 0; $i < count($timeline_contents_template[1]); $i++) {
                $date = $timeline_contents_template[1][$i];
                $text = $timeline_contents_template[2][$i];

                $timeline_contents .= '<div class="timeline-item"><div class="timeline-item-title"><div class="item-circle"><p>' . trim($date) . '</p></div></div><div class="timeline-item-content"><p>' . trim($text) . '</p></div></div>';
            }

            // 构建最终的HTML结构
            $timeline_contents = preg_replace('/<br\s*\/?>/', '', $timeline_contents);
            $rendered_html = '<div class="custom-tags"><div class="timeline ' . $color_class . '"><div class="timeline-item headline"><div class="timeline-item-title"><div class="item-circle"><p>' . $year . '</p></div></div></div>' . $timeline_contents . '</div></div>';

            return $rendered_html;
        },
        $text
    );
    return $text;
}

function ArtPlayer($text)
{
    $text = preg_replace_callback('/\[video title=\"(.*?)"\ url=\"(.*?)"\ container=\"(.*?)"\ subtitle=\"(.*?)"\ poster=\"(.*?)"\](.*?)\[\/video\]/ism', function ($text) {
        $t = explode("<br>", $text[6]);
        for ($i = 0; $i < count($t); $i++) {
            $a[] = explode("|", $t[$i]);
        }
        for ($i = 0; $i < count($a); $i++) {
            $cut[$i]['time'] = isset($a[$i][0]) ? (int) $a[$i][0] : 0;
            $cut[$i]['text'] = isset($a[$i][1]) ? $a[$i][1] : '';
            unset($cut[$i][0]);
            unset($cut[$i][1]);
        }
        $cut[0]['time'] == null ? $highlight = '[]' : $highlight = json_encode($cut);
        $text[4] == ' ' ? $tooltip = '无字幕' : $tooltip = '默认字幕';
        return '
    <div class="iframe_video artplayer artplayer-' . $text[3] . '"></div>
    <script>
        var ' . $text[3] . ' = new Artplayer({
            container: ".artplayer-' . $text[3] . '",
            url: "' . $text[2] . '",
            title: "' . $text[1] . '",
            poster: "' . $text[5] . '",
            subtitle: {
                url: "' . $text[4] . '",
            },            
            volume: 0.5,
            muted: false,
            autoplay: false,
            pip: true,
            autoSize: true,
            autoMini: false,
            screenshot: true,
            setting: true,
            loop: true,
            flip: true,
            playbackRate: true,
            aspectRatio: true,
            fullscreen: true,
            fullscreenWeb: true,
            subtitleOffset: true,
            miniProgressBar: true,
            mutex: true,
            backdrop: true,
            playsInline: true,
            autoPlayback: true,
            theme: "#23ade5",
            lang: navigator.language.toLowerCase(),
            whitelist: ["*"],
            moreVideoAttr: {
                crossOrigin: "anonymous",
            },
            settings: [{
                width: 200,
                html: "字幕",
                tooltip: "' . $tooltip . '",
                selector: [{
                    html: "Display",
                    tooltip: "显示",
                    switch: true,
                    onSwitch: function (item) {
                        item.tooltip = item.switch ? "关闭" : "显示";
                        ' . $text[3] . '.subtitle.show = !item.switch;
                        return !item.switch;
                    },
                }],
                onSelect: function(item) {
                    art.subtitle.switch(item.url, {
                        name: item.html,
                    });
                    return item.html;
                },
            }, ],
            highlight: ' . $highlight . '
        });
    </script>';
    }, $text);
    return $text;
}

// 重写文章图片加载
function PostImage($text)
{
    $pattern = '/<img[^>]*src="([^"]+)"[^>]*alt="([^"]+)"[^>]*(style="[^"]+")[^>]*>/i';
    $replacement = '<img title="$2" alt="$2" data-lazy-src="$1" $3 src="' . GetLazyLoad() . '">';
    $text = preg_replace($pattern, $replacement, $text);
    return $text;
}

/**
 * 判断时间区间
 * 
 * 使用方法  if(timeZone($this->date->timeStamp)) echo 'ok';
 */
function timeZone($from)
{
    $now = new Typecho_Date(Typecho_Date::gmtTime());
    return $now->timeStamp - $from < 24 * 60 * 60 ? true : false;
}


//获取Gravatar头像 QQ邮箱取用qq头像
function getGravatar($email, $name, $comments_a, $s = 96, $d = 'mp', $r = 'g')
{
    preg_match_all('/((\d)*)@qq.com/', $email, $vai);
    if (empty($vai['1']['0'])) {
        $url = Helper::options()->GravatarSelect;
        $url .= md5(strtolower(trim($email)));
        $url .= "?s=$s&d=$d&r=$r";
        $imga = '<img ' . $comments_a . ' src="' . GetLazyLoad() . '" data-lazy-src="' . $url . '" >';
    } else {
        $url = 'https://cravatar.cn/avatar/'.md5(strtolower(trim($email)));
        $imga = '<img ' . $comments_a . ' src="' . GetLazyLoad() . '" data-lazy-src="' . $url . '" >';
    }
    return $imga;
}

// 获取浏览器信息
function getBrowser($agent)
{
    $browsers = [
        '/MSIE\s([^\s|;]+)/i' => ['label' => 'Internet Explorer', 'icon' => 'fab fa-internet-explorer'],
        '/FireFox\/([^\s]+)/i' => ['label' => 'FireFox', 'icon' => 'fab fa-firefox-browser'],
        '/Maxthon([\d]*)\/([^\s]+)/i' => ['label' => '遨游', 'icon' => 'iconfont icon-maxthon'],
        '#360([a-zA-Z0-9.]+)#i' => ['label' => '360极速浏览器', 'icon' => 'iconfont icon-chrome'],
        '/Edg([\d]*)\/([^\s]+)/i' => ['label' => 'Microsoft Edge', 'icon' => 'fab fa-edge'],
        '/UC/i' => ['label' => 'UC浏览器', 'icon' => 'iconfont icon-UCliulanqi'],
        '/QQ/i' => ['label' => 'QQ浏览器', 'icon' => 'iconfont icon-QQliulanqi'],
        '/QQBrowser\/([^\s]+)/i' => ['label' => 'QQ浏览器', 'icon' => 'iconfont icon-QQliulanqi'],
        '/UBrowser/i' => ['label' => 'UC浏览器', 'icon' => 'iconfont icon-UCliulanqi'],
        '/Opera[\s|\/]([^\s]+)|OPR/i' => ['label' => 'Opera', 'icon' => 'fab fa-opera'],
        '/YaBrowser/i' => ['label' => 'Yandex', 'icon' => 'fab fa-yandex-international'],
        '/Quark/i' => ['label' => 'Quark', 'icon' => 'iconfont icon-quark'],
        '/XiaoMi/i' => ['label' => '小米浏览器', 'icon' => 'iconfont icon-XiaoMi'],
        '/Chrome([\d]*)\/([^\s]+)/i' => ['label' => 'Google Chrome', 'icon' => 'fab fa-chrome'],
        '/safari\/([^\s]+)/i' => ['label' => 'Safari', 'icon' => 'fab fa-safari'],
    ];
    $defaultBrowser = ['label' => 'Google Chrome', 'icon' => 'fab fa-chrome'];
    foreach ($browsers as $pattern => $info) {
        if (preg_match($pattern, $agent, $regs)) {
            echo "<i class='{$info['icon']}'></i>&nbsp;&nbsp;{$info['label']}";
            return;
        }
    }
    echo "<i class='{$defaultBrowser['icon']}'></i>&nbsp;&nbsp;{$defaultBrowser['label']}";
}

// 获取操作系统信息
function getOs($agent) {
    $osData = [
        'Windows Vista' => ['/nt 6.0/i', 'iconfont icon-windows'],
        'Windows 7' => ['/nt 6.1/i', 'iconfont icon-windows'],
        'Windows 8' => ['/nt 6.2/i', 'fab fa-windows'],
        'Windows 8.1' => ['/nt 6.3/i', 'fab fa-windows'],
        'Windows XP' => ['/nt 5.1/i', 'iconfont icon-windows'],
        'Windows 10' => ['/nt 10.0/i', 'fab fa-windows'],
        'Windows 11' => ['/nt 11.0/i', 'fab fa-windows'],
        'Android Pie' => ['/android 9/i', 'fab fa-android'],
        'Android ICS' => ['/android 4/i', 'fab fa-android'],
        'Android Lollipop' => ['/android 5/i', 'fab fa-android'],
        'Android M' => ['/android 6/i', 'fab fa-android'],
        'Android Nougat' => ['/android 7/i', 'fab fa-android'],
        'Android Oreo' => ['/android 8/i', 'fab fa-android'],
        'Android Q' => ['/android 10/i', 'fab fa-android'],
        'Android 11' => ['/android 11/i', 'fab fa-android'],
        'Android 12' => ['/android 12/i', 'fab fa-android'],
        'Android 13' => ['/android 13/i', 'fab fa-android'],
        'Ubuntu' => ['/ubuntu/i', 'fab fa-ubuntu'],
        'Arch Linux' => ['/Arch/i', 'iconfont icon-Arch-Linux'],
        'Manjaro' => ['/manjaro/i', 'iconfont icon-manjaro'],
        'Debian' => ['/debian/i', 'iconfont icon-debianos'],
        'Linux' => ['/linux/i', 'fab fa-linux'],
        'iOS(iPad)' => ['/iPad/i', 'fab fa-apple'],
        'iOS(iPhone)' => ['/iPhone/i', 'fab fa-apple'],
        'MacOS' => ['/mac/i', 'fab fa-apple'],
        'Android' => ['/fusion/i', 'fab fa-android'],
    ];

    foreach ($osData as $osName => list($pattern, $iconClass)) {
        if (preg_match($pattern, $agent)) {
            echo '&nbsp;&nbsp;<i class="' . $iconClass . '"></i>&nbsp;' . $osName . '&nbsp;/&nbsp;';
            return;
        }
    }

    // Default case
    echo '&nbsp;&nbsp;<i class="fab fa-linux"></i>&nbsp;&nbsp;Linux&nbsp;/&nbsp;';
}



function commentRank($widget, $email = NULL)
{
    if (empty($email))
        return;
    $txt = Helper::options()->CustomAuthenticated;
    if ($txt == "") {
        $txt = 'x||x';
    }
    $string_arr = explode("\r\n", $txt);
    $long = count($string_arr);
    $mailList = [];
    $authList = [];
    for ($i = 0; $i < $long; $i++) {
        $parts = explode("||", $string_arr[$i]);
        if (count($parts) >= 2) {
            $mailList[] = $parts[0];
            $authList[] = $parts[1];
        }
    }
    $all = array_combine($mailList, $authList);

    if ($widget->authorId == $widget->ownerId) {
        echo '<span class="vtag vmaster">博主</span>';
    } else if (in_array($email, $mailList)) {
        echo '<span class="vtag vauth">' . $all[$email] . '</span>';

    } else {
        echo '<span class="vtag vvisitor">访客</span>';
    }
}

//获取评论的锚点链接
function get_comment_at($coid)
{
    $db = Typecho_Db::get();
    $prow = $db->fetchRow($db->select('parent,status')->from('table.comments')
        ->where('coid = ?', $coid)); //当前评论
    $mail = "";
    $parent = @$prow['parent'];
    if ($parent != "0") { //子评论
        $arow = $db->fetchRow($db->select('author,status,mail')->from('table.comments')
            ->where('coid = ?', $parent)); //查询该条评论的父评论的信息
        @$author = @$arow['author']; //作者名称
        $mail = @$arow['mail'];
        if (@$author && $arow['status'] == "approved") { //父评论作者存在且父评论已经审核通过
            if (@$prow['status'] == "waiting") {
                echo '<span class="commentReview">（评论审核中）</span>';
            }
            echo '<a onclick="b(this);return false;" href="#comment-' . $parent . '">@' . htmlspecialchars($author, ENT_QUOTES, 'UTF-8') . '</a>';
        } else { //父评论作者不存在或者父评论没有审核通过
            if (@$prow['status'] == "waiting") {
                echo '<span class="commentReview">（评论审核中）</span>';
            } else {
                echo '';
            }
        }

    } else { //母评论，无需输出锚点链接
        if (@$prow['status'] == "waiting") {
            echo '<span class="commentReview">（评论审核中）</span>';
        } else {
            echo '';
        }
    }
}
/**
 * 重写评论显示函数
 */
function threadedComments($comments, $options)
{
    $commentClass = '';
    if ($comments->authorId) {
        if ($comments->authorId == $comments->ownerId) {
            $commentClass .= ' comment-by-author';
        } else {
            $commentClass .= ' comment-by-user';
        }
    }
    // 获取当前用户信息
    $user = Typecho_Widget::widget('Widget_User');
    $isAuthor = $user->hasLogin() && $user->uid == $comments->ownerId;
    $commentLevelClass = $comments->levels > 0 ? ' comment-child' : ' comment-parent';
    ?>
    <li id="li-<?php $comments->theId(); ?>" class="comment-body<?php
      if ($comments->levels > 0) {
          echo ' comment-child';
          $comments->levelsAlt(' comment-level-odd', ' comment-level-even');
      } else {
          echo ' comment-parent';
      }
      $comments->alt(' comment-odd', ' comment-even');
      echo $commentClass;
      ?>">
        <div id="<?php $comments->theId(); ?>">
            <div class="comment-author">
                <?php $email = $comments->mail;
                $name = $comments->author;
                $comments_a = 'class="vimg" style="border-radius: 50%;"';
                echo getGravatar($email, $name, $comments_a); ?>
                <div class="vuser">
                    <cite class="vnick" title="<?php $comments->author; ?>">
                        <?php $comments->author(); ?>
                    </cite>
                    <?php commentRank($comments, $comments->mail); ?>
                    <span class="vtag">
                        <?php $parentMail = get_comment_at($comments->coid) ?>
                        <?php echo $parentMail; ?>
                    </span>
                </div>
            </div>
            <div class="vhead">
                <a class="vtime" href="<?php $comments->permalink(); ?>"><?php $comments->date('Y-m-d H:i'); ?></a>
                <?php if (Helper::options()->CloseComments == 'off'): ?>
                    <span class="comment-reply">
                        <?php $comments->reply(); ?>
                    </span>
                <?php endif ?>
            </div>
            <div class="comment-content">
                <?php
                // 仅文章作者可见 {% self text %} 包裹的内容
                $content = $comments->content;
                if ($isAuthor) {
                    $content = preg_replace('/\{% self (.*?) %\}/is', '$1', $content);
                } else {
                    $content = preg_replace('/\{% self .*?%\}/is', '<div class=comment-self>仅作者可见</div>', $content);
                }
                $content = ParseCode($content) ;
                $content = PostImage($content);
                echo $content;
                ?>
            </div>
            <span class="comment-ua">
                <?php getOs($comments->agent); ?>
                <?php getBrowser($comments->agent); ?>
            </span>
        </div>
        <?php if ($comments->children) { ?>
            <div class="comment-children">
                <?php $comments->threadedComments($options); ?>
            </div>
        <?php } ?>
    </li>
<?php }

// 主页封面
function img_postthemb($thiz, $default_img)
{
    $content = $thiz->content;
    $ret = preg_match("/\<img.*?src\=\"(.*?)\"[^>]*>/i", $content, $thumbUrl);
    if ($ret === 1 && count($thumbUrl) == 2) {
        return $thumbUrl[1];
    } else {
        return $default_img = "https://i.loli.net/2020/05/01/gkihqEjXxJ5UZ1C.jpg";
    }
}

//  输出标签  
function printTag($that)
{ ?>
    <?php if (count($that->tags) > 0): ?>
        <?php foreach ($that->tags as $tags): ?>
            <a href="<?php print($tags['permalink']) ?>" class="post-meta__tags"><span>
                    <?php print($tags['name']) ?>
                </span></a>
        <?php endforeach; ?>
    <?php else: ?>
        <a class="post-meta__tags"><span>无标签</span></a>
    <?php endif; ?>
<?php }


//当前人数
function onlinePeople()
{
    // 使用动态路径，不要硬编码主题名
    $themeDir = defined('__TYPECHO_ROOT_DIR__') ? __TYPECHO_ROOT_DIR__ . '/usr/themes/' . basename(dirname(__DIR__)) : 'usr/themes/butterfly';
    $online_log = $themeDir . "/online.dat"; //保存人数的文件到根目录,
    $timeout = 30; //30秒内没动作者,认为掉线
    if (!file_exists($online_log)) {
        fopen($online_log, "w");
    }
    $entries = file($online_log);
    $temp = array();
    for ($i = 0; $i < count($entries); $i++) {
        $entry = explode(",", trim($entries[$i]));
        if (($entry[0] != getenv('REMOTE_ADDR')) && ($entry[1] > time())) {
            array_push($temp, $entry[0] . "," . $entry[1] . "\n"); //取出其他浏览者的信息,并去掉超时者,保存进$temp
        }
    }
    array_push($temp, getenv('REMOTE_ADDR') . "," . (time() + ($timeout)) . "\n"); //更新浏览者的时间
    $slzxrs = count($temp); //计算在线人数
    $entries = implode("", $temp);
    //写入文件
    $fp = fopen($online_log, "w");
    flock($fp, LOCK_EX); //flock() 不能在NFS以及其他的一些网络文件系统中正常工作
    fputs($fp, $entries);
    flock($fp, LOCK_UN);
    fclose($fp);
    echo $slzxrs;
}

function only_get_post_view($archive)
{
    $db = Typecho_Db::get();
    $cid = $archive->cid;
    $exist = $db->fetchRow($db->select('views')->from('table.contents')->where('cid = ?', $cid))['views'];
    if ($exist >= 10000) {
        $out = sprintf('%.2f W', $exist / 10000);
    } else {
        $out = sprintf('%d', $exist);
    }
    echo $out;
}
//总访问量
function theAllViews()
{
    $db = Typecho_Db::get();
    $row = $db->fetchAll($db->select('SUM(views)')->from('table.contents'));
    echo array_values($row[0])[0];
}
//  回复可见       
Typecho_Plugin::factory('Widget_Abstract_Contents')->excerptEx = array('myyodux', 'one');
Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('myyodux', 'one');
class myyodux
{
    public static function one($con, $obj, $text)
    {
        $text = empty($text) ? $con : $text;
        if (!$obj->is('single')) {
            $text = preg_replace("/\[hide\](.*?)\[\/hide\]/sm", '', $text);
            //   $text = preg_replace("/\n\s*){3,}/sm",' ',$text);
        }
        return $text;
    }
}

/**
 * 显示上一篇
 *
 * 如果没有下一篇,返回null
 */
function thePrevCid($widget, $default = NULL)
{
    $db = Typecho_Db::get();
    $sql = $db->select()->from('table.contents')
        ->where('table.contents.created < ?', $widget->created)
        ->where('table.contents.status = ?', 'publish')
        ->where('table.contents.type = ?', $widget->type)
        ->where('table.contents.password IS NULL')
        ->order('table.contents.created', Typecho_Db::SORT_DESC)
        ->limit(1);
    $content = $db->fetchRow($sql);

    if ($content) {
        return $content["cid"];
    } else {
        return $default;
    }
}

/**
 * 获取下一篇文章mid
 *
 * 如果没有下一篇,返回null
 */
function theNextCid($widget, $default = NULL)
{
    $db = Typecho_Db::get();
    $sql = $db->select()->from('table.contents')
        ->where('table.contents.created > ?', $widget->created)
        ->where('table.contents.status = ?', 'publish')
        ->where('table.contents.type = ?', $widget->type)
        ->where('table.contents.password IS NULL')
        ->order('table.contents.created', Typecho_Db::SORT_ASC)
        ->limit(1);
    $content = $db->fetchRow($sql);

    if ($content) {
        return $content["cid"];
    } else {
        return $default;
    }
}

/* 判断是否是移动端 */
function isMobile()
{
    if (isset($_SERVER['HTTP_X_WAP_PROFILE']))
        return true;
    if (isset($_SERVER['HTTP_VIA'])) {
        return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
    }
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        $clientkeywords = array('nokia', 'sony', 'ericsson', 'mot', 'samsung', 'htc', 'sgh', 'lg', 'sharp', 'sie-', 'philips', 'panasonic', 'alcatel', 'lenovo', 'iphone', 'ipod', 'blackberry', 'meizu', 'android', 'netfront', 'symbian', 'ucweb', 'windowsce', 'palm', 'operamini', 'operamobi', 'openwave', 'nexusone', 'cldc', 'midp', 'wap', 'mobile');
        if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT'])))
            return true;
    }
    if (isset($_SERVER['HTTP_ACCEPT'])) {
        if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
            return true;
        }
    }
    return false;
}
// 微博热搜
function weibohot()
{
    $api = file_get_contents('https://weibo.com/ajax/side/hotSearch');
    $data = json_decode($api, true)['data']['realtime'];

    $jyzy = array(
        '电影' => '影',
        '剧集' => '剧',
        '综艺' => '综',
        '音乐' => '音',
        '盛典' => '盛',
        '晚会' => '晚',
    );

    $hotness = array(
        '爆' => 'weibo-boom',
        '热' => 'weibo-hot',
        '沸' => 'weibo-boil',
        '新' => 'weibo-new',
        '荐' => 'weibo-recommend',
        '音' => 'weibo-jyzy',
        '影' => 'weibo-jyzy',
        '剧' => 'weibo-jyzy',
        '综' => 'weibo-jyzy',
        '盛' => 'weibo-jyzy',
        '晚' => 'weibo-jyzy',
    );

    foreach ($data as $item) {
        $hot = '荐';
        if (isset($item['is_ad'])) {
            continue;
        }
        if (isset($item['is_boom'])) {
            $hot = '爆';
        }
        if (isset($item['is_hot'])) {
            $hot = '热';
        }
        if (isset($item['is_fei'])) {
            $hot = '沸';
        }
        if (isset($item['is_new'])) {
            $hot = '新';
        }
        if (isset($item['flag_desc'])) {
            $hot = $jyzy[$item['flag_desc']];
        }
        echo '<div class="weibo-list-item"><div class="weibo-hotness ' . $hotness[$hot] . '">' . $hot . '</div><span class="weibo-title"><a title="' . $item['note'] . '" href="https://s.weibo.com/weibo?q=%23' . $item['word'] . '%23" target="_blank" rel="external nofollow noreferrer" style="color:#a08ed5">' . $item['note'] . '</a></span><div class="weibo-num"><span>' . $item['num'] . '</span></div></div>';
    }
}

// 自定义文章摘要
function summaryContent($widget)
{
    $customSummary = '';
    if ($widget->fields->customSummary) {
        $customSummary = $widget->fields->customSummary;
    } elseif ($widget->fields->excerpt && $widget->fields->excerpt != '') {
        $customSummary = $widget->fields->excerpt;
    } else {
        $customSummary = $widget->excerpt(130);
    }
    echo $customSummary;
}

//主页封面处理函数
function noCover($widget)
{
    if ($widget->fields->NoCover == "off") {
        return false;
    }
    return true;
}

function cdnBaseUrl(){
    $StaticFile = Helper::options()->StaticFile;
    $CDNURL = Helper::options()->CDNURL;
    if($StaticFile == 'CDN' && $CDNURL == ''){
        echo 'https://' . Helper::options()->jsdelivrLink . '/gh/wehaox/CDN@main/butterfly';
    }
    elseif($StaticFile == 'CDN' && $CDNURL != ''){
        echo $CDNURL;
    }
    else{
        echo Helper::options()->themeUrl;
    }
}

function darkTimeFunc(){
    $time = Helper::options()->darkTime;
    if(empty($time)){
        $time = "20-7";
    }
    $timeSlot = explode('-', $time);
    echo "e >= $timeSlot[0] || e <= $timeSlot[1]";
}

function getThemeVersion()
{
  $version = Plugin::parseInfo(Helper::options()->themeFile(Helper::options()->theme, "index.php"))["version"];
  return $version;
}

?>
