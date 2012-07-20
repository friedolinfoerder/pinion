

pinion.backend.renderer.DownloadRenderer = (function($) {
    
    var constr;
    
    // public API -- constructor
    constr = function(settings, backend) {
        var data = settings.data;
        
        this.$element = $("<div class='pinion-backend-renderer-DownloadRenderer'></div>")
            .append("<div class='pinion-icon-file'></div>")
            .append("<div class='pinion-download'>"+data.file.filename+"</div>");
        
        var $downloadBar = $("<div class='pinion-download-bar'></div>")
            .css("width", data.bar+"%");
            
        $("<div class='pinion-download-statisticBar'></div>")
            .append($downloadBar)
            .appendTo(this.$element);
            
        $("<div class='pinion-download-statisticValue'></div>")
            .append("<div class='pinion-download-statisticValue-percent'>"+data.procent+"%</div>")
            .append("<div class='pinion-download-statisticValue-absolut' style='display:none'>"+data.count+"</div>")
            .click(function() {
                $(this).children().toggle();
            })
            .appendTo(this.$element);
        
    }
    
    // public API -- prototype
    constr.prototype = {
        constructor: pinion.backend.renderer.DownloadRenderer
    }
    
    return constr;
    
}(jQuery));