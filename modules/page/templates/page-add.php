<div id="pinion-addPage"> 
    <div class="pinion-logo"></div>
    <div class="pinion-box">
        <div class="pinion-no-page"><?php $translate("Page not found!") ?></div>
        <div class="pinion-no-page-found">
            <?php $translate("There is no page with the url ") ?><?php $url() ?><?php $page() ?>
        </div>

        <div class="pinion-create-page">
            <div class="pinion-text"><?php $translate("Do you want to create this page?"); ?></div>
        </div>
        <?php $template("add-button") ?>
    </div>
</div>

