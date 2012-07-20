<?php

/**
 ***** VARS ****
 * 
 */


?>
<div>
    <video id="video-<?php $id() ?>" class="video-js vjs-default-skin" controls preload="auto" width="640" height="264">
        <?php 
            foreach($get("videofiles") as $videofile) {
                $file = $videofile["file"];
                print "<source src='{$get("path")}{$file["filename"]}' type='video/{$file["type"]}' />";
            }
        ?>
    </video>
    <script>
        <?php $js() ?> 
        _V_("video-<?php $id() ?>");
    </script>
</div>
