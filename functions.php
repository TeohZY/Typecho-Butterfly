<?php
use Typecho\Plugin;
if (!defined('__TYPECHO_ROOT_DIR__'))
    exit;

require_once('api/api.php');
require_once('api/search.php');
require_once('config/custom_config.php');
require_once('lib/core.php');
require_once('lib/Vditor/index.php');

// 新文章缩略图
function get_ArticleThumbnail($widget)
{
    // 当文章无图片时的随机缩略图
//   $rand = mt_rand(1, 26); // 随机 1-9 张缩略图
//   // 缩略图加速
//   $rand_url;
//   if(!empty(Helper::options()->articleImgSpeed)){
//     $rand_url = Helper::options()->articleImgSpeed;
//   }else {
//     $rand_url = $widget->widget('Widget_Options')->themeUrl . '/images/articles/';
//   }
//   $random =  $rand_url . $rand . '.jpg'; // 随机缩略图路径
//   $random =  'https://static01.imgkr.com/temp/517e5d14c312427dbf93304563869279.png';
//   $attach = $widget->attachments(1)->attachment;
    $random = '/usr/themes/butterfly/img/DefualtThumbnail.jpg';
    $pattern = '/\<img.*?src\=\"(.*?)\"[^>]*>/i';

    //如果有自定义缩略图
    if ($widget->fields->thumb) {
        return $widget->fields->thumb;
    } else if (preg_match_all($pattern, $widget->content, $thumbUrl) && strlen($thumbUrl[1][0]) > 7) {
        return $thumbUrl[1][0];
    } else {
        return $random;
    }
}

// 主页文章缩略图
function GetRandomThumbnail($widget)
{
    // $random = 'https://i.loli.net/2020/05/01/gkihqEjXxJ5UZ1C.jpg';
    $random = '/usr/themes/butterfly/img/DefualtThumbnail.jpg';
    if (Helper::options()->futureRandom) {
        $moszu = explode("\r\n", Helper::options()->futureRandom);
        $random = $moszu[array_rand($moszu, 1)] . "?futureRandom=" . mt_rand(0, 1000000);
    }
    $pattern = '/\<img.*?src\=\"(.*?)\"[^>]*>/i';
    $patternMD = '/\!\[.*?\]\((http(s)?:\/\/.*?(jpg|jpeg|gif|png|webp))/i';
    $patternMDfoot = '/\[.*?\]:\s*(http(s)?:\/\/.*?(jpg|jpeg|gif|png|webp))/i';
    $t = preg_match_all($pattern, $widget->content, $thumbUrl);
    $img = $random;
    if ($widget->fields->thumb) {
        $img = $widget->fields->thumb;
    } elseif ($t) {
        $img = $thumbUrl[1][0];
    } elseif (preg_match_all($patternMD, $widget->content, $thumbUrl)) {
        $img = $thumbUrl[1][0];
    } elseif (preg_match_all($patternMDfoot, $widget->content, $thumbUrl)) {
        $img = $thumbUrl[1][0];
    }
    echo $img;
}
// 文章封面缩略图
function GetRandomThumbnailPost($widget)
{
    $img = '';
    if ($widget->fields->thumb) {
        $img = $widget->fields->thumb;
    }
    echo $img;
}

// 全站字数统计
function allOfCharacters()
{
    $showPrivate = 0;
    $chars = 0;
    $db = Typecho_Db::get();
    if ($showPrivate == 0) {
        $select = $db->select('text')->from('table.contents')->where('table.contents.status = ?', 'publish');
    } else {
        $select = $db->select('text')->from('table.contents');
    }
    $rows = $db->fetchAll($select);
    foreach ($rows as $row) {
        $chars += mb_strlen($row['text'], 'UTF-8');
    }
    $unit = '';
    if ($chars >= 10000) {
        $chars /= 10000;
        $unit = 'W';
    } else if ($chars >= 1000) {
        $chars /= 1000;
        $unit = 'K';
    }
    $out = sprintf('%.2lf %s', $chars, $unit);
    echo $out;
}

function thumb($cid)
{
    if (empty($imgurl)) {
        $rand_num = 10; //随机图片数量，根据图片目录中图片实际数量设置
        if ($rand_num == 0) {
            $imgurl = "img/0.jpg";
            //如果$rand_num = 0,则显示默认图片，须命名为"0.jpg"，注意是绝对地址
        } else {
            $imgurl = "img/" . rand(1, $rand_num) . ".jpg";
            //随机图片，须按"1.jpg","2.jpg","3.jpg"...的顺序命名，注意是绝对地址
        }
    }
    $db = Typecho_Db::get();
    $rs = $db->fetchRow($db->select('table.contents.text')
        ->from('table.contents')
        ->where('table.contents.type = ?', 'attachment')
        ->where('table.contents.parent= ?', $cid)
        ->order('table.contents.cid', Typecho_Db::SORT_ASC)
        ->limit(1));
    $img = unserialize($rs['text']);
    if (empty($img)) {
        echo $imgurl;
    } else {
        echo '你的博客地址' . $img['path'];
    }
}


// 评论时间
function timesince($older_date, $comment_date = false)
{
    $chunks = array(
        array(86400, '天'),
        array(3600, '小时'),
        array(60, '分'),
        array(1, '秒'),
    );
    $newer_date = time();
    $since = abs($newer_date - $older_date);
    if ($since < 2592000) {
        for ($i = 0, $j = count($chunks); $i < $j; $i++) {
            $seconds = $chunks[$i][0];
            $name = $chunks[$i][1];
            if (($count = floor($since / $seconds)) != 0)
                break;
        }
        $output = $count . $name . '前';
    } else {
        $output = !$comment_date ? (date('Y-m-j G:i', $older_date)) : (date('Y-m-j', $older_date));
    }
    return $output;
}



// 文章内获取第一张图做封面
function getPostImg($archive)
{
    $img = array();
    //  匹配 img 的 src 的正则表达式
    preg_match_all("/<img.*?src=\"(.*?)\".*?\/?>/i", $archive->content, $img);
    //  判断是否匹配到图片
    if (count($img) > 0 && count($img[0]) > 0) {
        //  返回图片
        return $img[1][0];
    } else {
        //  如果没有匹配到就返回 none
        return 'none';
    }
}

function createCatalog($obj)
{ //为文章标题添加锚点
    global $catalog;
    global $catalog_count;
    $catalog = array();
    $catalog_count = 0;
    $obj = preg_replace_callback('/<h([1-6])(.*?)>(.*?)<\/h\1>/i', function ($obj) {
        global $catalog;
        global $catalog_count;
        $catalog_count++;
        $catalog[] = array('text' => trim(strip_tags($obj[3])), 'depth' => $obj[1], 'count' => $catalog_count);
        return '<h' . $obj[1] . $obj[2] . ' id="cl-' . $catalog_count . '"><a class="markdownIt-Anchor" href="#cl-' . $catalog_count . '"></a>' . $obj[3] . '</h' . $obj[1] . '>';
    }, $obj);
    return $obj;
}


// 目录树
function getCatalog()
{ //输出文章目录容器
    global $catalog;
    $index = '';
    if ($catalog) {
        $prev_depth = '';
        $to_depth = 0;
        foreach ($catalog as $catalog_item) {
            $catalog_depth = $catalog_item['depth'];
            if ($prev_depth) {
                if ($catalog_depth == $prev_depth) {
                    $index .= '</li >' . "\n";
                } elseif ($catalog_depth > $prev_depth) {
                    $to_depth++;
                    $index .= '<ol class="toc-child">' . "\n";
                } else {
                    $to_depth2 = ($to_depth > ($prev_depth - $catalog_depth)) ? ($prev_depth - $catalog_depth) : $to_depth;
                    if ($to_depth2) {
                        for ($i = 0; $i < $to_depth2; $i++) {
                            $index .= '</li>' . "\n" . '</ol>' . "\n";
                            $to_depth--;
                        }
                    }
                    $index .= '</li>';
                }
            }
            $index .= '<li class="toc-item">
            <a class="toc-link" href="#cl-' . $catalog_item['count'] . '">
            <span class="toc-number"></span>
            <span class="toc-text">' . $catalog_item['text'] . '</span>
            </a>';
            $prev_depth = $catalog_item['depth'];
        }
        for ($i = 0; $i <= $to_depth; $i++) {
            $index .= '</li>' . "\n";
        }
        // $index = '<div >'."\n".'<div >'."\n"."\n".$index.'</div>'."\n".'</div>'."\n";
    }
    echo $index;
}

/* 获取懒加载图片 */
function GetLazyLoad()
{
    if (Helper::options()->LazyLoad) {
        return Helper::options()->LazyLoad;
    } else {
        return "data:image/gif;base64,R0lGODdhAQABAPAAAMPDwwAAACwAAAAAAQABAAACAkQBADs=";
    }
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


/**
 * 获取标签数目
 * 
 * 语法: <?php echo tagsNum(); ?>
 * 
 * @access protected
 * @return integer
 */
function tagsNum($display = true)
{
    $db = Typecho_Db::get();
    $total_tags = $db->fetchObject($db->select(array('COUNT(mid)' => 'num'))
        ->from('table.metas')
        ->where('table.metas.type = ?', 'tag'))->num;
    if ($display) {
        echo $total_tags;
    } else {
        return $total_tags;
    }
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
    for ($i = 0; $i < $long; $i++) {
        $mailList[] = explode("||", $string_arr[$i])[0];
        $authList[] = explode("||", $string_arr[$i])[1];
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
            echo '<a onclick="b(this);return false;" href="#comment-' . $parent . '">@' . $author . '</a>';
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
    $online_log = "usr/themes/butterfly/online.dat"; //保存人数的文件到根目录,
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

//调用博主最近文章更新时间
function get_last_update()
{
    $num = '1';
    $type = 'post';
    $status = 'publish';
    $now = time();
    $db = Typecho_Db::get();
    $prefix = $db->getPrefix();
    $create = $db->fetchRow($db->select('created')->from('table.contents')->where('table.contents.type=? and status=?', $type, $status)->order('created', Typecho_Db::SORT_DESC)->limit($num));
    $update = $db->fetchRow($db->select('modified')->from('table.contents')->where('table.contents.type=? and status=?', $type, $status)->order('modified', Typecho_Db::SORT_DESC)->limit($num));
    if ($create >= $update) {
        echo Typecho_I18n::dateWord(isset($create['created']), $now);
    } else {
        $lastModified = $now - $update['modified'];
        $timeIntervals = [
            31536000 => '年',
            2592000 => '个月',
            86400 => '天',
            3600 => '小时',
            60 => '分钟',
            1 => '秒'
        ];
        foreach ($timeIntervals as $interval => $label) {
            if ($lastModified > $interval) {
                $value = floor($lastModified / $interval);
                echo $value . ' ' . $label . '前';
                break;
            }
        }
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
function RunTime()
{
    $site_create_time = strtotime(Helper::options()->buildtime);
    $time = time() - $site_create_time;
    if (is_numeric($time)) {
        if ($time >= 86400) {
            $days = floor($time / 86400);
            $time = ($time % 86400);
            echo $days . ' 天';
        } else {
            echo '1 天';
        }

    } else {
        echo '';
    }
}
function RecapOutPut($login)
{
    $siteKey = Helper::options()->siteKey;
    $secretKey = Helper::options()->secretKey;
    if (!empty($siteKey) && !empty($secretKey) && !$login) {
        echo '<script src="https://recaptcha.net/recaptcha/api.js" async defer data-no-instant></script>
                              <div class="g-recaptcha" data-sitekey=' . $siteKey . '></div>';
    }
    if (!empty(Helper::options()->hcaptchaSecretKey) && !empty(Helper::options()->hcaptchaAPIKey) && !$login) {
        echo '
            <div id="h-captcha" class="h-captcha" data-sitekey=' . Helper::options()->hcaptchaSecretKey . '></div>';
    }
}

function comments_filter($comment)
{
    if (isset($_REQUEST['text']) != null) {
        if ($_POST['g-recaptcha-response'] == null) {
            throw new Typecho_Widget_Exception(_t('人机验证失败,确认你加载了谷歌人机验证并通过验证'));
        } else {
            $siteKey = Helper::options()->siteKey;
            $secretKey = Helper::options()->secretKey;
            function getCaptcha($recaptcha_response, $secretKey)
            {
                $response = file_get_contents("https://recaptcha.net/recaptcha/api/siteverify?secret=" . $secretKey . "&response=" . $recaptcha_response);
                $response = json_decode($response);
                return $response;
            }
            $resp = getCaptcha($_POST['g-recaptcha-response'], $secretKey);

            if ($resp->success == true) {
                return $comments;
            } else {
                switch ($resp->error - codes) {
                    case '{[0] => "timeout-or-duplicate"}':
                        throw new Typecho_Widget_Exception(_t('验证时间超过2分钟或连续重复发言！'));
                        break;
                    case '{[0] => "invalid-input-secret"}':
                        throw new Typecho_Widget_Exception(_t('博主填了无效的siteKey或者secretKey...'));
                        break;
                    case '{[0] => "bad-request"}':
                        throw new Typecho_Widget_Exception(_t('请求错误！请检查网络'));
                        break;
                    default:
                        throw new Typecho_Widget_Exception(_t('很遗憾，您被当成了机器人...'));
                }
            }
        }
    }
    return $comment;
}


function hcaptcha_filter($comment)
{
    if (isset($_REQUEST['text']) != null) {
        if ($_POST['h-captcha-response'] == null) {
            throw new Typecho_Widget_Exception(_t('人机验证失败,确认你加载了hcaptcha人机验证并通过验证'));
        } else {
            if (isset($_POST['h-captcha-response']) && !empty($_POST['h-captcha-response'])) {
                $secret = Helper::options()->hcaptchaAPIKey;
                $verifyResponse = file_get_contents('https://hcaptcha.com/siteverify?secret=' . $secret . '&response=' . $_POST['h-captcha-response'] . '&remoteip=' . $_SERVER['REMOTE_ADDR']);
                $responseData = json_decode($verifyResponse);
                if ($responseData->success === true || $responseData->success === 1) {
                    return $comments;
                } else {
                    switch ($responseData->error - codes) {
                        case '{[0] => "timeout-or-duplicate"}':
                            throw new Typecho_Widget_Exception(_t('验证时间超过2分钟或连续重复发言！'));
                            break;
                        case '{[0] => "invalid-input-secret"}':
                            throw new Typecho_Widget_Exception(_t('网站管理员填了无效的siteKey或者secretKey...'));
                            break;
                        case '{[0] => "bad-request"}':
                            throw new Typecho_Widget_Exception(_t('请求错误！请检查网络'));
                            break;
                        default:
                            throw new Typecho_Widget_Exception(_t('很遗憾，您被当成了机器人...'));
                    }
                }
            }
        }
    }
    return $comment;
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
        echo Helper::options()->themeUrl . '/static';
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

// 三合一避免重复查询
function get_post_details($archive)
{
    $db = Typecho_Db::get();
    $cid = $archive->cid;

    $row = $db->fetchRow($db->select('text', 'views')->from('table.contents')->where('cid = ?', $cid)->limit(1));
    $text = $row['text'];
    $views = (int)$row['views'];
    $total_length = mb_strlen($text, 'UTF-8');

    $chinese_text = preg_replace("/[^\x{4e00}-\x{9fa5}]/u", "", $text);
    $chinese_length = mb_strlen($chinese_text, 'utf-8');

    $reading_time = ceil($chinese_length / 400);

    if ($archive->is('single')) {
        $cookie = Typecho_Cookie::get('contents_views');
        $cookie = $cookie ? explode(',', $cookie) : array();

        if (!in_array($cid, $cookie)) {
            $db->query($db->update('table.contents')
                ->rows(array('views' => $views + 1))
                ->where('cid = ?', $cid));
            $views += 1;
            array_push($cookie, $cid);
            $cookie = implode(',', $cookie);
            Typecho_Cookie::set('contents_views', $cookie);
        }
    }

    return [
        'total_length' => $total_length,
        'chinese_length' => $chinese_length,
        'reading_time' => $reading_time,
        'views' => $views
    ];
}


function getSiteStatistics()
{
    $db = Typecho_Db::get();
    $now = time();

    // 合并查询，获取所有需要的信息
    $query = $db->select(
        ['SUM(LENGTH(text))' => 'totalChars', 'SUM(views)' => 'totalViews', 'MAX(created)' => 'latestCreate', 'MAX(modified)' => 'latestModify']
    )->from('table.contents')->where('table.contents.status = ?', 'publish')->where('table.contents.type = ?', 'post');

    $result = $db->fetchRow($query);

    // 计算字符数并添加单位
    $chars = $result['totalChars'];
    $unit = '';
    if ($chars >= 10000) {
        $chars /= 10000;
        $unit = 'W';
    } elseif ($chars >= 1000) {
        $chars /= 1000;
        $unit = 'K';
    }
    $charCount = sprintf('%.2lf %s', $chars, $unit);

    // 获取总浏览次数
    $totalViews = $result['totalViews'];

    // 获取最后更新信息
    $latestCreate = $result['latestCreate'];
    $latestModify = $result['latestModify'];
    $lastUpdate = '';

    if ($latestCreate >= $latestModify) {
        $lastUpdate = Typecho_I18n::dateWord($latestCreate, $now);
    } else {
        $lastModified = $now - $latestModify;
        $timeIntervals = [
            31536000 => '年',
            2592000 => '个月',
            86400 => '天',
            3600 => '小时',
            60 => '分钟',
            1 => '秒'
        ];
        foreach ($timeIntervals as $interval => $label) {
            if ($lastModified > $interval) {
                $value = floor($lastModified / $interval);
                $lastUpdate = $value . ' ' . $label . '前';
                break;
            }
        }
    }

    $stat = Typecho_Widget::widget('Widget_Stat');
    $publishedPostsNum = $stat->publishedPostsNum;
    $categoriesNum = $stat->categoriesNum;

    $tagsNum = tagsNum(false);
    

    // 返回结果数组
    return [
        'charCount' => $charCount,
        'totalViews' => $totalViews,
        'lastUpdate' => $lastUpdate,
        'publishedPostsNum' =>  $publishedPostsNum,
        'categoriesNum' =>  $categoriesNum,
        'tagsNum' =>  $tagsNum,
    ];
}
// 处理评论区表情包
function parseOwOTags($commentContent) {
    // 读取 OwO.json 文件
    $owoJsonPath = 'path/to/OwO.json';
    $owoData = json_decode(file_get_contents($owoJsonPath), true);

    // 正则表达式匹配 {% icon QQ,QQ-OK %}
    $pattern = '/\{% icon (\w+),(\w+) %\}/';

    // 使用回调函数替换匹配的标签
    $commentContent = preg_replace_callback($pattern, function($matches) use ($owoData) {
        $category = $matches[1];
        $text = $matches[2];

        // 检查 JSON 数据中是否存在该类别和文本
        if (isset($owoData[$category]['container'])) {
            foreach ($owoData[$category]['container'] as $item) {
                if ($item['text'] === $text) {
                    // 返回带有样式的 img 标签
                    return "<img src='{$item['icon']}' style='height:60px;width:60px;'>";
                }
            }
        }

        // 如果没有找到匹配的，返回原始文本
        return $matches[0];
    }, $commentContent);

    return $commentContent;
}

function getThemeVersion()
{
  $version = Plugin::parseInfo(Helper::options()->themeFile(Helper::options()->theme, "index.php"))["version"];
  return $version;
}

?>