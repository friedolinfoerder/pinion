
pinion.namespace("modules.youtube");

pinion.modules.youtube.Preview = (function($) {
    
    var constr;
    
    constr = function(data) {
        this.$element = $("<div>")
            .one("mouseenter", function() {
                $("<iframe width='240' height='180' src='http://www.youtube.com/embed/"+data.videoid+"?autoplay=1' frameborder='0'></iframe>")
                    .appendTo($video);
            });
        
        $("<div class='pinion-preview-info'></div>")
            .append("<div class='pinion-width'><div class='pinion-icon'></div><div class='pinion-text'>"+data.width+"px</div></div>")
            .append("<div class='pinion-height'><div class='pinion-icon'></div><div class='pinion-text'>"+data.height+"px</div></div>")
            .appendTo(this.$element);
            
        var $video = $("<div class='pinion-youtube-video'>")
            .appendTo(this.$element);
            
        $("<div class='pinion-youtube-logo'></div>")
            .appendTo(this.$element);
            
        $("<a href='http://www.youtube.com/watch?v="+data.videoid+"' class='pinion-youtube-link'>http://www.youtube.com/watch?v="+data.videoid+"</a>")
            .appendTo(this.$element);
    };
    
    constr.prototype = {
        constructor: pinion.modules.youtube.Preview,
        init: function() {
            
        }
    };
    
    return constr;
    
}(jQuery));
