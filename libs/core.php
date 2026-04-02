<?php
function renderMenu($menuYaml) {
    // 解析 YAML 数据
    $menuItems = yaml_parse($menuYaml);

    // 如果解析失败或数据不是数组，直接返回
    if (!is_array($menuItems)) {
        return;
    }

    echo '<div class="menus_items">';
    foreach ($menuItems as $label => $data) {
        // 处理子菜单
        if (is_array($data)) {
            // 渲染父级菜单（带下拉箭头）
            $label = explode("||", $label);
            $label[0] = isset($label[0]) ? htmlspecialchars($label[0], ENT_QUOTES, 'UTF-8') : '';
            $label[1] = isset($label[1]) ? htmlspecialchars($label[1], ENT_QUOTES, 'UTF-8') : '';
            echo '<div class="menus_item">';
            echo "<a class='site-page group' href='javascript:void(0);' data-pjax-state=''>";
            echo "<i class='fa-fw " . $label[1] . "'></i>";
            echo "<span>" . $label[0] . "</span>";
            echo "<i class='fas fa-chevron-down'></i>";
            echo '</a>';

            // 渲染子菜单
            echo '<ul class="menus_item_child">';
            foreach ($data as $subLabel => $subData) {
                // 如果是子菜单项，直接渲染
                if (!is_array($subData)) {
                    $parts = explode(' || ', $subData);
                    $url = isset($parts[0]) ? htmlspecialchars($parts[0], ENT_QUOTES, 'UTF-8') : '';
                    $icon = isset($parts[1]) ? htmlspecialchars($parts[1], ENT_QUOTES, 'UTF-8') : '';
                    $fullUrl = rtrim(Helper::options()->siteUrl, '/') . $url;
                    $subLabel = htmlspecialchars($subLabel, ENT_QUOTES, 'UTF-8');
                    echo "<li><a class='site-page child' href='" . $fullUrl . "' data-pjax-state=''>";
                    echo "<i class='fa-fw " . $icon . "'></i><span>" . $subLabel . "</span></a></li>";
                } else {
                    // 如果子菜单项有子菜单，递归渲染
                    $subLabel = htmlspecialchars($subLabel, ENT_QUOTES, 'UTF-8');
                    echo "<li>";
                    echo "<a class='site-page child' href='javascript:void(0);' data-pjax-state=''>";
                    echo "<i class='fa-fw iconfont'></i><span>" . $subLabel . "</span><i class='fas fa-chevron-down'></i></a>";
                    echo '<ul class="menus_item_child">';
                    renderMenuRecursive($subData);  // 递归渲染子菜单
                    echo '</ul>';
                    echo "</li>";
                }
            }
            echo '</ul>';
            echo '</div>';
        } else {
            // 渲染普通菜单项
            $parts = explode(' || ', $data);
            $url = isset($parts[0]) ? htmlspecialchars($parts[0], ENT_QUOTES, 'UTF-8') : '';
            $icon = isset($parts[1]) ? htmlspecialchars($parts[1], ENT_QUOTES, 'UTF-8') : '';
            $label = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
            $fullUrl = rtrim(Helper::options()->siteUrl, '/') . $url;
            echo '<div class="menus_item">';
            echo "<a class='site-page' href='" . $fullUrl . "' data-pjax-state=''>";
            echo "<i class='fa-fw " . $icon . "'></i>";
            echo "<span>" . $label . "</span>";
            echo '</a>';
            echo '</div>';
        }
    }
    echo '</div>';
}

function renderMenuRecursive($menuItems) {
    foreach ($menuItems as $subLabel => $subData) {
        if (!is_array($subData)) {
            $parts = explode(' || ', $subData);
            $url = isset($parts[0]) ? htmlspecialchars($parts[0], ENT_QUOTES, 'UTF-8') : '';
            $icon = isset($parts[1]) ? htmlspecialchars($parts[1], ENT_QUOTES, 'UTF-8') : '';
            $fullUrl = rtrim(Helper::options()->siteUrl, '/') . $url;
            $subLabel = htmlspecialchars($subLabel, ENT_QUOTES, 'UTF-8');
            echo "<li><a class='site-page child' href='" . $fullUrl . "' data-pjax-state=''>";
            echo "<i class='fa-fw " . $icon . "'></i><span>" . $subLabel . "</span></a></li>";
        } else {
            $subLabel = htmlspecialchars($subLabel, ENT_QUOTES, 'UTF-8');
            echo "<li>";
            echo "<a class='site-page child' href='javascript:void(0);' data-pjax-state=''>";
            echo "<i class='fa-fw iconfont'></i><span>" . $subLabel . "</span><i class='fas fa-chevron-down'></i></a>";
            echo '<ul class="menus_item_child">';
            renderMenuRecursive($subData);
            echo '</ul>';
            echo "</li>";
        }
    }
}


?>
