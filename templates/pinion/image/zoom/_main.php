<?php
// here we need jqueryzoom
$jqueryzoom = $this->module("jqueryzoom");
if($jqueryzoom) {
    $jqueryzoom->run();
} else {
    print "<div>No jqueryzoom module available</div>";
    return;
}
?>

<div id="image-<?php $id() ?>" class="image">
    <?php 
        foreach($get("images") as $image) {
            $imgTag = $image["tag"];
            $src = isset($image["versions"][1]) ? $image["versions"][1]["src"] : $image["src"];
            print "<span class='zoom-image' data-src='$src'>$imgTag</span>";
        }
    ?>
    <script>
        jQuery("#image-<?php $id() ?>")
            .children(".zoom-image")
                .each(function() {
                    var $this = jQuery(this);

                    $this.zoom({url: $this.attr("data-src")});
                })
                .end()
            .children("script")
                .remove();
    </script>
</div>