<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('common/header_com.php'); ?>

<body style="zoom: 1;">
    <div id="web_bg"></div>
    <div class="page" id="body-wrap">
        <?php if (is_array($this->options->beautifyBlock) && in_array('ShowTopimg',$this->options->beautifyBlock)): ?>
        <header class="full_page" id="page-header" style="background-image: url(<?php $this->options->headerimg() ?>)">
            <div id="site-info">
                <h1 id="site-title">
                    <?php $this->options->author_site_description() ?>
                </h1>
                <div id="site-subtitle">
                    <span id="subtitle"></span>
                </div>
            </div>
            <div id="scroll-down"><i class="fas fa-angle-down scroll-down-effects"></i></div>
            <?php else: ?>
            <header class="not-top-img" id="page-header">
                <?php endif; ?>
                <?php  $this->need('public/nav.php'); ?>
            </header>
            <div id="local-search">
                <div class="search-dialog" style="--search-height: 800px;">
                    <nav class="search-nav"><span class="search-dialog-title">搜寻</span><span
                            id="loading-status"></span><button class="search-close-button"><i
                                class="fas fa-times"></i></button>
                    </nav>
                    <div class="is-center" id="loading-database"><i class="fas fa-spinner fa-pulse"></i> <span>正在加载数据库</span></div>
                    <div class="search-wrap" style="display: block;">
                        <div id="local-search-input">
                            <div class="local-search-box"><input class="local-search-box--input" placeholder="搜寻文章"
                                    type="text"></div>
                        </div>
                        <hr>
                        <div id="local-search-results"></div>
                        <div id="local-search-stats-wrap"></div>
                    </div>
                </div>
                <div id="search-mask" style=""></div>
                <script src=" <?php $this->options->themeUrl('/js/local-search.js'); ?>"></script>

            </div>