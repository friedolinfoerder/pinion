<div id="pinion-backend-menu" class="pinion-font">
    <a class='menuHeadline'>
        <span class="pinion-backend-menu-icon"></span>
        <span class="pinion-backend-menu-text"><?php $menuString() ?></span>
    </a>
    <div class="pinion-backend-menu-list first pinion-hide">
        <div class="windowContainer">
            <ul>
                <?php 
                    $modulesForMenu = $get("modulesForMenu");
                    if($modulesForMenu):
                ?>
                <li><a><span class='text'><?php $modulesString() ?></span><span class='pinion-backend-icon-arrowRight'></span></a>
                    <div class='pinion-backend-menu-list'>
                        <div class='windowContainer'>
                            <ul>
                                <?php 
                                    foreach($modulesForMenu as $category => $modules) {
                                        $ul = "<div class='pinion-backend-menu-list'><div class='windowContainer'><ul>";
                                        $moduleCount = 0;
                                        foreach($modules as $module) {
                                            $ul .= "<li><a class='withIcon' href='pinion/modules/{$module["name"]}'><span class='icon'><img src='{$module["imageSrc"]}' width='25px' /></span><span class='text'>{$module["title"]}</span></a></li>";
                                            $moduleCount++;
                                        }
                                        $ul .= "</ul></div></div>";

                                        print "<li><a><span class='text'>$category ($moduleCount)</span><span class='pinion-backend-icon-arrowRight'></span></a>$ul</li>";
                                    }
                                ?>
                            </ul>
                        </div>
                    </div>
                </li>
                <?php endif; ?>
                <?php 
                    $menuItems = $get("menuItems");
                    function generateMenuitem($key, $value) {
                        if(isset($value["href"])) {
                            print "<li><a class='withIcon' href='pinion/modules/{$value["href"]}'><span class='icon'><img src='{$value["icon"]}' width='25px' /></span><span class='text'>$key</span></a></li>";
                        } else {
                            print "<li><a><span class='text'>$key</span><span class='pinion-backend-icon-arrowRight'></span></a>";
                            print "<div class='pinion-backend-menu-list'><div class='windowContainer'><ul>";
                            foreach($value as $name => $val) {
                                generateMenuitem($name, $val);
                            }
                            print "</ul></div></div></li>";
                        }
                    }
                    
                        
                    foreach($menuItems as $key => $value) {
                        generateMenuitem($key, $value);
                    }  
                ?>
            </ul>
        </div>
    </div>
</div>
<div id="pinion-backend-shortcutsWrapper">
    <ul id="pinion-backend-shortcuts"></ul>
    <div id="pinion-backend-icon-trashCan"></div>
</div>
<div id="pinion-backend-bar-right">
    <div id="pinion-messages-wrapper">
        <div id="pinion-messages">
            <div id="pinion-logo">
                <div class="pinion-backend-icon-logo"></div>
            </div>
        </div>
    </div>
    <a id="pinion-backend-icon-preview" href="<?php $url() ?>?preview" target="_blank"></a>
    <div id="pinion-backend-user"><span id="pinion-backend-user-firstname"><?php print $identity(false)->firstname ?></span> <span id="pinion-backend-user-lastname"><?php print $identity(false)->lastname ?></span></div>
    <a id="pinion-backend-logoutLink" class="pinion-backend-icon-logout" href="#"></a>
</div>
