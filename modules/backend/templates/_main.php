<div id="pinion-help">
    <div class="pinion-text-Wrapper">
        <span class='pinion-text'></span>
    </div>
</div>
<div id="pinion-backend-bar">
    <?php 
        if($get("isLoggedIn")) {
            $this->renderTemplate("bar");
        } else {
            $this->renderTemplate("login");
        }
    ?>
</div>
<div id="pinion-backend" class="pinion-font"></div>
<?php if($get("isLoggedIn")): ?>
    <ul id='pinion-backend-minimizeLinks'></ul>
    <div id="pinion-backend-menuClickStopper"></div>
    <div id="pinion-contextHelp"></div>
<?php endif; ?>
