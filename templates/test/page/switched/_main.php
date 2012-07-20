<div id="page">
    <div id="header">
        <h1>pinion test-page</h1>
        <?php 
            $languageSwitcher = $this->module("translation")->getTranslator();
            $this->render($languageSwitcher);
        ?>
    </div>
    <div id="wrapper" class="clearfix">
        <div id="linkeSpalte">
            <?php $area("right"); ?>
        </div>
        <div id="content">
            <?php $area("mainarea"); ?>
        </div>
        <div id="rechteSpalte">
            <div id="rechtsContent">
                <?php $area("left"); ?>
            </div>
            <div id="rechtsUnten">
            </div>
        </div>
    </div>
    <div id="footer">
        <h5>lalala - bli bla blub</h5>
    </div>
</div>