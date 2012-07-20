<div id="pinion-page-maintenance"> 
    <div class="pinion-logo"></div>
    <div class="pinion-box">
        <div class="pinion-maintenance-mode"><?php $translate("maintenance mode") ?></div>
        <div class="pinion-maintenance-explanation">
            <?php
                $url();
                print " ";
                $translate("is currently in maintenance mode");
                print "<br />";
                $translate("Please try again later");
            ?>
        </div>
    </div>
</div>