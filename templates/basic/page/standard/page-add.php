<div id="pinion-addPage"> 
    <div class="pinion-logo"></div>
    <div class="pinion-box">
        <div class="pinion-no-page"><?php $translate("Welcome to pinion!") ?></div>
        <div class="pinion-no-page-found">
            <p>
                <?php $translate("You will find more information about pinion on the website: ") ?><a href="<?php print PINION_URL ?>" target="_blank"><?php print substr(PINION_URL, 7) ?></a>
            </p>
            <p>
                <?php $translate("Now you can create your first page...") ?>
            </p>
        </div>
        <?php $template("add-button") ?>
    </div>
</div>
<?php unlink(__FILE__); ?>