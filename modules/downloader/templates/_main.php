<?php

/**
 ***** VARS ****
 * $file_id
 * $count
 * $label
 */


?>
<div>
    <a href="<?php SITE_URL ?>?module=downloader&event=download&id=<?php $file_id() ?>" target="_blank"><?php $label() ?></a>
    <div><?php $count() ?> downloads</div>
</div>