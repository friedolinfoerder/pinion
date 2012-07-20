<div>
    <?php $url() ?>, <?php $count() ?> results
    <div>
        <?php 
            $elements = $get("elements");
            foreach($elements as $element) {
                $this->content($element);
            }
        ?>
    </div>
</div>