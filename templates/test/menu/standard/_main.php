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
        "classNames" => array("mainmenu", "submenu"),
        "range" => array(0, 4),
    ));
?>


<ul class="<?php print $ulClass ?>">
    <?php $this->renderTemplateWithAttribute("menuitem", "menuitems", "children", array("max" => 12)) ?>
</ul>