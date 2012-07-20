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
        <div class="main-home column first-column last-column column-4 clearfix">
            <?php $area("main-home"); ?>
        </div>
        <div class="column first-column column-1">
            <?php $area("content-box-1"); ?>
<!--            <div class="content-box">
                <div class="content-box-headline">
                    <h2>develop modules</h2>
                </div>
                <div class="content-box-image">
                    <img src="templates/pinion/page/standard/images/testimage.jpg"></img>
                </div>
                <div class="content-box-content">
                    hallo
                </div>
            </div>-->
        </div>
        <div class="column column-1">
            <?php $area("content-box-2"); ?>
        </div>
        <div class="column column-1">
            <?php $area("content-box-3"); ?>
        </div>
        <div class="column last-column column-1">
            <?php $area("content-box-4"); ?>
        </div>
        <div class="content-area column first-column column-3">
<!--            inhalt der linken Spalte .content-->
            <?php $area("content"); ?>
        </div>
        <div class="sidebar column last-column column-1">
<!--            inhalt der rechten Spalte .sidebar-->
            <?php $area("sidebar"); ?>
        </div>
    </div>
    <div id="footer">
         <?php $area("footer"); ?>
    </div>
</div>