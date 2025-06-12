<?php
function markdownToHtml($markdownText) {

    // 创建 Markdown 实例
    $markdown = new Markdown();

    // 将 Markdown 转换为 HTML
    $html = $markdown->convert($markdownText);
    $html = ParseCode($html);
    return $html;
}
function searchPost() {
    $request = Typecho_Request::getInstance();

    // 获取并过滤查询关键词
    $keywords = $request->get('keywords');
    $keywords = strip_tags(trim($keywords));

    // 数据库查询
    $db = Typecho_Db::get();
    $select = $db->select()->from('table.contents')
        ->where('status = ?', 'publish')
        ->where('type = ?', 'post')
        ->where('title LIKE ? OR text LIKE ?', '%' . $keywords . '%', '%' . $keywords . '%')
        ->limit(10);

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
        // Use CDATA for content to preserve HTML
        $content = $entry->addChild('content');
        $content->addAttribute('type', 'html');
        $contentNode = dom_import_simplexml($content);
        $contentOwner = $contentNode->ownerDocument;
        $contentNode->appendChild($contentOwner->createCDATASection($htmlContent));
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