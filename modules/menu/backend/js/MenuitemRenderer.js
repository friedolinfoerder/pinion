

pinion.backend.renderer.MenuitemRenderer = (function($) {
    
    var constr;
    
    // public API -- constructor
    constr = function(settings, backend) {
        var _this = this,
            data = settings.data;
        
        this.$element = $("<div class='pinion-backend-renderer-MenuitemRenderer'></div>");
        
        
        // NAME
        var updateTextboxTitle = this.addChild({
            type: "input",
            name: "UpdateTextbox",
            label: "title",
            value: data.title
        });
        
        updateTextboxTitle.on("change", function(element) {
            data.title = element.settings.value;
        }, this);
        
        
        // URL
        var updateTextboxUrl = this.addChild({
            type: "input",
            name: "AutoCompleteUpdateTextbox",
            label: "url",
            value: data.url,
            data: {
                event: "getUrlData",
                module: "page",
                info: {}
            }
        });
        updateTextboxUrl.$element.appendTo(this.$element);
        
        updateTextboxUrl.on("change", function(element) {
            data.url = element.settings.value;
        }, this);
        
        
        // DELETE BUTTON
        pinion.data.Bar.call(this, [
            pinion.data.Delete.call(this, settings.data)
        ]);
        
        
        if(data.created && data.updated) {
            pinion.data.Timeinfo.call(this, settings.data);
        }
    }
    
    // public API -- prototype
    constr.prototype = {
        constructor: pinion.backend.renderer.MenuitemRenderer
    }
    
    return constr;
    
}(jQuery));