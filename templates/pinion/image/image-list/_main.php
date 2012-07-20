<?php
// here we need colorbox
$colorbox = $module("colorbox");
if($colorbox) {
    $colorbox->run();
} else {
    print "<div>No colorbox module available</div>";
    return;
}
?>


<div id="image-<?php $id() ?>" class="image-list">
    <?php 
        foreach($get("images") as $image) {
            $imgTag = $image["tag"];
            $src = isset($image["versions"][1]) ? $image["versions"][1]["src"] : $image["src"];
            print "<a href='$src'>$imgTag</a>";
        }
    ?>
    <script>
        jQuery("#image-<?php $id() ?>").children("a").colorbox({rel: "#image-<?php $id() ?>"});
    </script>
</div>