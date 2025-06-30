<?php
/**
 * 这是 Typecho 版本的 butterfly 主题
 * 主题为移植至Typecho，你可以替换原butterfly主题的index.css文件
 * 主题可可以部分内容可以直接使用原butterfly主题的配置文件，为了解析yaml文件需要安装php-yaml扩展
 * @package Typecho-Butterfly
 * @version  1.0.1
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/** 文章置顶 */
$sticky = $this->options->sticky_cids;
if($sticky && $this->is('index') || $this->is('front')){
    $sticky_cids = explode(',', strtr($sticky, ' ', ','));//分割文本 
    $sticky_html = "<span class='article-meta'><i class='fas fa-thumbtack article-meta__icon sticky'></i><span class='sticky'>置顶 </span><span class='article-meta__separator'>|</span></span>";
    $db = Typecho_Db::get();
    $select1 = $this->select()->where('type = ?', 'post');
    $select2 = $this->select()->where('type = ? AND status = ? AND created < ?', 'post','publish',time());
    $this->row = [];
    $this->stack = [];
    $this->length = 0;
    $order = '';
    foreach($sticky_cids as $i => $cid) {
        if($i == 0) $select1->where('cid = ?', $cid);
        else $select1->orWhere('cid = ?', $cid);
        $order .= " when $cid then $i";
        $select2->where('table.contents.cid != ?', $cid);
    }
    if ($order) $select1->order('',"(case cid$order end)");
    if ($this->_currentPage == 1) foreach($db->fetchAll($select1) as $sticky_post){
        $sticky_post['sticky'] = $sticky_html;
        $this->push($sticky_post);
    }
    $uid = $this->user->uid; //登录时，显示用户各自的私密文章
    if($uid) $select2->orWhere('authorId = ? AND status = ?',$uid,'private');
    $sticky_posts = $db->fetchAll($select2->order('table.contents.created', Typecho_Db::SORT_DESC)->page($this->_currentPage, $this->parameter->pageSize));
    foreach($sticky_posts as $sticky_post) $this->push($sticky_post); //压入列队
    $this->setTotal($this->getTotal()-count($sticky_cids)); //置顶文章不计算在所有文章内
}
?>
<?php  $this->need('header.php'); ?>
<main class="layout" id="content-inner">
<div class="recent-posts nc" id="recent-posts">
<?php 
if($this->options->googleadsense != ""):
$i=1;
if($this->options->pageSize<=5)
{
    $k=$m=$g=3;
}else if($this->options->pageSize==10)
{
    $k=rand(3,4);
    $m=rand(6,8);
    $g=rand(10,12);
}else if($this->options->pageSize>5&&$this->options->pageSize<10){
    $k=$m=$g=4;
}
endif;
$coverIndex = 1; 
while($this->next()): 
    if($this->options->googleadsense != ""):
    if($i==$k || $i==$m || $i==$g){
?>
 <div class="recent-post-item ads-wrap">
        <ins class="adsbygoogle"
             style="display:block;height:200px;width:100%;"
             data-ad-format="fluid"
             data-ad-client="<?php $this->options->googleadsense(); ?>"></ins>
        <script>
             (adsbygoogle = window.adsbygoogle || []).push({});
        </script>
  </div>
<?php 
$i++;
}
$i++;
endif;

if($this->options->coverPosition === 'cross'){
    $sideClass = ($coverIndex % 2 == 0) ? 'right' : 'left';
}else{
    $sideClass  = $this->options->coverPosition;
}
?>
    <div class="recent-post-item">
    <?php if(noCover($this)): ?>  
        <div class="post_cover  <?php echo $sideClass; ?>">
             <a href="<?php $this->permalink() ?>">
                <img class="post-bg" data-lazy-src="<?php echo get_ArticleThumbnail($this);?>" src="<?php echo GetLazyLoad() ?>" onerror="this.onerror=null;this.src='<?php $this->options->themeUrl('img/404.jpg'); ?>'"></a>
        </div>
    <?php endif ?>
    <div class="recent-post-info<?php echo noCover($this) ? '' : ' no-cover'; ?>">
        <a  class="article-title" href="<?php $this->permalink() ?>"><?php $this->title() ?></a>
        <div class="article-meta-wrap">
        <?php $this->sticky(); ?>
            <span class="post-meta-date">
                <i class="far fa-calendar-alt"></i>
                <span class="article-meta-label">发表于</span>
                <span datetime="<?php $this->date('Y-m-d'); ?>" style="display: inline;" pubdate><?php $this->date('Y-m-d'); ?></span>
            </span>
            <span class="post-meta-date">
                <span class="article-meta-separator">|</span>
                <i class="fas fa-history"></i>
                <span class="article-meta-label">更新于</span>
                <span datetime="<?php echo date('Y-m-d', $this->modified); ?>"  style="display: inline;"><?php echo date('Y-m-d', $this->modified); ?></span>
            </span>
            <span class="article-meta">
                <span class="article-meta-separator">|</span>
                <i class="fas fa-inbox"></i>
                <?php $this->category(' '); ?>
            </span>
            <span class="article-meta">
                <span class="article-meta-separator">|</span>
                <i class="fa-solid fa-pen-nib"></i>
                <?php _e('作者: '); ?><a itemprop="name" href="<?php $this->author->permalink(); ?>" rel="author"><?php $this->author(); ?></a>
            </span>
            <span class="article-meta">
                <span class="article-meta-separator">|</span>
                <i class="fas fa-comments"></i>
                <a class="twikoo-count" href="<?php $this->permalink() ?>#comments"><?php $this->commentsNum('0条评论', '1 条评论', '%d 条评论'); ?></a>
            </span>
            <span class="article-meta">
                <span class="article-meta-separator">|</span>
                <i class="far fa-eye fa-fw post-meta-icon"></i>
                <span class="post-meta-label">阅读量:<?php only_get_post_view($this) ?></span>
            </span>
        </div>
        <div class="content">
            <?php summaryContent($this);
            echo '<br><a href="',$this->permalink(),'" title="',$this->title(),'">阅读全文...</a>';
                ?>
            </div>
    </div>
</div>
<?php 
 if (noCover($this)) {
    $coverIndex++;
}
 endwhile; ?>
 <nav id="pagination">
    <?php
    // 获取 pageNav 渲染后的内容
    ob_start(); // 开始输出缓冲
    $this->pageNav(
        '<i class="fas fa-chevron-left fa-fw"></i>', // 上一页图标
        '<i class="fas fa-chevron-right fa-fw"></i>', // 下一页图标
        1, // 分割范围，即显示当前页的前后页数
        '...',  // 使用简单的三个点作为分割字符
        array(
            'wrapTag' => 'div',  // 包裹整个分页的标签
            'wrapClass' => 'pagination',  // 包裹元素的类名
            'itemTag' => '', 
            'splitWord' => '...',  // 分割字符设为 '...'
            'prevClass' => 'extend prev',  // 上一页按钮的类名
            'nextClass' => 'extend next',  // 下一页按钮的类名
            'currentClass' => 'page-number current',  // 当前页的类名
            'linkFormat' => '<a href="{url}#content-inner" data-pjax-state="">{text}</a>',  // 普通分页链接格式
            'currentFormat' => '<span class="page-number current">{text}</span>'  // 当前页的格式，使用 <span> 包裹页码
        )
    );
    $pagination_html = ob_get_clean(); // 获取缓冲内容并结束缓冲

    // 优化后的正则替换
    // 替换当前页、普通页码链接和分隔符
    $pagination_html = preg_replace_callback(
        '/<a href="([^"]+)"(?: class="page-number current")?>(\d+)<\/a>|<span>\.\.\.<\/span>|<a href="([^"]+)" class="extend (prev|next)"[^>]*>(.*?)<\/a>/',
        function ($matches) {
            // 处理当前页和普通链接
            if (!empty($matches[2])) {
                return strpos($matches[0], 'class="page-number current"') !== false
                    ? '<span class="page-number current">' . $matches[2] . '</span>' // 当前页
                    : '<a href="' . $matches[1] . '#content-inner" data-pjax-state="">' . $matches[2] . '</a>'; // 普通页
            }
            // 处理分隔符
            if (!empty($matches[0]) && strpos($matches[0], '...') !== false) {
                return '<span class="space">...</span>'; // 分隔符
            }
            // 处理上一页和下一页链接
            if (!empty($matches[3])) {
                return '<a href="' . $matches[3] . '#content-inner" class="extend ' . $matches[4] . '">' . $matches[5] . '</a>';
            }
        },
        $pagination_html
    );

    // 输出修改后的 HTML
    echo $pagination_html;
    ?>
</nav>

</div>
<?php $this->need('sidebar.php'); ?>
</main>
<?php $this->need('footer.php'); ?>
<script>
function ver() {console.log(`
===================================================================
                                                                   
    #####  #    # ##### ##### ###### #####  ###### #      #   #    
    #    # #    #   #     #   #      #    # #      #       # #     
    #####  #    #   #     #   #####  #    # #####  #        #      
    #    # #    #   #     #   #      #####  #      #        #      
    #    # #    #   #     #   #      #   #  #      #        #     
    #####   ####    #     #   ###### #    # #      ######   #  
    
                           <?php echo getThemeVersion().PHP_EOL?>
===================================================================
`);}
</script>