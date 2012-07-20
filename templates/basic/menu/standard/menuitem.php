<?php
    $aClasses = array($position(false), $zebra(false));
    
    if($hasChildren(false))
        $aClasses[] = "hasChildren";
    
    if($active(false))
        $aClasses[] = "active";
    elseif($activeBranch(false))
        $aClasses[] = "activeBranch";
    else
        $aClasses[] = "neutral";
    
    $aClasses = join(" ", $aClasses);
?>
<li><a class="<?php print $aClasses ?>" href='<?php $url() ?>'><?php $title() ?></a>
    <?php
        if($hasChildren(false)) {
            $template("_main");
        }    
    ?>
</li>