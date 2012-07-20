<?php

/**
 ***** VARS ****
 * $file_id
 * $count
 * $label
 */

$filename = $module("fileupload")->data->find_by_id("file", $file_id(false))->filename;
$version = substr($filename, 7, -4);

?>
<div class="pinion-downloader">
    <a href="<?php SITE_URL ?>?module=downloader&event=download&id=<?php $file_id() ?>" target="_blank">
        <div class="download-icon"></div>
        <div class="download-text"><?php $label() ?></div>
        <div class="download-info"><?php print $version ?> (<?php $count() ?> downloads)</div>
    </a>
</div>