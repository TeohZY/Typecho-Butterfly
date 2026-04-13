<?php
if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 获取标签数目
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

// 调用博主最近文章更新时间
function get_last_update()
{
    $num = '1';
    $type = 'post';
    $status = 'publish';
    $now = time();
    $db = Typecho_Db::get();
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

// 三合一避免重复查询
function get_post_details($archive)
{
    $db = Typecho_Db::get();
    $cid = $archive->cid;

    $row = $db->fetchRow($db->select('text', 'views')->from('table.contents')->where('cid = ?', $cid)->limit(1));
    $text = $row['text'];
    $views = (int) $row['views'];
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

    $query = $db->select(
        ['SUM(LENGTH(text))' => 'totalChars', 'SUM(views)' => 'totalViews', 'MAX(created)' => 'latestCreate', 'MAX(modified)' => 'latestModify']
    )->from('table.contents')->where('table.contents.status = ?', 'publish')->where('table.contents.type = ?', 'post');

    $result = $db->fetchRow($query);

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

    $totalViews = $result['totalViews'];

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

    return [
        'charCount' => $charCount,
        'totalViews' => $totalViews,
        'lastUpdate' => $lastUpdate,
        'publishedPostsNum' => $stat->publishedPostsNum,
        'categoriesNum' => $stat->categoriesNum,
        'tagsNum' => tagsNum(false),
    ];
}
