<?php
/**
 * 本地搜索 API
 * 修复了以下问题：
 * 1. SQL LIKE 查询注入风险 - 对通配符进行转义
 * 2. XSS 风险 - 移除危险的 HTML 标签和属性
 */

/**
 * XSS 过滤：移除 script、style、iframe 等危险标签和事件属性
 */
function sanitizeSearchContent($html) {
    // 移除危险的标签
    $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
    $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);
    $html = preg_replace('/<iframe\b[^>]*>(.*?)<\/iframe>/is', '', $html);
    $html = preg_replace('/<object\b[^>]*>(.*?)<\/object>/is', '', $html);
    $html = preg_replace('/<embed\b[^>]*>/is', '', $html);

    // 移除事件属性
    $html = preg_replace('/\s+on\w+\s*=\s*["\'][^"\']*["\']/i', '', $html);
    $html = preg_replace('/\s+on\w+\s*=\s*[^\s>]+/i', '', $html);

    // 移除 javascript: 协议
    $html = preg_replace('/href\s*=\s*["\']javascript:[^"\']*["\']/i', '', $html);

    return $html;
}

function markdownToHtml($markdownText) {
    // 创建 Markdown 实例
    $markdown = new Markdown();

    // 将 Markdown 转换为 HTML
    $html = $markdown->convert($markdownText);
    $html = ParseCode($html);

    // 清理 HTML，防止 XSS
    $html = sanitizeSearchContent($html);

    return $html;
}

function escapeLikeQuery($str) {
    // SQL LIKE 查询中 % 和 _ 是通配符，需要转义
    return str_replace(['%', '_'], ['\\%', '\\_'], $str);
}

function searchPost() {
    $request = Typecho_Request::getInstance();

    // 获取并过滤查询关键词
    $keywords = $request->get('keywords');

    // 如果没有关键词，返回空结果
    if(empty($keywords)) {
        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?><search></search>';
        exit;
    }

    // 清理关键词
    $keywords = strip_tags(trim($keywords));

    // 转义 LIKE 查询的通配符，防止 SQL 注入
    $escapedKeywords = escapeLikeQuery($keywords);

    // 数据库查询
    $db = Typecho_Db::get();
    $select = $db->select()->from('table.contents')
        ->where('status = ?', 'publish')
        ->where('type = ?', 'post')
        ->where('title LIKE ? OR text LIKE ?', '%' . $escapedKeywords . '%', '%' . $escapedKeywords . '%')
        ->limit(20);

    $results = $db->fetchAll($select);

    // 设置响应头为 XML
    header('Content-Type: application/xml; charset=utf-8');

    // 构建 XML 结果
    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><search></search>');

    foreach ($results as $result) {
        $entry = $xml->addChild('entry');
        $entry->addChild('title', htmlspecialchars($result['title'], ENT_QUOTES, 'UTF-8'));

        // Add link as an attribute to the link element
        $link = $entry->addChild('link');
        $link->addAttribute('href', Typecho_Common::url($result['slug'], Helper::options()->index));

        $entry->addChild('url', htmlspecialchars(Typecho_Router::url('post', $result), ENT_QUOTES, 'UTF-8'));

        // 使用 Typecho 自带的 Markdown 转换功能
        $htmlContent = markdownToHtml($result['text']);
        $htmlContent = preg_replace('/<!--.*?-->/s', '', $htmlContent);

        // 截取摘要内容（去除 HTML 标签后取前200字符）
        $plainContent = strip_tags($htmlContent);
        $plainContent = preg_replace('/\s+/', ' ', $plainContent);
        $plainContent = mb_substr($plainContent, 0, 200, 'UTF-8');

        // Use CDATA for content
        $content = $entry->addChild('content');
        $content->addAttribute('type', 'html');
        $contentNode = dom_import_simplexml($content);
        $contentOwner = $contentNode->ownerDocument;
        $contentNode->appendChild($contentOwner->createCDATASection($plainContent));
    }

    // 输出 XML
    echo $xml->asXML();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if(isset($_GET['action'])&& $_GET['action'] == 'search-post'){
        searchPost();
    }
}
?>