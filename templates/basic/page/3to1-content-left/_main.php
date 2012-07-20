<?php
    $pageBackground = $get("vars")->pageBackground;
    $headerBackground = $get("vars")->headerBackground;
    $footerBackground = $get("vars")->footerBackground;
    $headerText = $get("vars")->headerText;
    $footerText = $get("vars")->footerText;
?>

<div id="basic-page-outer">
    <div id="basic-page" style="background-color: <?php print $pageBackground; ?>">
        <div id="header" style="background-color: <?php print $headerBackground; ?>; color: <?php print $headerText; ?>">
            <div id="header-content">
                <div class="header-content-left">
                    <?php $area("header-left") ?>
                </div>
                <div class="header-content-right">
                    <?php $area("header-right") ?>
                </div>
            </div>
        </div>
        <div id="languageSwitcher">
            <?php 
                $languageSwitcher = $this->module("translation")->getTranslator();
                $this->render($languageSwitcher);
            ?>
        </div>
        <div id="page-content">
            <div class="column column-3 first-column">
                <h1 class="page-title"><?php $title() ?></h1>
                <?php $area("content") ?>
            </div>
            <div class="column column-1 last-column">
                <?php $area("column-left") ?>
            </div>
        </div>
        <div id="push"></div>
    </div>
    <div id="footer" style="background-color: <?php print $footerBackground; ?>; color: <?php print $footerText; ?> ">
        <div id="footer-content">
            <div class="footer-content-left">
                <p class="pinion-note" style="color: <?php print $footerText; ?>">
                    <?php $translate("this site was created with the CMS pinion", array(
                        "de" => "Diese Seite wurde mit dem CMS pinion erstellt")) 
                    ?> - <a href="<?php print PINION_URL ?>" target="_blank"><?php print substr(PINION_URL, 7) ?></a>
                </p>
            </div>
            <div class="footer-content-right">
                <?php $area("footer") ?>
            </div>
        </div>
    </div>
</div>