<?php
/*
 * * * * VARIABLES
 * menuitems
 *
 * * * * TEMPLATES
 * 
 *
*/
?>


<?php 
    $ulClass = $this->level(array(
        "classNames" => array("submenu")
    ));
?>


<ul class="<?php print $ulClass ?>">
    <?php $this->renderTemplateWithAttribute("menuitem", "menuitems", "children") ?>
</ul>