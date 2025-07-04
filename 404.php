<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->page404(); ?>
<?php  $this->need('header_com.php'); ?>
<div id="web_bg"></div>
<div class="error404" id="body-wrap">
     <header class="not-top-img" id="page-header">
     <?php  $this->need('public/nav.php'); ?>
</header>
    <div id="error-wrap"><div class="error-content"><div class="error-img"><img src="https://i.loli.net/2020/05/19/aKOcLiyPl2JQdFD.png" alt="Page not found" class="entered"></div><div class="error-info"><h1 class="error_title">404</h1><div class="error_subtitle">頁面沒有找到</div></div></div></div></div>
    <?php require_once('public/rightside.php');?>
<?php if ($this->options->showFramework == 'off'): ?>
<style>.framework-info{display:none}</style>
<?php endif; ?>
<?php if ($this->options->CursorEffects !== 'off' &&$this->options->CursorEffects == 'heart') : ?>
<script id="click-heart" src="https://cdn.jsdelivr.net/npm/butterfly-extsrc@1/dist/click-heart.min.js" async="async" mobile="false"></script>
<?php elseif ($this->options->CursorEffects !== 'off' &&$this->options->CursorEffects == 'fireworks') : ?>
<canvas class="fireworks"></canvas>
<script id="fireworks" src="https://cdn.jsdelivr.net/npm/butterfly-extsrc@1.1.0/dist/fireworks.min.js" async="async" mobile="false"></script>
<?php endif; ?>
<?php if ($this->options->ShowLive2D !== 'off' && !isMobile()) : ?>
    <script src="https://cdn.jsdelivr.net/gh/stevenjoezhang/live2d-widget@latest/autoload.js"></script>
<?php endif; ?>
<script><?php $this->options->CustomScript() ?></script>
 <?php $this->options->CustomBodyEnd() ?>
<div class="js-pjax">
<?php if ($this->options->NewTabLink == 'on'): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  var aElements = document.getElementsByTagName('a');
  var domain = document.domain;
  for (var i = 0; i < aElements.length; i++) {
    var aElement = aElements[i];
    var url = aElement.href;
    if (url && url.length > 0 && url.indexOf(domain) === -1 && url !== 'javascript:void(0);') {
      aElement.setAttribute('target', '_blank');
    }
  }
});
</script>
<?php endif; ?>        
<?php if($this->is('index')): ?>
<!--打字-->
<?php if (is_array($this->options->beautifyBlock) && in_array('ShowTopimg',$this->options->beautifyBlock)): ?>
   <?php if(!empty($this->options->CustomSubtitle)): ?>
      <script>
 function subtitleType() {
if (true) {
var typed = new Typed("#subtitle", {
strings: "<?php $this->options->CustomSubtitle()?>".split(","),
startDelay: 300,
typeSpeed: 150,
loop: <?php $this->options->SubtitleLoop() ?>,
backSpeed: 50
})
}
}
"function"==typeof Typed?subtitleType():getScript("https://cdn.jsdelivr.net/npm/typed.js/lib/typed.min.js")
.then(subtitleType)
</script>
   <?php else: ?>
      <script>
function subtitleType(){
fetch("https://v1.hitokoto.cn").then(t=>t.json()).then(t=>{
o=0=="".length?new Array:" ".split(",");
o.unshift(t.hitokoto),
new Typed("#subtitle",{
    strings:o,
    startDelay:300,
    typeSpeed:150,
    loop: <?php $this->options->SubtitleLoop() ?>,
    backSpeed:50
      }
  )}
)}
"function"==typeof Typed?subtitleType():getScript("https://cdn.jsdelivr.net/npm/typed.js/lib/typed.min.js")
.then(subtitleType)
</script>
    <?php endif ?>
    <?php endif?>
<!--打字end-->
<!--判断主页end-->
<?php endif?>
</div>
<!--pjax-->
<?php if($this->options->EnablePjax === 'on') : ?>
<?php if($this->options->StaticFile == 'CDN' && $this->options->CDNURL == ''): ?>
<link rel="stylesheet" href="https://<?php $this->options->jsdelivrLink() ?>/gh/rstacruz/nprogress@master/nprogress.css">
<script src="https://<?php $this->options->jsdelivrLink() ?>/gh/rstacruz/nprogress@master/nprogress.js"></script>
<script src="https://<?php $this->options->jsdelivrLink() ?>/npm/pjax/pjax.min.js"></script>
<?php elseif($this->options->StaticFile == 'CDN' && $this->options->CDNURL !== ''): ?>
<link rel="stylesheet" href="https://lib.baomitu.com/nprogress/0.2.0/nprogress.css">
<script src="https://lib.baomitu.com/nprogress/0.2.0/nprogress.js"></script>
<script src="<?php $this->options->CDNURL() ?>/js/pjax.min.js"></script>
<?php else: ?>
<link rel="stylesheet" href="<?php $this->options->themeUrl('static/css/nprogress.css'); ?>">
<script src="<?php $this->options->themeUrl('static/js/nprogress.js'); ?>"></script>
<script src="<?php $this->options->themeUrl('static/js/pjax.min.js'); ?>"></script>
<?php endif; ?>
<script>
let pjaxSelectors = ["title", "#body-wrap", "#rightside-config-hide", "#rightside-config-show", ".js-pjax"];
var pjax = new Pjax({
    elements: 'a:not([target="_blank"])',
    selectors: pjaxSelectors,
    cacheBust: !1,
    analytics: !1,
    scrollRestoration: !1});
document.addEventListener("pjax:send", (function() {
if (window.removeEventListener("scroll", window.tocScrollFn), "object" == typeof preloader && preloader.initLoading(), window.aplayers)
for (let e = 0; e < window.aplayers.length; e++) window.aplayers[e].options.fixed || window.aplayers[e].destroy();"object" == typeof typed && typed.destroy();
const e = document.body.classList;
e.contains("read-mode") && e.remove("read-mode")
NProgress.start();
})),
document.addEventListener("pjax:complete", (function() {
    <?php $this->options->PjaxCallBack() ?>
    NProgress.done();
    document.querySelectorAll("script[data-pjax]").forEach(e => {
        const t = document.createElement("script"),
        o = e.text || e.textContent || e.innerHTML || "";
        Array.from(e.attributes).forEach(e => t.setAttribute(e.name, e.value)), t.appendChild(document.createTextNode(o)), e.parentNode.replaceChild(t, e)}),
    GLOBAL_CONFIG.islazyload && window.lazyLoadInstance.update(), "function" == typeof chatBtnFn && chatBtnFn(), "function" == typeof panguInit && panguInit(), "function" == typeof gtag && gtag("config", "", 
    {page_path: window.location.pathname}),
    "object" == typeof _hmt && _hmt.push(["_trackPageview", window.location.pathname]), 
    "function" == typeof loadMeting && document.getElementsByClassName("aplayer").length && loadMeting(),
    "object" == typeof Prism && Prism.highlightAll(), "object" == typeof preloader && preloader.endLoading()

    window.refreshFn();
})),
document.addEventListener("pjax:error", e => {
    // 404 === e.request.status && pjax.loadUrl("/404");
    if(e.request.status === 404){
        window.location="/404";
    }
    if(e.request.status === 403){
        window.location=e.request.responseURL
    }
})
</script>
<?php endif?>
 <!--pjax end-->
</body>
</html>