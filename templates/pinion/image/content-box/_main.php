<div class="content-box-image">
<?php 
    $images = $get("images");
    // get a random image
    $image = $images[rand(0, count($images) - 1)];
    print $image["tag"];
?>
</div>