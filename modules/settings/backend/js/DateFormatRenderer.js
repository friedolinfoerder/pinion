

pinion.backend.renderer.DateFormatRenderer = (function($) {
    
    var constr;
    
    // public API -- constructor
    constr = function(settings, backend) {
        var data = settings.data,
            formats = data.formats,
            events = settings.events;
        
        this.$element = $("<div class='pinion-backend-renderer-DateFormatRenderer'></div>")
            .append("<div class='pinion-title'>"+data.language+"</div>")
            .append("<div class='pinion-flag pinion-flag-"+data.id+"'></div>");
        
        for(var i in formats) {
            this.addChild({
                name: "Textbox",
                type: "input",
                label: i,
                value: formats[i],
                events: events
            });
        }
        
        this.info = {id: data.id};
    }
    
    // public API -- prototype
    constr.prototype = {
        constructor: pinion.backend.renderer.DateFormatRenderer
    }
    
    return constr;
    
}(jQuery));