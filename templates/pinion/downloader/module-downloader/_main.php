<?php

/**
 ***** VARS ****
 * $file_id
 * $count
 * $label
 */


?>
<div class="pinion-module-downloader">
    <a href="<?php SITE_URL ?>?module=downloader&event=download&id=<?php $file_id() ?>" target="_blank">
        <div class="download-icon"></div>
        <div class="download-text"><?php $label() ?></div>
        <div class="download-info"><?php $count() ?> downloads till now</div>
    </a>
</div>