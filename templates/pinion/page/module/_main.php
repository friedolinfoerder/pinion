<?php
$module("jQuery")->run();
?>

<div id="pageWrapper">
    <style type="text/css">
        /* import font from google-fonts*/
        @import url(http://fonts.googleapis.com/css?family=Asap:700italic,700,400italic,400);
    </style>
    <div id="header">
        <a id="logo" href="<?php print SITE_URL; ?>">
            pinion - open source web cms
        </a>
        <div id="menu">
            <?php $area("menu"); ?>
        </div>
        <div id="searchBox">
            <?php $area("search"); ?>
        </div>
        <div id="languageSwitcher">
            <?php 
                $languageSwitcher = $this->module("translation")->getTranslator();
                $this->render($languageSwitcher);
            ?>
        </div>
    </div>

    <div id="page-content">
        <h1 class="pinion-page-title"><?php $title() ?></h1>
        <div class="content-area column first-column column-3">
            <?php $area("content"); ?>
        
            <?php
                $numTabs = $get("vars")->numTabs;
                for($i = 0; $i < $numTabs; $i++) {
                    print "<div class='module-tab column first-column column-3'>";
                    $area("tab-$i");
                    print "</div>";
                }
            ?>
			
            <div class="content-area column first-column column-3">
                    <?php $area("comment"); ?>
            </div>
        </div>
        <div class="sidebar column last-column column-1">
            <?php $area("sidebar"); ?>
        </div>
    </div>
    <div id="footer">
         <?php $area("footer"); ?>
    </div>
</div>