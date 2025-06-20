<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<nav id="nav" class="show">
    <span id="blog-info">
        <a href="<?php $this->options->siteUrl(); ?>">
            <?php if(!empty($this->options->SiteLogo)) : ?>
            <img src="<?php $this->options->SiteLogo() ?>" width="95px" />
            <?php else :?>
            <span class="site-name">
                <?php $this->options->title() ?>
            </span>
            <?php endif ?>
        </a>
    </span>
    <div id="menus">
        <div id="search-button">
            <a class="site-page social-icon search">
                <i class="fas fa-search fa-fw"></i>
                <span> 搜索</span>
            </a>
        </div>
        <div id="toggle-menu"><a class="site-page"><i class="fas fa-bars fa-fw"></i></a></div>
        <?php renderMenu($this->options->menu) ?>
    </div>
</nav>