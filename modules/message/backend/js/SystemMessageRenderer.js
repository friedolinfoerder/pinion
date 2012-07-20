
pinion.on("create.message.systemMessagesList", function(data) {
    data.element.settings.data = pinion.messages.reverse();
});

pinion.backend.renderer.SystemMessageRenderer = (function($) {
    
    var constr,
        modules = pinion.php.modules;
    
    // public API -- constructor
    constr = function(settings, backend) {
        var data = settings.data;
        
        this.$element = $("<div class='pinion-backend-renderer-SystemMessageRenderer'></div>")
            .append("<div class='pinion-colorfield-left'></div>")
            .append("<div class='pinion-colorfield-right'></div>")
            .addClass("pinion-"+data.type);
        
        // ICON
        if(data.module) {
            $("<div class='pinion-moduleIcon'><img src='"+modules[data.module].icon+"' /></div>").appendTo(this.$element);
        }
        
        // MESSAGE
        $("<div class='pinion-textWrapper'><div class='pinion-systemMessage'>"+data.text+"</div></div>").appendTo(this.$element);
    }
    
    // public API -- prototype
    constr.prototype = {
        constructor: pinion.backend.renderer.SystemMessageRenderer
    }
    
    return constr;
    
}(jQuery));


