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
            $label=explode("||",$label);
            echo '<div class="menus_item">';
            echo "<a class='site-page group' href='javascript:void(0);' data-pjax-state=''>";
            echo "<i class='fa-fw $label[1]'></i>";
            echo "<span>$label[0]</span>";

            echo "<i class='fas fa-chevron-down'></i>";
            echo '</a>';
            
            // 渲染子菜单
            echo '<ul class="menus_item_child">';
            foreach ($data as $subLabel => $subData) {
                // 如果是子菜单项，直接渲染
                if (!is_array($subData)) {
                    [$url, $icon] = explode(' || ', $subData); // 分离URL和图标
                    $fullUrl = rtrim(Helper::options()->siteUrl, '/') . $url;
                    echo "<li><a class='site-page child' href='$fullUrl' data-pjax-state=''>";
                    echo "<i class='fa-fw $icon'></i><span>$subLabel</span></a></li>";
                } else {
                    // 如果子菜单项有子菜单，递归渲染
                    echo "<li>";
                    echo "<a class='site-page child' href='javascript:void(0);' data-pjax-state=''>";
                    echo "<i class='fa-fw iconfont'></i><span>$subLabel</span><i class='fas fa-chevron-down'></i></a>";
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
            [$url, $icon] = explode(' || ', $data); // 分离URL和图标
            $fullUrl = rtrim(Helper::options()->siteUrl, '/') . $url;
            echo '<div class="menus_item">';
            echo "<a class='site-page' href='$fullUrl' data-pjax-state=''>";
            echo "<i class='fa-fw $icon'></i>";
            echo "<span>$label</span>";
            echo '</a>';
            echo '</div>';
        }
    }
    echo '</div>';
}

function renderMenuRecursive($menuItems) {
    foreach ($menuItems as $subLabel => $subData) {
        if (!is_array($subData)) {
            [$url, $icon] = explode(' || ', $subData);
            $fullUrl = rtrim(Helper::options()->siteUrl, '/') . $url;
            echo "<li><a class='site-page child' href='$fullUrl' data-pjax-state=''>";
            echo "<i class='fa-fw $icon'></i><span>$subLabel</span></a></li>";
        } else {
            echo "<li>";
            echo "<a class='site-page child' href='javascript:void(0);' data-pjax-state=''>";
            echo "<i class='fa-fw iconfont'></i><span>$subLabel</span><i class='fas fa-chevron-down'></i></a>";
            echo '<ul class="menus_item_child">';
            renderMenuRecursive($subData);
            echo '</ul>';
            echo "</li>";
        }
    }
}


?>