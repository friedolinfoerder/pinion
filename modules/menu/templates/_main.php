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
        "wrapString" => "NR",
        "wrap" => "NR-level",
        "classNames" => array("first", "second", "third", "fourth"),
        "range" => array(0, 4),
    ));
?>


<ul class="<?php print $ulClass ?>">
    <?php $this->renderTemplateWithAttribute("menuitem", "menuitems", "children", array("max" => 12)) ?>
</ul>
