<?php

/**
 ***** VARS ****
 * $file_id
 * $count
 * $label
 */


?>
<a href="<?php SITE_URL ?>?module=downloader&event=download&id=<?php $file_id() ?>" target="_blank" class="pinion-module-info-downloader">
    <div class="pinion-download-icon"></div>
    <div class="pinion-download-title"><?php $label() ?></div>
</a>