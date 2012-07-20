<?php
// here we need jquery
$module("jquery")->run();
?>

<div id="content-box-<?php $uid() ?>" class="content-box">
    <?php
        $elements = $get("elements");
        foreach($elements as $element) {
            $this->content($element);
        }
    ?>
    <script>
        jQuery("#content-box-<?php $uid() ?>").contentBox();
    </script>
</div>