<?php
if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

function getMomentsTableName()
{
    $db = Typecho_Db::get();
    return $db->getPrefix() . 'bf_moments';
}

function getMomentLikesTableName()
{
    $db = Typecho_Db::get();
    return $db->getPrefix() . 'bf_moment_likes';
}

function getMomentCommentsTableName()
{
    $db = Typecho_Db::get();
    return $db->getPrefix() . 'bf_moment_comments';
}

function ensureMomentsSchema()
{
    $db = Typecho_Db::get();
    $momentsTable = getMomentsTableName();
    $likesTable = getMomentLikesTableName();
    $commentsTable = getMomentCommentsTableName();

    $db->query("
        CREATE TABLE IF NOT EXISTS `{$momentsTable}` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `author_id` int unsigned NOT NULL,
            `content` text NOT NULL,
            `images` text NULL,
            `status` varchar(16) NOT NULL DEFAULT 'publish',
            `like_count` int unsigned NOT NULL DEFAULT 0,
            `comment_count` int unsigned NOT NULL DEFAULT 0,
            `created` int unsigned NOT NULL,
            `modified` int unsigned NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_author_id` (`author_id`),
            KEY `idx_status_created` (`status`, `created`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $db->query("
        CREATE TABLE IF NOT EXISTS `{$likesTable}` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `moment_id` int unsigned NOT NULL,
            `user_id` int unsigned NULL,
            `ip` varchar(64) NULL,
            `created` int unsigned NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_moment_user` (`moment_id`, `user_id`),
            KEY `idx_moment_id` (`moment_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $db->query("
        CREATE TABLE IF NOT EXISTS `{$commentsTable}` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `moment_id` int unsigned NOT NULL,
            `author_id` int unsigned NULL,
            `author_name` varchar(255) NOT NULL,
            `author_mail` varchar(255) NULL,
            `content` text NOT NULL,
            `status` varchar(16) NOT NULL DEFAULT 'approved',
            `created` int unsigned NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_moment_status` (`moment_id`, `status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
}

function getMomentPageUrl()
{
    $url = trim((string) Helper::options()->momentsPageUrl);
    if ($url !== '') {
        return $url;
    }

    return rtrim(Helper::options()->siteUrl, '/') . '/moments.html';
}

function getMomentPageSize()
{
    $size = (int) Helper::options()->momentsPageSize;
    if ($size < 1) {
        $size = 10;
    }

    return min($size, 30);
}

function getMomentLikeActionUrl()
{
    return Typecho_Common::url('/action/moments-like', Helper::options()->index);
}

function getMomentCommentActionUrl()
{
    return Typecho_Common::url('/action/moments-comment', Helper::options()->index);
}

function hasMomentLiked($momentId, $userId = 0, $ip = '')
{
    $momentId = (int) $momentId;
    if ($momentId < 1) {
        return false;
    }

    $db = Typecho_Db::get();
    $likesTable = getMomentLikesTableName();
    $select = $db->select('id')->from($likesTable)->where('moment_id = ?', $momentId)->limit(1);

    if ($userId > 0) {
        $select->where('user_id = ?', (int) $userId);
    } else {
        $ip = trim((string) $ip);
        if ($ip === '') {
            return false;
        }
        $select->where('ip = ?', $ip);
    }

    return (bool) $db->fetchRow($select);
}

function fetchMomentCommentsMap(array $momentIds, $limitPerMoment = 20)
{
    $momentIds = array_values(array_unique(array_filter(array_map('intval', $momentIds))));
    if (empty($momentIds)) {
        return [];
    }

    $db = Typecho_Db::get();
    $commentsTable = getMomentCommentsTableName();
    $rows = $db->fetchAll(
        $db->select(
            "{$commentsTable}.id",
            "{$commentsTable}.moment_id",
            "{$commentsTable}.author_id",
            "{$commentsTable}.author_name",
            "{$commentsTable}.author_mail",
            "{$commentsTable}.content",
            "{$commentsTable}.created"
        )
            ->from($commentsTable)
            ->where("{$commentsTable}.moment_id IN ?", $momentIds)
            ->where("{$commentsTable}.status = ?", 'approved')
            ->order("{$commentsTable}.created", Typecho_Db::SORT_ASC)
    );

    $result = [];
    foreach ($rows as $row) {
        $momentId = (int) $row['moment_id'];
        if (!isset($result[$momentId])) {
            $result[$momentId] = [];
        }

        if (count($result[$momentId]) >= (int) $limitPerMoment) {
            continue;
        }

        $result[$momentId][] = $row;
    }

    return $result;
}

function createMomentComment($momentId, $authorId, $authorName, $authorMail, $content)
{
    $momentId = (int) $momentId;
    $authorId = (int) $authorId;
    $authorName = trim((string) $authorName);
    $authorMail = trim((string) $authorMail);
    $content = trim((string) $content);

    if ($momentId < 1 || $authorName === '' || $content === '') {
        return null;
    }

    $db = Typecho_Db::get();
    $momentsTable = getMomentsTableName();
    $commentsTable = getMomentCommentsTableName();
    $moment = $db->fetchRow(
        $db->select('id')->from($momentsTable)->where('id = ?', $momentId)->where('status = ?', 'publish')->limit(1)
    );

    if (empty($moment)) {
        return null;
    }

    $now = time();
    $commentId = (int) $db->query(
        $db->insert($commentsTable)->rows([
            'moment_id' => $momentId,
            'author_id' => $authorId > 0 ? $authorId : null,
            'author_name' => $authorName,
            'author_mail' => $authorMail !== '' ? $authorMail : null,
            'content' => $content,
            'status' => 'approved',
            'created' => $now,
        ])
    );
    $commentCount = (int) $db->fetchObject(
        $db->select(['COUNT(id)' => 'num'])->from($commentsTable)->where('moment_id = ?', $momentId)->where('status = ?', 'approved')
    )->num;

    $db->query(
        $db->update($momentsTable)->rows([
            'comment_count' => $commentCount,
            'modified' => $now,
        ])->where('id = ?', $momentId)
    );

    return $db->fetchRow(
        $db->select(
            "{$commentsTable}.id",
            "{$commentsTable}.moment_id",
            "{$commentsTable}.author_id",
            "{$commentsTable}.author_name",
            "{$commentsTable}.author_mail",
            "{$commentsTable}.content",
            "{$commentsTable}.created"
        )
            ->from($commentsTable)
            ->where("{$commentsTable}.id = ?", $commentId)
            ->limit(1)
    );
}

function fetchMomentsList($limit = null)
{
    $db = Typecho_Db::get();
    $pageSize = $limit ?: getMomentPageSize();
    $momentsTable = getMomentsTableName();

    $select = $db->select(
        "{$momentsTable}.id",
        "{$momentsTable}.author_id",
        "{$momentsTable}.content",
        "{$momentsTable}.images",
        "{$momentsTable}.status",
        "{$momentsTable}.like_count",
        "{$momentsTable}.comment_count",
        "{$momentsTable}.created",
        "{$momentsTable}.modified",
        'table.users.screenName',
        'table.users.mail'
    )
        ->from($momentsTable)
        ->join('table.users', "table.users.uid = {$momentsTable}.author_id")
        ->where("{$momentsTable}.status = ?", 'publish')
        ->order("{$momentsTable}.created", Typecho_Db::SORT_DESC)
        ->limit($pageSize);

    return $db->fetchAll($select);
}

function createMoment($authorId, $content, array $images = [])
{
    $content = trim((string) $content);
    $images = array_values(array_filter(array_map('trim', $images)));

    if ($authorId < 1 || $content === '') {
        return false;
    }

    $db = Typecho_Db::get();
    $momentsTable = getMomentsTableName();
    $now = time();

    return $db->query(
        $db->insert($momentsTable)->rows([
            'author_id' => (int) $authorId,
            'content' => $content,
            'images' => !empty($images) ? json_encode($images, JSON_UNESCAPED_UNICODE) : null,
            'status' => 'publish',
            'like_count' => 0,
            'comment_count' => 0,
            'created' => $now,
            'modified' => $now,
        ])
    );
}

function getMomentById($id)
{
    $id = (int) $id;
    if ($id < 1) {
        return null;
    }

    $db = Typecho_Db::get();
    $momentsTable = getMomentsTableName();

    return $db->fetchRow(
        $db->select(
            "{$momentsTable}.id",
            "{$momentsTable}.author_id",
            "{$momentsTable}.content",
            "{$momentsTable}.images",
            "{$momentsTable}.status",
            "{$momentsTable}.like_count",
            "{$momentsTable}.comment_count",
            "{$momentsTable}.created",
            "{$momentsTable}.modified",
            'table.users.screenName',
            'table.users.mail'
        )
            ->from($momentsTable)
            ->join('table.users', "table.users.uid = {$momentsTable}.author_id")
            ->where("{$momentsTable}.id = ?", $id)
            ->limit(1)
    );
}

function getMomentPermalink($moment)
{
    return getMomentPageUrl() . '#moment-' . (int) $moment['id'];
}

function getMomentPreviewText($text, $length = 220)
{
    $text = preg_replace('/<br\s*\/?>/i', "\n", $text);
    $text = preg_replace('/<\/p>/i', "\n", $text);
    $text = strip_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    $text = preg_replace("/\r\n|\r/u", "\n", $text);
    $text = preg_replace("/\n{3,}/u", "\n\n", $text);
    $text = trim($text);

    if (mb_strlen($text, 'UTF-8') > $length) {
        $text = Typecho_Common::subStr($text, 0, $length, '...');
    }

    return nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
}

function getMomentImages($source, $limit = 9)
{
    if (is_array($source)) {
        $images = [];
        foreach ($source as $item) {
            if (is_string($item)) {
                $url = trim($item);
            } elseif (is_array($item)) {
                $url = trim((string) ($item['url'] ?? ''));
            } else {
                $url = '';
            }

            if ($url !== '') {
                $images[] = $url;
            }
        }

        return array_slice(array_values(array_unique($images)), 0, $limit);
    }

    $source = trim((string) $source);
    if ($source === '') {
        return [];
    }

    if ($source[0] === '[') {
        $decoded = json_decode($source, true);
        if (is_array($decoded)) {
            return getMomentImages($decoded, $limit);
        }
    }

    preg_match_all('/<img[^>]+(?:data-lazy-src|src)=["\']([^"\']+)["\']/i', $source, $matches);
    if (empty($matches[1])) {
        return [];
    }

    $images = array_values(array_unique($matches[1]));
    return array_slice($images, 0, $limit);
}

function formatMomentTime($created)
{
    $created = (int) $created;
    $diff = time() - $created;

    if ($diff < 60) {
        return '刚刚';
    }

    if ($diff < 3600) {
        return floor($diff / 60) . ' 分钟前';
    }

    if ($diff < 86400) {
        return floor($diff / 3600) . ' 小时前';
    }

    if ($diff < 604800) {
        return floor($diff / 86400) . ' 天前';
    }

    return date('Y年m月d日 H:i', $created);
}
