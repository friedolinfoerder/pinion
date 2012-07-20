<div class="nivoslideshow-wrapper">
    <div class="<?php $id() ?> theme-default">
        <?php
            $sources = $sources(false);
            foreach($sources as $source) {
                print "<img src='$source' />";
            }
        ?>
    </div>
    
    <script>
        (function($) {
            var $slideshow = $(".<?php $id() ?>");

            $slideshow
                .css({
                    width: <?php $width() ?>,
                    height: <?php $height() ?>
                })
                .nivoSlider(<?php $optionsJson() ?>);
                
            // remove script
            $slideshow.next().remove();
        }(jQuery));
    </script>
</div>



