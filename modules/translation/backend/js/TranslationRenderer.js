


pinion.backend.renderer.TranslationRenderer = (function($) {
    
    var constr;
    
    // public API -- constructor
    constr = function(settings, backend) {
        var _this = this,
            data = settings.data,
            children = data.children;

        this.$element = $("<div class='pinion-backend-renderer-TranslationRenderer'></div>");
            
        this.addChild({
            name: "Finder",
            type: "list",
            data: children,
            renderer: "SimpleWordRenderer"
        });
    }
    
    // public API -- prototype
    constr.prototype = {
        constructor: pinion.backend.renderer.TranslationRenderer
    }
    
    return constr;
    
}(jQuery));