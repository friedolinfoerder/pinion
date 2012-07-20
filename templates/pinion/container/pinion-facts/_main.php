<div class="pinion-facts">
    <?php
        $elements = $get("elements");
        foreach($elements as $element) {
            $this->content($element);
        }
    ?>
</div>