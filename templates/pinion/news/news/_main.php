<div class="news">
    <h1 class="news-title">
        news
    </h1>
    <?php
        $elements = $get("elements");
        foreach($elements as $element) {
            $this->content($element);
        }
    ?>
</div>