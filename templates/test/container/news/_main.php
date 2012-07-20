<div class="newscontainer">
    <div><?php print date("d.m.Y - H:i", $get("created")) ?></div>
    <?php
        $elements = $get("elements");
        foreach($elements as $element) {
            $this->content($element);
        }
    ?>
</div>