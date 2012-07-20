pinion.backend.input.Button = (function($) {
    
    var constr;
    
    constr = function(settings) {
        this.$element = $("<div class='pinion-backend-input-Button pinion-button'></div>");
        
        this._counter = 0;
    };
    
    constr.prototype = {
        constructor: pinion.backend.input.Button,
        defaultSettings: {
            label: "send",
            click: null,
            translate: true
        },
        init: function() {
            var _this = this,
                label = this.settings.translate ? pinion.translate(this.settings.label) : this.settings.label;
            
            // JAVASCRIPT SETTINGS
            if(this.settings.click) {
                this.click(this.settings.click);
            }
            
            this.$button = $("<input type='submit' value='"+label+"' />")
                .click(function() {
                    _this.fire("click");
                    return false;
                })
                .appendTo(this.$element);
        },
        click: function(callback) {
            if(callback === undefined) {
                this.$button.click();
                return this;
            } else {
                return this.on("click", callback);
            }
        },
        reset: function() {
            this._counter = 0;
        }
    };
    
    return constr;
    
}(jQuery));