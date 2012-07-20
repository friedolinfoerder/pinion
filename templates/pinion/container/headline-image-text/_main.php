<div class="headline-image-text clearfix">
    <?php
        $elements = $get("elements");
        foreach($elements as $element) {
            $this->content($element);
        }
    ?>
</div>