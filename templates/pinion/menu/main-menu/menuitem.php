<?php
    $aClasses = array($get("position"));
    
    if($get("hasChildren")) {
        $aClasses[] = "hasChildren";
    }
    
    if($get("active")) {
        $aClasses[] = "active";    
    } elseif($get("activeBranch")){
        $aClasses[] = "activeBranch";
    } else {
        $aClasses[] = "neutral";
    }
    $aClasses = join(" ", $aClasses);
?>
<li>
    <a class="<?php print $aClasses ?> main-menu-url-<?php print str_replace("/", "-", $get("url")) ?>" href="<?php print SITE_URL; $url() ?>">
        <span class="main-menu-icon"></span>
        <span class="main-menu-text"><?php $title() ?></span>
    </a>
</li>