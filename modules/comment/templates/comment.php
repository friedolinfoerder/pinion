<div>
    <div><?php $name() ?></div>
    <?php if($get("has_email") && !is_null($get("email"))): ?>
    <div><?php $email() ?></div>
    <?php endif; ?>
    <?php if($get("has_homepage") && !is_null($get("homepage"))): ?>
    <div><?php $homepage() ?></div>
    <?php endif; ?>
    <?php if($get("has_subject") && !is_null($get("subject"))): ?>
    <div><?php $subject() ?></div>
    <?php endif; ?>
    <div><?php $text() ?></div>
</div>
