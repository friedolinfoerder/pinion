

pinion.backend.input.Textarea = (function($) {
    
    var constr;
    
    constr = function(settings) {
        this.$element = $("<div class='pinion-backend-input-Textarea'></div>");
        
        if(settings.bigger) {
            this.$element.addClass("pinion-biggerInput");
        }
        
        this.$input = $("<textarea>"+settings.value+"</textarea>");
        this.$inputWrapper = settings.label ? $("<div class='pinion-backend-inputwrapper'></div>").appendTo(this.$element) : this.$element;
        
        // as default the infoKey is the same as the label
        if(settings.infoKey === undefined) {
            if(settings.label != "") {
                settings.infoKey = settings.label;
            } else {
                settings.infoKey = "value";
            }   
        }
    };
    
    // inherit from Textbox
    pinion.inherit(constr, pinion.backend.input.Textbox);
    
    return constr;
    
}(jQuery));