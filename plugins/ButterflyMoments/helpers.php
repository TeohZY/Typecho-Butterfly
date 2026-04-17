<?php

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

function butterflyMomentsTableName()
{
    return Typecho_Db::get()->getPrefix() . 'bf_moments';
}

function butterflyMomentsLikesTableName()
{
    return Typecho_Db::get()->getPrefix() . 'bf_moment_likes';
}

function butterflyMomentsCommentsTableName()
{
    return Typecho_Db::get()->getPrefix() . 'bf_moment_comments';
}

function butterflyMomentsEnsureSchema()
{
    $db = Typecho_Db::get();
    $momentsTable = butterflyMomentsTableName();
    $likesTable = butterflyMomentsLikesTableName();
    $commentsTable = butterflyMomentsCommentsTableName();

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
            KEY `idx_moment_ip` (`moment_id`, `ip`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $db->query("
        CREATE TABLE IF NOT EXISTS `{$commentsTable}` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `moment_id` int unsigned NOT NULL,
            `author_id` int unsigned NULL,
            `author_name` varchar(255) NOT NULL,
            `author_mail` varchar(255) NULL,
            `ip` varchar(64) NULL,
            `content` text NOT NULL,
            `status` varchar(16) NOT NULL DEFAULT 'approved',
            `created` int unsigned NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_moment_status` (`moment_id`, `status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    try {
        $db->query("ALTER TABLE `{$commentsTable}` ADD COLUMN `ip` varchar(64) NULL AFTER `author_mail`");
    } catch (Throwable $e) {
    }
}

function butterflyMomentsPanelUrl($panel, array $query = [])
{
    $path = 'extending.php?panel=ButterflyMoments/' . ltrim($panel, '/');
    if (!empty($query)) {
        $path .= '&' . http_build_query($query);
    }

    return Helper::options()->adminUrl($path, true);
}

function butterflyMomentsFetchList($limit = 50, $page = 1)
{
    $db = Typecho_Db::get();
    $table = butterflyMomentsTableName();
    $page = max(1, (int) $page);
    $offset = ($page - 1) * (int) $limit;

    return $db->fetchAll(
        $db->select(
            "{$table}.id",
            "{$table}.author_id",
            "{$table}.content",
            "{$table}.images",
            "{$table}.status",
            "{$table}.like_count",
            "{$table}.comment_count",
            "{$table}.created",
            "{$table}.modified",
            'table.users.screenName'
        )
            ->from($table)
            ->join('table.users', "table.users.uid = {$table}.author_id")
            ->order("{$table}.created", Typecho_Db::SORT_DESC)
            ->limit((int) $limit)
            ->offset($offset)
    );
}

function butterflyMomentsFetchComments($limit = 100, $status = 'all', $momentId = 0, $page = 1)
{
    $db = Typecho_Db::get();
    $commentsTable = butterflyMomentsCommentsTableName();
    $momentsTable = butterflyMomentsTableName();
    $momentId = (int) $momentId;
    $page = max(1, (int) $page);
    $offset = ($page - 1) * (int) $limit;
    $select = $db->select(
        "{$commentsTable}.id",
        "{$commentsTable}.moment_id",
        "{$commentsTable}.author_id",
        "{$commentsTable}.author_name",
        "{$commentsTable}.author_mail",
        "{$commentsTable}.content",
        "{$commentsTable}.status",
        "{$commentsTable}.created",
        "{$momentsTable}.content AS moment_content",
        "{$momentsTable}.status AS moment_status",
        'table.users.screenName'
    )
        ->from($commentsTable)
        ->join($momentsTable, "{$momentsTable}.id = {$commentsTable}.moment_id", Typecho_Db::LEFT_JOIN)
        ->join('table.users', "table.users.uid = {$momentsTable}.author_id", Typecho_Db::LEFT_JOIN)
        ->order("{$commentsTable}.created", Typecho_Db::SORT_DESC)
        ->limit((int) $limit)
        ->offset($offset);

    if (in_array($status, ['approved', 'pending'], true)) {
        $select->where("{$commentsTable}.status = ?", $status);
    }

    if ($momentId > 0) {
        $select->where("{$commentsTable}.moment_id = ?", $momentId);
    }

    return $db->fetchAll($select);
}

function butterflyMomentsFetchLikes($limit = 120, $momentId = 0, $page = 1)
{
    $db = Typecho_Db::get();
    $likesTable = butterflyMomentsLikesTableName();
    $momentsTable = butterflyMomentsTableName();
    $momentId = (int) $momentId;
    $page = max(1, (int) $page);
    $offset = ($page - 1) * (int) $limit;
    $select = $db->select(
        "{$likesTable}.id",
        "{$likesTable}.moment_id",
        "{$likesTable}.user_id",
        "{$likesTable}.ip",
        "{$likesTable}.created",
        "{$momentsTable}.content AS moment_content",
        "{$momentsTable}.status AS moment_status",
        'table.users.screenName'
    )
        ->from($likesTable)
        ->join($momentsTable, "{$momentsTable}.id = {$likesTable}.moment_id", Typecho_Db::LEFT_JOIN)
        ->join('table.users', "table.users.uid = {$likesTable}.user_id", Typecho_Db::LEFT_JOIN)
        ->order("{$likesTable}.created", Typecho_Db::SORT_DESC)
        ->limit((int) $limit)
        ->offset($offset);

    if ($momentId > 0) {
        $select->where("{$likesTable}.moment_id = ?", $momentId);
    }

    return $db->fetchAll($select);
}

function butterflyMomentsFetchStats()
{
    $db = Typecho_Db::get();
    $momentsTable = butterflyMomentsTableName();
    $likesTable = butterflyMomentsLikesTableName();
    $commentsTable = butterflyMomentsCommentsTableName();

    return [
        'moments' => (int) $db->fetchObject($db->select(['COUNT(id)' => 'num'])->from($momentsTable))->num,
        'published_moments' => (int) $db->fetchObject($db->select(['COUNT(id)' => 'num'])->from($momentsTable)->where('status = ?', 'publish'))->num,
        'likes' => (int) $db->fetchObject($db->select(['COUNT(id)' => 'num'])->from($likesTable))->num,
        'comments' => (int) $db->fetchObject($db->select(['COUNT(id)' => 'num'])->from($commentsTable))->num,
        'approved_comments' => (int) $db->fetchObject($db->select(['COUNT(id)' => 'num'])->from($commentsTable)->where('status = ?', 'approved'))->num,
        'pending_comments' => (int) $db->fetchObject($db->select(['COUNT(id)' => 'num'])->from($commentsTable)->where('status = ?', 'pending'))->num,
    ];
}

function butterflyMomentsCountList()
{
    $db = Typecho_Db::get();
    $table = butterflyMomentsTableName();
    return (int) $db->fetchObject($db->select(['COUNT(id)' => 'num'])->from($table))->num;
}

function butterflyMomentsCountComments($status = 'all', $momentId = 0)
{
    $db = Typecho_Db::get();
    $commentsTable = butterflyMomentsCommentsTableName();
    $momentId = (int) $momentId;
    $select = $db->select(['COUNT(id)' => 'num'])->from($commentsTable);

    if (in_array($status, ['approved', 'pending'], true)) {
        $select->where('status = ?', $status);
    }

    if ($momentId > 0) {
        $select->where('moment_id = ?', $momentId);
    }

    return (int) $db->fetchObject($select)->num;
}

function butterflyMomentsCountLikes($momentId = 0)
{
    $db = Typecho_Db::get();
    $likesTable = butterflyMomentsLikesTableName();
    $momentId = (int) $momentId;
    $select = $db->select(['COUNT(id)' => 'num'])->from($likesTable);

    if ($momentId > 0) {
        $select->where('moment_id = ?', $momentId);
    }

    return (int) $db->fetchObject($select)->num;
}

function butterflyMomentsDeleteLike($id)
{
    $db = Typecho_Db::get();
    $likesTable = butterflyMomentsLikesTableName();
    $momentsTable = butterflyMomentsTableName();
    $id = (int) $id;
    if ($id < 1) {
        return false;
    }

    $like = $db->fetchRow(
        $db->select('id', 'moment_id')->from($likesTable)->where('id = ?', $id)->limit(1)
    );

    if (empty($like)) {
        return false;
    }

    $db->query($db->delete($likesTable)->where('id = ?', $id));
    $likeCount = (int) $db->fetchObject(
        $db->select(['COUNT(id)' => 'num'])->from($likesTable)->where('moment_id = ?', (int) $like['moment_id'])
    )->num;

    $db->query(
        $db->update($momentsTable)->rows([
            'like_count' => $likeCount,
            'modified' => time(),
        ])->where('id = ?', (int) $like['moment_id'])
    );

    return true;
}

function butterflyMomentsGetRecentGuestComment($momentId, $ip, $seconds = 30)
{
    $db = Typecho_Db::get();
    $commentsTable = butterflyMomentsCommentsTableName();
    $momentId = (int) $momentId;
    $ip = trim((string) $ip);
    if ($momentId < 1 || $ip === '') {
        return null;
    }

    return $db->fetchRow(
        $db->select('id', 'content', 'created')
            ->from($commentsTable)
            ->where('moment_id = ?', $momentId)
            ->where('author_id IS NULL')
            ->where('ip = ?', $ip)
            ->where('created >= ?', time() - max(1, (int) $seconds))
            ->order('created', Typecho_Db::SORT_DESC)
            ->limit(1)
    );
}

function butterflyMomentsFind($id)
{
    $db = Typecho_Db::get();
    $table = butterflyMomentsTableName();

    return $db->fetchRow(
        $db->select()->from($table)->where('id = ?', (int) $id)->limit(1)
    );
}

function butterflyMomentsCreate($authorId, $content, array $images = [], $status = 'publish')
{
    $db = Typecho_Db::get();
    $table = butterflyMomentsTableName();
    $now = time();

    return $db->query(
        $db->insert($table)->rows([
            'author_id' => (int) $authorId,
            'content' => trim((string) $content),
            'images' => !empty($images) ? json_encode(array_values($images), JSON_UNESCAPED_UNICODE) : null,
            'status' => $status === 'draft' ? 'draft' : 'publish',
            'like_count' => 0,
            'comment_count' => 0,
            'created' => $now,
            'modified' => $now,
        ])
    );
}

function butterflyMomentsUpdate($id, $content, array $images = [], $status = 'publish')
{
    $db = Typecho_Db::get();
    $table = butterflyMomentsTableName();

    return $db->query(
        $db->update($table)->rows([
            'content' => trim((string) $content),
            'images' => !empty($images) ? json_encode(array_values($images), JSON_UNESCAPED_UNICODE) : null,
            'status' => $status === 'draft' ? 'draft' : 'publish',
            'modified' => time(),
        ])->where('id = ?', (int) $id)
    );
}

function butterflyMomentsDelete($id)
{
    $db = Typecho_Db::get();
    $table = butterflyMomentsTableName();
    $likesTable = butterflyMomentsLikesTableName();
    $commentsTable = butterflyMomentsCommentsTableName();

    $db->query($db->delete($likesTable)->where('moment_id = ?', (int) $id));
    $db->query($db->delete($commentsTable)->where('moment_id = ?', (int) $id));

    return $db->query($db->delete($table)->where('id = ?', (int) $id));
}

function butterflyMomentsPreview($content, $length = 90)
{
    $content = strip_tags((string) $content);
    $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');
    $content = preg_replace("/\s+/u", ' ', $content);
    $content = trim($content);

    if (mb_strlen($content, 'UTF-8') > $length) {
        $content = Typecho_Common::subStr($content, 0, $length, '...');
    }

    return $content;
}

function butterflyMomentsImagesFromTextarea($text)
{
    $items = preg_split('/\r\n|\r|\n/', (string) $text);
    return array_values(array_filter(array_map('trim', (array) $items)));
}

function butterflyMomentsNormalizeImages($images)
{
    if (is_string($images)) {
        $decoded = json_decode($images, true);
        $images = is_array($decoded) ? $decoded : [];
    }

    if (!is_array($images)) {
        return [];
    }

    $normalized = [];
    foreach ($images as $image) {
        if (is_string($image)) {
            $url = trim($image);
            if ($url !== '') {
                $normalized[] = ['url' => $url, 'cid' => 0, 'title' => basename(parse_url($url, PHP_URL_PATH) ?: $url)];
            }
            continue;
        }

        if (!is_array($image)) {
            continue;
        }

        $url = trim((string) ($image['url'] ?? ''));
        if ($url === '') {
            continue;
        }

        $normalized[] = [
            'url' => $url,
            'cid' => (int) ($image['cid'] ?? 0),
            'title' => trim((string) ($image['title'] ?? basename(parse_url($url, PHP_URL_PATH) ?: $url))),
        ];
    }

    return $normalized;
}

function butterflyMomentsToggleLike($momentId, $userId = 0, $ip = '')
{
    $db = Typecho_Db::get();
    $momentsTable = butterflyMomentsTableName();
    $likesTable = butterflyMomentsLikesTableName();
    $momentId = (int) $momentId;
    $userId = (int) $userId;
    $ip = trim((string) $ip);

    if ($momentId < 1) {
        return null;
    }

    $moment = $db->fetchRow(
        $db->select('id', 'like_count')->from($momentsTable)->where('id = ?', $momentId)->limit(1)
    );

    if (empty($moment)) {
        return null;
    }

    if ($userId > 0) {
        $existing = $db->fetchRow(
            $db->select('id')->from($likesTable)->where('moment_id = ?', $momentId)->where('user_id = ?', $userId)->limit(1)
        );
    } else {
        if ($ip === '') {
            return null;
        }

        $existing = $db->fetchRow(
            $db->select('id')->from($likesTable)->where('moment_id = ?', $momentId)->where('ip = ?', $ip)->limit(1)
        );
    }

    $liked = false;
    if (!empty($existing)) {
        $db->query($db->delete($likesTable)->where('id = ?', (int) $existing['id']));
    } else {
        $db->query(
            $db->insert($likesTable)->rows([
                'moment_id' => $momentId,
                'user_id' => $userId > 0 ? $userId : null,
                'ip' => $userId > 0 ? null : $ip,
                'created' => time(),
            ])
        );
        $liked = true;
    }

    $likeCount = (int) $db->fetchObject(
        $db->select(['COUNT(id)' => 'num'])->from($likesTable)->where('moment_id = ?', $momentId)
    )->num;

    $db->query(
        $db->update($momentsTable)->rows(['like_count' => $likeCount, 'modified' => time()])->where('id = ?', $momentId)
    );

    return [
        'liked' => $liked,
        'like_count' => $likeCount,
    ];
}

function butterflyMomentsCreateComment($momentId, $authorId, $authorName, $authorMail, $content, $status = 'approved', $ip = '')
{
    $db = Typecho_Db::get();
    $momentsTable = butterflyMomentsTableName();
    $commentsTable = butterflyMomentsCommentsTableName();
    $momentId = (int) $momentId;
    $authorId = (int) $authorId;
    $authorName = trim((string) $authorName);
    $authorMail = trim((string) $authorMail);
    $content = trim((string) $content);
    $status = $status === 'pending' ? 'pending' : 'approved';
    $ip = trim((string) $ip);

    if ($momentId < 1 || $authorName === '' || $content === '') {
        return null;
    }

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
            'ip' => $ip !== '' ? $ip : null,
            'content' => $content,
            'status' => $status,
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

    return [
        'id' => $commentId,
        'moment_id' => $momentId,
        'author_id' => $authorId,
        'author_name' => $authorName,
        'author_mail' => $authorMail,
        'content' => $content,
        'created' => $now,
        'comment_count' => $commentCount,
        'status' => $status,
    ];
}

function butterflyMomentsSyncCommentCount($momentId)
{
    $db = Typecho_Db::get();
    $momentId = (int) $momentId;
    if ($momentId < 1) {
        return 0;
    }

    $momentsTable = butterflyMomentsTableName();
    $commentsTable = butterflyMomentsCommentsTableName();
    $commentCount = (int) $db->fetchObject(
        $db->select(['COUNT(id)' => 'num'])->from($commentsTable)->where('moment_id = ?', $momentId)->where('status = ?', 'approved')
    )->num;

    $db->query(
        $db->update($momentsTable)->rows([
            'comment_count' => $commentCount,
            'modified' => time(),
        ])->where('id = ?', $momentId)
    );

    return $commentCount;
}

function butterflyMomentsDeleteComment($id)
{
    $db = Typecho_Db::get();
    $commentsTable = butterflyMomentsCommentsTableName();
    $id = (int) $id;
    if ($id < 1) {
        return false;
    }

    $comment = $db->fetchRow(
        $db->select('id', 'moment_id')->from($commentsTable)->where('id = ?', $id)->limit(1)
    );

    if (empty($comment)) {
        return false;
    }

    $db->query($db->delete($commentsTable)->where('id = ?', $id));
    butterflyMomentsSyncCommentCount((int) $comment['moment_id']);

    return true;
}

function butterflyMomentsUpdateCommentStatus($id, $status)
{
    $db = Typecho_Db::get();
    $commentsTable = butterflyMomentsCommentsTableName();
    $id = (int) $id;
    $status = $status === 'approved' ? 'approved' : 'pending';
    if ($id < 1) {
        return false;
    }

    $comment = $db->fetchRow(
        $db->select('id', 'moment_id')->from($commentsTable)->where('id = ?', $id)->limit(1)
    );

    if (empty($comment)) {
        return false;
    }

    $db->query(
        $db->update($commentsTable)->rows(['status' => $status])->where('id = ?', $id)
    );
    butterflyMomentsSyncCommentCount((int) $comment['moment_id']);

    return true;
}
