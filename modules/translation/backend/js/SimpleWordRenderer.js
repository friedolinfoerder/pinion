


pinion.backend.renderer.SimpleWordRenderer = (function($) {
    
    var constr;
    
    // public API -- constructor
    constr = function(settings, backend) {
        var _this = this,
            data = settings.data;

        this.$element = $("<div class='pinion-backend-renderer-SimpleWordRenderer'></div>")
            .append("<div class='pinion-flag pinion-flag-"+data.language+"'></div>")
        
        
        var $translationWrapper = $("<div class='pinion-translation'></div>").appendTo(this.$element);
        
        // TRANSLATION
        var updateTextboxTranslation = this.addChild({
            type: "input",
            name: "UpdateTextbox",
            value: data.translation
        });
        updateTextboxTranslation.$element.appendTo($translationWrapper);
        
        updateTextboxTranslation.on("change", function(element) {
            data.word = element.settings.value;
        }, this);
        
        // DELETE BUTTON
        pinion.data.Bar.call(this, [
            pinion.data.Delete.call(this, settings.data)
        ]);
        
        
        if(data.created) {
            pinion.data.Info.call(this, ["Time", "Revision", "User"], settings.data);
        } else {
            this.$element.addClass("pinion-new");
        }
        
    }
    
    // public API -- prototype
    constr.prototype = {
        constructor: pinion.backend.renderer.SimpleWordRenderer
    }
    
    return constr;
    
}(jQuery));