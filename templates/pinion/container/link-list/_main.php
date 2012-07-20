<div class="pinion-link-list">
    <?php
        $elements = $get("elements");
        foreach($elements as $element) {
            $this->content($element);
        }
    ?>
</div>