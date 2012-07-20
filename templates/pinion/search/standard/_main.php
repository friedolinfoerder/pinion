<?php

/**
 ***** VARS ****
 * domId
 */


?>
<div class="pinion-search">
    <?php 
        $template("input");
        
        if($get("with_results")) {
            if($get("searched")) {
                $this->renderTemplateWithAttribute("pageResult", "results");
            } else {
                print "No search!";
            }
        } 
    ?>
</div>