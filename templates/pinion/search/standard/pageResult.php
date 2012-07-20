<div class="search-results">
    <div class="search-result">
        <span class="search-count">
            <?php $count = $get("count"); print $count; ?>
        </span>
        <span><?php $results = ($count == 1) ? "result" : "results"; $translate($results." found on page")?></span>
        <a class="page-link" href="<?php $url() ?>">
            <?php $url() ?>
        </a>
    </div>
    <div class="search-text">
        <?php 
            $elements = $get("elements");
            foreach($elements as $element) {
                $this->content($element);
            }
        ?>
    </div>
</div>