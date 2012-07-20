<?php

/**
 ***** VARS ****
 * text
 */

?>

<div class="pinion-todo-note">
    <div class="note-top">
        <div class="note-text-container">
            <div class="note-text"><?php $text() ?></div>
        </div>
        <div class="note-topRight">
            <div class="note-topRight-corner"></div>
            <div class="note-topRight-side"></div>
        </div>
    </div>
    <div class="note-bottom">
        <div class="note-bottomLeft"></div>
        <div class="note-bottomMiddle">
            <div class="note-created"><?php print date("d.m.Y - H:i", $get("created")) ?></div>
        </div>
        <div class="note-bottomRight"></div>
        
    </div>
</div>

<!--<div>
    <p>
        <?php $text() ?>
    </p>
</div>-->