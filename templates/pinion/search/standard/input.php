<form action="<?php print SITE_URL ?><?php $pageUrl() ?>" method="post" id="<?php $domId() ?>">
    <input class="textinput" type="text" name="value" />
    <input class="icon-search" type="submit" value="<?php $translate("search") ?>" />
    <input type="hidden" name="id" value="<?php $id() ?>" />
    <input type="hidden" name="event" value="doSearch" />
    <input type="hidden" name="module" value="search" />
</form>

<!--<script>
    (function($) {
        var $form = $("#<?php $domId() ?>");
        $form.submit(function() {
            var value = $form.find("[name=value]"),
                $results = $(".<?php $domClass() ?>");

            $.ajax({
                type: "post",
                dataType: "json",
                data: $.toJSON({
                    events: [{
                        event: "doSearch",
                        module: "search",
                        info: {
                            id: <?php $id() ?>,
                            value: value.val()
                        }
                    }]
                }),
                success: function(data) {
                    var content = data.content;
                    $results.html("");
                    console.log(content);
                    if(content !== undefined) {
                        for(var i = 0, length = content.length; i < length; i++) {
                            $results.append(content[i]);
                        }
                    }
                }
            });

            return false;
        });

        // remove script
        $form.next().remove();
    }(jQuery));
</script>-->