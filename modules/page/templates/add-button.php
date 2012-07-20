<script>
    jQuery(function($) {
        pinion.Frontend.instance.addElement({
            name: "Button",
            type: "input",
            label: "<?php $translate("add page") ?> <?php $page() ?>",
            click: function() {
                pinion.ajax({
                    event: "addPage",
                    module: "page",
                    info: {
                        url: "<?php $page() ?>"
                    }
                }, function() {
                    window.location.reload();
                });
            }
        }).$element.appendTo($("#pinion-addPage"));
    });
</script>