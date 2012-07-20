


pinion.namespace("backend.html");

pinion.backend.html.SimpleHtml = (function($) {
    
    var constr;
    
    // public API -- constructor
    constr = function(settings) {
        this.$element = $("<div class='pinion-backend-html-SimpleHtml'>"+settings.html+"</div>");
    }
    
    // public API -- prototype
    constr.prototype = {
        constructor: pinion.backend.html.SimpleHtml
    }
    return constr;
    
}(jQuery));




pinion.backend.html.IFrame = (function($) {
    
    var constr;
    
    // public API -- constructor
    constr = function(settings) {
        var width = settings.width ? " width='"+settings.width+"'" : "",
            height = settings.height ? " height='"+settings.height+"'" : "";
            
        this.$element = $("<iframe src='"+settings.src+"' class='pinion-backend-html-IFrame'"+width+height+"></iframe>");
    }
    
    // public API -- prototype
    constr.prototype = {
        constructor: pinion.backend.html.IFrame,
        defaultSettings: {
            src: null,
            width: "100%",
            height: "100%"
        }
    }
    return constr;
    
}(jQuery));