# Typecho Butterfly 主题

基于 [hexo-theme-butterfly](https://github.com/jerryc127/hexo-theme-butterfly) 移植的 Typecho 主题。

## 特性

- 响应式设计，适配移动端
- 支持暗色模式
- 文章目录自动生成
- 图片懒加载
- 代码高亮 (PrismJS)
- 评论支持 (Twikoo)
- 本地搜索
- Pjax 无刷新加载
- 页面预加载
- 自定义样式美化

## 目录结构

```
.
├── 404.php              # 404 页面
├── archive.php          # 归档页
├── archive_header.php   # 归档页头部
├── articles.php         # 文章列表
├── category-list.php    # 分类列表
├── comments.php         # 评论模块
├── footer.php          # 页脚
├── functions.php       # 主题函数
├── header.php          # 页头
├── header_com.php      # 公共页头
├── index.php           # 首页
├── link.php            # 链接页面
├── page.php            # 页面
├── page_header.php     # 页面头部
├── post.php            # 文章页
├── post_header.php     # 文章头部
├── post_sidebar.php    # 文章侧边栏
├── sidebar.php         # 侧边栏
├── tags.php            # 标签页
│
├── css/                # 主 CSS 目录
├── js/                 # 主 JS 目录
├── img/                 # 主图片目录
│
├── libs/               # 库和扩展
│   ├── api.php         # API 路由处理
│   ├── core.php        # 核心函数（菜单渲染等）
│   ├── custom_config.php # 后台配置
│   ├── search.php      # 搜索 API
│   └── Vditor/         # Vditor 编辑器
│
├── widgets/            # 公共组件
│   ├── nav.php         # 导航栏
│   ├── noqq.php        # 防腾讯卫士
│   ├── defend.php      # 密码保护
│   └── rightside.php   # 右侧栏
│
└── assets/            # 静态资源
    ├── css/            # 扩展样式
    ├── fonts/         # 字体文件
    ├── img/           # 图片资源
    └── js/            # 扩展脚本
```

## 安装

1. 下载主题文件到 Typecho 主题目录
2. 确保目录名为 `Butterfly`
3. 在后台启用主题

## 配置

主题提供丰富的后台配置选项，包括：

### 基本设置
- 站点图标
- 首页图片
- SEO 设置
- 统计代码（百度/Google）

### 外观设置
- 暗色模式
- 自定义颜色
- 字体大小
- 美化模块开关

### 功能设置
- 本地搜索
- Pjax 加速
- 新标签打开链接
- 评论设置

### 高级设置
- 自定义 CSS/JS
- 密码保护
- 评论区验证码

## 依赖

### 前端依赖
- jQuery
- PrismJS (代码高亮)
- Fancybox (图片灯箱)
- LazyLoad (图片懒加载)
- OwO (表情)
- Vditor (编辑器)

### 后端依赖
- Typecho 1.2+
- PHP 7.4+

## 二次开发

### 添加自定义样式

在后台 `自定义 CSS` 框中添加：

```css
/* 示例 */
.my-custom-class {
    color: #fff;
}
```

### 添加自定义脚本

在后台 `自定义底部 JS` 框中添加：

```javascript
// 示例
console.log('Hello Butterfly');
```

### 主题钩子

可在后台配置以下钩子：
- `CustomHead` - 头部自定义内容
- `CustomCSS` - 自定义样式
- `CustomScript` - 底部自定义脚本
- `PjaxCallBack` - Pjax 回调函数

## 安全

本主题已修复以下安全问题：

- [x] SQL 注入防护
- [x] XSS 跨站脚本防护
- [x] CSRF 令牌验证
- [x] 密码保护机制 (Session + 哈希)

## 许可证

[MIT License](LICENSE)

## 致谢

- [hexo-theme-butterfly](https://github.com/jerryc127/hexo-theme-butterfly) - 主题原型
- [PrismJS](https://prismjs.com/) - 代码高亮
- [Fancybox](https://fancyapps.com/fancybox/) - 图片灯箱
- [Twikoo](https://twikoo.js.org/) - 评论系统
