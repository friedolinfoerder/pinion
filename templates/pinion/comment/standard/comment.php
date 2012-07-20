<div class="comment-view">
    <div class="comment-name"><?php $name() ?></div>
    
    <?php if($get("has_email") && !is_null($get("email"))): ?>
    <div class="comment-mail">
        <div class="comment-mail-icon"></div><a class="comment-mail-text" href="mailto:<?php $email() ?>"><?php $email() ?></a>
    </div>
    <?php endif; ?>
    
    <?php if($get("has_homepage") && !is_null($get("homepage"))): ?>
    <div class="comment-homepage">
        <div class="comment-homepage-icon"></div><a class="comment-homepage-text" href="http://<?php $homepage() ?>"><?php $homepage() ?></a>
    </div>
    <?php endif; ?>
    
    <?php if($get("has_subject") && !is_null($get("subject"))): ?>
    <div class="comment-subject"><?php $subject() ?></div>
    <?php endif; ?>
    
    <div class="comment-text"><?php $text() ?></div>
</div>
