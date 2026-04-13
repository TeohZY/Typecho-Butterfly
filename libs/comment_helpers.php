<?php
if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

function RecapOutPut($login)
{
    $siteKey = Helper::options()->siteKey;
    if (!empty($siteKey) && !empty(Helper::options()->secretKey) && !$login) {
        echo '<script src="https://recaptcha.net/recaptcha/api.js" async defer data-no-instant></script>
                              <div class="g-recaptcha" data-sitekey=' . $siteKey . '></div>';
    }
}

function comments_filter($comment)
{
    if (!empty($_REQUEST['text'])) {
        if (empty($_POST['g-recaptcha-response'])) {
            throw new Typecho_Widget_Exception(_t('人机验证失败,确认你加载了谷歌人机验证并通过验证'));
        } else {
            $secretKey = Helper::options()->secretKey;
            $recaptcha_response = $_POST['g-recaptcha-response'];

            $response = file_get_contents("https://recaptcha.net/recaptcha/api/siteverify?secret=" . $secretKey . "&response=" . $recaptcha_response);
            $resp = json_decode($response);

            if (!empty($resp->success)) {
                return $comment;
            } else {
                $errorCodes = !empty($resp->{'error-codes'}) ? $resp->{'error-codes'} : [];
                if (in_array('timeout-or-duplicate', $errorCodes)) {
                    throw new Typecho_Widget_Exception(_t('验证时间超过2分钟或连续重复发言！'));
                } elseif (in_array('invalid-input-secret', $errorCodes)) {
                    throw new Typecho_Widget_Exception(_t('博主填了无效的siteKey或者secretKey...'));
                } elseif (in_array('bad-request', $errorCodes)) {
                    throw new Typecho_Widget_Exception(_t('请求错误！请检查网络'));
                } else {
                    throw new Typecho_Widget_Exception(_t('很遗憾，您被当成了机器人...'));
                }
            }
        }
    }
    return $comment;
}

/**
 * 蜜罐 + 时间戳反垃圾过滤器
 * 无需用户操作，完全无感知
 */
function antiSpam_filter($comment)
{
    if (!empty($_POST['website'])) {
        throw new Typecho_Widget_Exception(_t('垃圾评论'));
    }

    if (isset($_POST['form_time']) && is_numeric($_POST['form_time'])) {
        $formTime = intval($_POST['form_time']);
        $diff = time() - $formTime;
        if ($diff < 3) {
            throw new Typecho_Widget_Exception(_t('评论提交过快'));
        }
    }

    return $comment;
}

Typecho_Plugin::factory('Widget_Feedback')->filter = 'antiSpam_filter';
