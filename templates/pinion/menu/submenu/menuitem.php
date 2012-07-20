<?php
    $aClasses = array($get("position"));
	$activeBranch = true;
    
    if($get("hasChildren")) {
        $aClasses[] = "hasChildren";
    }
    
    if($get("active")) {
        $aClasses[] = "active";    
    } elseif($get("activeBranch")){
        $aClasses[] = "activeBranch";
    } else {
        $aClasses[] = "neutral";
		$activeBranch = false;
    }
    $aClasses = join(" ", $aClasses);
    
    if($get("level") == 0) {
        if($get("hasChildren") && $activeBranch) {
            $this->renderTemplateWithAttribute("menuitem", "menuitems", "children");
        }
        return;
    }
	
?>


<li><a class="<?php print $aClasses ?>" href="<?php print SITE_URL; $url() ?>"><?php $title() ?></a>
    <?php
        if($get("hasChildren")) {
            $template("_main");
        }    
    ?>
</li>

