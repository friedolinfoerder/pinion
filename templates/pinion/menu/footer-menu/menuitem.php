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
    <a class="<?php print $aClasses ?>" href="<?php print SITE_URL; $url() ?>">
        <?php $title() ?>
    </a>
</li>