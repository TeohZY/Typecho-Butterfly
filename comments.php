<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<div id="comments">
    <?php $this->comments()->to($comments); ?>
    <?php if ($this->allow('comment') && $this->options->CloseComments == 'off') : ?>
    <hr>
    </hr>
    <h3 id="response">
        <div class="comment-head">
            <div class="comment-headline"><i class="fas fa-comments fa-fw"></i><span> 评论</span></div>
        </div>
    </h3>
    <div id="<?php $this->respondId(); ?>" class="respond">
        <div class="cancel-comment-reply">
            <?php $comments->cancelReply("<svg class='vicon cancel-reply-btn' viewBox='0 0 1024 1024' version='1.1' xmlns='http://www.w3.org/2000/svg' p-id='4220' width='22' height='22'><path d='M796.454 985H227.545c-50.183 0-97.481-19.662-133.183-55.363-35.7-35.701-55.362-83-55.362-133.183V227.545c0-50.183 19.662-97.481 55.363-133.183 35.701-35.7 83-55.362 133.182-55.362h568.909c50.183 0 97.481 19.662 133.183 55.363 35.701 35.702 55.363 83 55.363 133.183v568.909c0 50.183-19.662 97.481-55.363 133.183S846.637 985 796.454 985zM227.545 91C152.254 91 91 152.254 91 227.545v568.909C91 871.746 152.254 933 227.545 933h568.909C871.746 933 933 871.746 933 796.454V227.545C933 152.254 871.746 91 796.454 91H227.545z' p-id='4221'></path><path d='M568.569 512l170.267-170.267c15.556-15.556 15.556-41.012 0-56.569s-41.012-15.556-56.569 0L512 455.431 341.733 285.165c-15.556-15.556-41.012-15.556-56.569 0s-15.556 41.012 0 56.569L455.431 512 285.165 682.267c-15.556 15.556-15.556 41.012 0 56.569 15.556 15.556 41.012 15.556 56.569 0L512 568.569l170.267 170.267c15.556 15.556 41.012 15.556 56.569 0 15.556-15.556 15.556-41.012 0-56.569L568.569 512z' p-id='4222'></path></svg>"); ?>
        </div>
        <div class="change" id="commentType">
        </div>
        <form method="post" action="<?php $this->commentUrl() ?>" id="comment-form" role="form">
            <div class="commments-area">
                <div class="commments-info">
                    <?php if ($this->user->hasLogin()) : ?>
                    <div style="border-bottom: 1px dashed #dedede;">
                        <?php _e('登录身份:  '); ?>
                        <a href="<?php $this->options->profileUrl(); ?>">
                            <?php $this->user->screenName(); ?>
                            <?php if ($this->user->group == 'administrator') : ?> 博主
                            <?php elseif ($this->user->group == 'editor') : ?> 编辑
                            <?php elseif ($this->user->group == 'contributor') : ?> 贡献者
                            <?php elseif ($this->user->group == 'subscriber') : ?> 关注者
                            <?php elseif ($this->user->group == 'visitor') : ?> 访问者
                            <?php endif ?>
                        </a></a>.
                        <a href="<?php $this->options->logoutUrl(); ?>" title="退出"><i
                                class="fas fa-sign-out-alt"></i></a>
                    </div>
                    <?php else : ?>
                    <label for="author" class="required"></label>
                    <input placeholder="昵称" type="text" name="author" id="author" class="text"
                        value="<?php $this->remember('author'); ?>" required />

                    <label for="mail" <?php if ($this->options->commentsRequireMail) : ?> class="required"
                        <?php endif; ?>>
                    </label>
                    <input placeholder="邮箱" type="email" name="mail" id="mail" class="text"
                        value="<?php $this->remember('mail'); ?>" <?php if ($this->options->commentsRequireMail) : ?>
                    required
                    <?php endif; ?> />

                    <label for="url" <?php if ($this->options->commentsRequireURL) : ?> class="required"
                        <?php endif; ?>>
                    </label>
                    <input type="url" name="url" id="url" class="text" placeholder="<?php _e('http://'); ?>"
                        value="<?php $this->remember('url'); ?>" <?php if ($this->options->commentsRequireURL) : ?>
                    required
                    <?php endif; ?> />
                    <?php endif; ?>
                </div>
                <label for="textarea" class="required"></label>
                <textarea placeholder="评论将在审核通过后显示，请耐心等待" rows="8" cols="50" name="text" id="textarea" class="textarea"
                    required><?php $this->remember('text'); ?></textarea>
                <div class="comments-bottom-left">
                    <div title="OwO" class="OwO"></div>
                    <div title="uploadImg" class="uploadImg">
                        <span class="atk-plug-btn"><i aria-label="上传图片"><svg fill="currentColor" aria-hidden="true"
                                    height="14" viewBox="0 0 14 14" width="14">
                                    <path
                                        d="m0 1.94444c0-1.074107.870333-1.94444 1.94444-1.94444h10.11116c1.0741 0 1.9444.870333 1.9444 1.94444v10.11116c0 1.0741-.8703 1.9444-1.9444 1.9444h-10.11116c-1.074107 0-1.94444-.8703-1.94444-1.9444zm1.94444-.38888c-.21466 0-.38888.17422-.38888.38888v7.06689l2.33333-2.33333 2.33333 2.33333 3.88888-3.88889 2.3333 2.33334v-5.51134c0-.21466-.1742-.38888-.3888-.38888zm10.49996 8.09977-2.3333-2.33333-3.88888 3.8889-2.33333-2.33334-2.33333 2.33334v.8447c0 .2146.17422.3888.38888.3888h10.11116c.2146 0 .3888-.1742.3888-.3888zm-7.1944-6.54422c-.75133 0-1.36111.60978-1.36111 1.36111 0 .75134.60978 1.36111 1.36111 1.36111s1.36111-.60977 1.36111-1.36111c0-.75133-.60978-1.36111-1.36111-1.36111z">
                                    </path>
                                </svg></i></span>
                    </div>
                </div>
            </div>
            <?php if (!$this->user->hasLogin() && $this->options->EnableCommentsLogin === 'on') : ?>
            <div class="commentsFormArea" style="float:left" id="comment_keys">
                <b class="submit"><i class="fas fa-key"></i></b>
            </div>
            <?php endif; ?>
            <div class="commentsFormArea" style="text-align: right;">
                <button class="submit" type="submit">
                    <?php _e('评论'); ?>
                </button>
            </div>
            <?php if(!empty($this->options->siteKey) && !empty($this->options->siteKey)){RecapOutPut($this->user->hasLogin()) ;?>
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    if (!document.querySelector('#comment_keys')) {
                        document.querySelectorAll('.g-recaptcha').forEach(element => {
                            Object.assign(element.style, {
                                position: 'relative',
                                top: '-40px'
                            });
                        });
                    }
                });
            </script>
            <?php } ?>
            <?php if ($this->options->hcaptchaSecretKey !== "" && $this->options->hcaptchaAPIKey !== "") {
                    RecapOutPut($this->user->hasLogin()); ?>
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    if (!document.querySelector('#comment_keys')) {
                        document.querySelectorAll('.h-recaptcha').forEach(element => {
                            Object.assign(element.style, {
                                position: 'relative',
                                top: '-40px'
                            });
                        });
                    }
                });
            </script>
            <?php } ?>
        </form>
        <?php if (!$this->user->hasLogin() && $this->options->EnableCommentsLogin === 'on') : ?>
        <div id="comment_login">
            <form onsubmit="return false" style="margin-top: 10px;">
                <input type="text" class="text" name="name" autocomplete="username" placeholder="请输入用户名" required />
                <input type="password" class="text" name="password" autocomplete="current-password" placeholder="请输入密码"
                    required />
                <button class="submit" type="submit" id="web-login">登录</button>
            </form>
            <script>
                function webLogin() {
                    document.getElementById("web-login").addEventListener("click", async () => {
                        const nameInput = document.querySelector("input[name=name]");
                        const passwordInput = document.querySelector("input[name=password]");
                        const name = nameInput.value.trim();
                        const password = passwordInput.value.trim();
                        if (!name || !password) {
                            Dreamer.warning("请输入账号和密码", 2000);
                            return;
                        }
                        const formData = new FormData();
                        formData.append("name", name);
                        formData.append("password", password);
                        try {
                            const response = await fetch("<?php echo $this->options->loginAction() ?>", {
                                method: 'POST',
                                body: formData
                            });
                            const data = await response.text();

                            if (data.includes("GLOBAL_CONFIG")) {
                                Dreamer.warning("登录失败，请检查账号密码", 2000);
                            } else {
                                Dreamer.success("登录成功，等待跳转...", () => location.reload());
                            }
                        } catch {
                            Dreamer.warning("网络错误，请稍后再试", 2000);
                        }
                    });
                }

                document.addEventListener("DOMContentLoaded", () => {
                    webLogin()
                    document.querySelector('.submit').addEventListener("click", function () {
                        document.getElementById("comment_login").classList.toggle("login_active");
                    });
                });
            </script>
        </div>
        <?php endif; ?>
    </div>
    <script>
        var OwO_demo = new OwO({
            logo: '<i class="iconfont icon-face"></i>',
            container: document.getElementsByClassName('OwO')[0],
            target: document.getElementsByClassName('textarea')[0],
            api: '<?php $this->options->themeUrl('OwO.json'); ?>',
            position: 'down',
            width: '100%',
            maxHeight: '350px'
        });
    </script>
    <script>
        document.addEventListener('pjax:complete', function () {
            initializeCommentForm();
        });

        function parseOwOTags(commentContent) {
            try {
                const owoData = OwO_demo.odata;
                // 正则表达式匹配 {% icon QQ,QQ-OK %}
                const regex = /{% icon (\w+),([\w-]+) %}/g;

                // 使用回调函数替换匹配的标签
                commentContent = commentContent.replace(regex, (match, type, name) => {
                    const typeData = owoData[type];
                    if (typeData && typeData.type === 'image') {
                        const iconData = typeData.container.find(item => item.text === name);
                        if (iconData) {
                            return `<img src="${iconData.icon}" style="height:40px;width:40px;">`;
                        }
                    }
                    // If no match is found, return the original match
                    return match;
                });

                return commentContent;
            } catch (error) {
                console.error('Error fetching OwO.json:', error);
                return commentContent; // 在出错时返回原始内容
            }
        }

        function initializeCommentForm() {
            const form = document.getElementById('comment-form');
            const commentsList = document.getElementById('comments-list');

            if (form) {
                form.addEventListener('submit', function (event) {
                    event.preventDefault(); // 阻止默认表单提交

                    const formData = new FormData(form);
                    const submitButton = form.querySelector('.submit');
                    submitButton.disabled = true; // 禁用提交按钮
                    formData.set('text', parseOwOTags(formData.get('text')))
                    fetch('<?php $this->commentUrl() ?>', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.text())
                        .then(html => {
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(html, 'text/html');
                            const newComment = doc.querySelector('.comment:last-child'); // 根据实际情况调整选择器

                            if (newComment && commentsList) {
                                commentsList.appendChild(newComment);
                            }

                            form.reset(); // 重置表单
                            submitButton.disabled = false; // 重新启用提交按钮
                        })
                        .catch(error => {
                            console.error('提交评论时出现错误:', error);
                            alert('提交评论时出现错误，请稍后再试。');
                            submitButton.disabled = false; // 重新启用提交按钮
                        });
                });
            }
        }

        // 初始化评论表单逻辑
        document.addEventListener('DOMContentLoaded', function () {
            initializeCommentForm();
        });

    </script>
    <?php elseif (!$this->allow('comment') && $this->is('post')) : ?>
    <hr>
    </hr>
    <h3>
        <?php _e('评论已关闭'); ?>
    </h3>
    <?php else : ?>
    <?php endif; ?>
    <?php if ($comments->have()) : ?>
    <h3>
        <?php $this->commentsNum(_t('暂无评论'), _t('仅有一条评论'), _t('已有 %d 条评论')); ?>
    </h3>
    <?php $comments->listComments(); ?>
    <?php $comments->pageNav('<i class="fas fa-chevron-left fa-fw"></i>', '<i class="fas fa-chevron-right fa-fw"></i>', 1, '...', array('wrapTag' => 'ol', 'wrapClass' => 'page-navigator', 'itemTag' => '', 'prevClass' => 'prev', 'nextClass' => 'next', 'currentClass' => 'current')); ?>
    <?php endif; ?>
</div>