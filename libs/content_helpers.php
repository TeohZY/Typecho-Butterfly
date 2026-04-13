<?php
if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

// 新文章缩略图
function get_ArticleThumbnail($widget)
{
    $random = Helper::options()->themeUrl . '/img/DefualtThumbnail.jpg';
    $pattern = '/\<img.*?src\=\"(.*?)\"[^>]*>/i';

    try {
        $content = @$widget->content;
        if ($content === null || $content === false) {
            $content = '';
        }
    } catch (Throwable $e) {
        $content = '';
    }

    if (!empty($widget->fields->thumb)) {
        return $widget->fields->thumb;
    } elseif (!empty($content) && preg_match_all($pattern, $content, $thumbUrl) && strlen($thumbUrl[1][0]) > 7) {
        return $thumbUrl[1][0];
    } else {
        return $random;
    }
}

// 主页文章缩略图
function GetRandomThumbnail($widget)
{
    $random = Helper::options()->themeUrl . '/img/DefualtThumbnail.jpg';
    if (Helper::options()->futureRandom) {
        $moszu = explode("\r\n", Helper::options()->futureRandom);
        $random = $moszu[array_rand($moszu, 1)] . "?futureRandom=" . mt_rand(0, 1000000);
    }
    $pattern = '/\<img.*?src\=\"(.*?)\"[^>]*>/i';
    $patternMD = '/\!\[.*?\]\((http(s)?:\/\/.*?(jpg|jpeg|gif|png|webp))/i';
    $patternMDfoot = '/\[.*?\]:\s*(http(s)?:\/\/.*?(jpg|jpeg|gif|png|webp))/i';
    $content = isset($widget->content) ? $widget->content : '';
    $t = preg_match_all($pattern, $content, $thumbUrl);
    $img = $random;
    if (!empty($widget->fields->thumb)) {
        $img = $widget->fields->thumb;
    } elseif ($t) {
        $img = $thumbUrl[1][0];
    } elseif (preg_match_all($patternMD, $content, $thumbUrl)) {
        $img = $thumbUrl[1][0];
    } elseif (preg_match_all($patternMDfoot, $content, $thumbUrl)) {
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
    } elseif ($chars >= 1000) {
        $chars /= 1000;
        $unit = 'K';
    }
    $out = sprintf('%.2lf %s', $chars, $unit);
    echo $out;
}

function thumb($cid)
{
    if (empty($imgurl)) {
        $rand_num = 10;
        if ($rand_num == 0) {
            $imgurl = "img/0.jpg";
        } else {
            $imgurl = "img/" . rand(1, $rand_num) . ".jpg";
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
            if (($count = floor($since / $seconds)) != 0) {
                break;
            }
        }
        $output = $count . $name . '前';
    } else {
        $output = !$comment_date ? date('Y-m-j G:i', $older_date) : date('Y-m-j', $older_date);
    }
    return $output;
}

// 文章内获取第一张图做封面
function getPostImg($archive)
{
    $img = array();
    preg_match_all("/<img.*?src=\"(.*?)\".*?\/?>/i", $archive->content, $img);
    if (count($img) > 0 && count($img[0]) > 0) {
        return $img[1][0];
    } else {
        return 'none';
    }
}

function createCatalog($obj)
{
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
{
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
