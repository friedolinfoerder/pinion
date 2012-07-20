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
        <div class="column first-column column-2">
            linke Spalte Breite 2
             <?php $area("testarea1"); ?>
        </div>
        <div class="column last-column column-2">
            rechte Spalte Breite 2
             <?php $area("testarea2"); ?>
        </div>
        <div class="column first-column column-1">
            erste Spalte Breite 1
             <?php $area("testarea3"); ?>
        </div>
        <div class="column column-1">
            zweite Spalte Breite 1
             <?php $area("testarea4"); ?>
        </div>
        <div class="column column-1">
            dritte Spalte Breite 1
             <?php $area("testarea5"); ?>
        </div>
        <div class="column last-column column-1">
            vierte Spalte Breite 1
             <?php $area("testarea6"); ?>
        </div>

        <div class="column first-column column-3">
            linke Spalte Breite 3
             <?php $area("testarea7"); ?>
        </div>
        <div class="column last-column column-1">
            rechte Spalte Breite 1
             <?php $area("testarea8"); ?>
        </div>

        <div class="column first-column column-1">
            erste Spalte Breite 1, danach folgt aber keine mehr
             <?php $area("testarea9"); ?>
        </div>

        <div class="column first-column last-column column-4">
            eine Spalte Breite 4
             <?php $area("testarea10"); ?>
        </div>
    </div>
    <div id="footer">
         <?php $area("footer"); ?>
    </div>
</div>