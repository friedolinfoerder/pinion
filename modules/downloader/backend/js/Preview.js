
pinion.namespace("modules.downloader");

pinion.modules.downloader.Preview = (function($) {
    
    var constr;
    
    constr = function(data) {
        this.$element = $("<div>");
        
        $("<div class='pinion-preview-info'></div>")
            .append("<div class='pinion-downloader-label'>"+data.label+"</div>")
            .appendTo(this.$element);
            
        this.$element
            .append("<div class='pinion-icon-file'></div>")
            .append("<div class='pinion-downloader-filename'>"+data.file.filename+"</div>")
    };
    
    constr.prototype = {
        constructor: pinion.modules.downloader.Preview,
        init: function() {
            
        }
    };
    
    return constr;
    
}(jQuery));
