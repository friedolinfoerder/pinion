<?php

/**
 ***** VARS ****
 * id
 * comments (array)
 */
?>

<div class="comment">
    <h1 class="comment-headline"><?php $translate("comment") ?></h1>
    <form method="post" id="<?php $domId() ?>" class="comment-form">
        <div>
            <label><?php $translate("name") ?></label>
            <div class="inputwrapper"><input type="text" name="name" /></div>
        </div>
        <?php if($get("has_email")): ?>
        <div>
            <label><?php $translate("email") ?></label>
            <div class="inputwrapper"><input type="text" name="email" /></div>
        </div>
        <?php endif; ?>
        <?php if($get("has_homepage")): ?>
        <div>
            <label><?php $translate("homepage") ?></label>
            <div class="inputwrapper"><input type="text" name="homepage" /></div>
        </div>
        <?php endif; ?>
        <?php if($get("has_subject")): ?>
        <div>
            <label><?php $translate("subject") ?></label>
            <div class="inputwrapper"><input type="text" name="subject" /></div>
        </div>
        <?php endif; ?>
        <div>
            <label><?php $translate("comment") ?></label>
            <div class="inputwrapper"><textarea name="comment"></textarea></div>
        </div>
        <input type="hidden" name="event" value="addComment" />
        <input type="hidden" name="module" value="comment" />
        <input type="hidden" name="id" value="<?php $id() ?>" />
        <input type="submit" value="<?php $translate("send") ?>" />
    </form>
    <script>
        (function($) {
            var $form = $("#<?php $domId() ?>");
            $form.submit(function() {
                var info = {id: <?php $id() ?>};
                $(this).find("input[type=text], [name=comment]").each(function() {
                    var $this = $(this);
                    info[$this.attr("name")] = $this.val();
                    $this.val("");
                });
                    
                $.ajax({
                    type: "post",
                    data: $.toJSON({
                        events: [{
                            event: "addComment",
                            module: "comment",
                            info: info
                        }]
                    })
                });
                
                return false;
            });
            
            // remove script
            $form.next().remove();
        }(jQuery));
    </script>

    <?php $this->renderTemplateWithAttribute("comment", "comments") ?>
</div>