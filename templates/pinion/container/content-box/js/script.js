(function($) {
    $.fn.contentBox = function() {
        
        var $content = this.children(".content-box-content")
            .css("top", 155)
            .show();
        
        return this
                .hover(function() {
                    $content
                        .stop(true)
                        .animate({top: 35}, 300);
                }, function() {
                    $content
                        .stop(true)
                        .animate({top: 155}, 300);
                })
                .end();
    };
}(jQuery));
