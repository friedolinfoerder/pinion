<div class="news-item">
    <div class="news-date"><?php $date($get("created")) ?></div>
    <?php
        $elements = $get("elements");
        foreach($elements as $element) {
            $this->content($element);
        }
    ?>
</div>