<?php

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

function butterflyMomentsTableName()
{
    return Typecho_Db::get()->getPrefix() . 'bf_moments';
}

function butterflyMomentsEnsureSchema()
{
    $db = Typecho_Db::get();
    $momentsTable = butterflyMomentsTableName();

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
}

function butterflyMomentsPanelUrl($panel, array $query = [])
{
    $path = 'extending.php?panel=ButterflyMoments/' . ltrim($panel, '/');
    if (!empty($query)) {
        $path .= '&' . http_build_query($query);
    }

    return Helper::options()->adminUrl($path, true);
}

function butterflyMomentsFetchList($limit = 50)
{
    $db = Typecho_Db::get();
    $table = butterflyMomentsTableName();

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
