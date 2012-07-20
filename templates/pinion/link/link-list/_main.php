<?php

/**
 ***** VARS ****
 * 
 */


?>
<a href="<?php $url() ?>"<?php $newTab() ?> class="pinion-link-list-item">
    <div class="pinion-link-list-icon pinion-icon-<?php print str_replace("/", "-", $get("url")) ?>"></div>
    <div class="pinion-link-list-title"><?php $title() ?></div>
</a>