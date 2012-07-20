<?php

/**
 ***** VARS ****
 * file
 */

$this->module("jQuery")->run();

?>

<div>
    <audio src="<?php $src() ?>" preload="auto" id="audio-<?php $id() ?>" />
    <script>
        (function($) {
            var $audio = $("#audio-<?php $id() ?>");
            audiojs.create($audio.get(0));
            $audio.next("script").remove();
        }(jQuery));
    </script>
</div>