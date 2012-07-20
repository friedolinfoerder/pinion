pinion.backend.input.Switcher = (function($) {
    
    var constr,
        TextboxPrototype = pinion.backend.input.Textbox.prototype;
    
    constr = function(settings) {
        this.$element = $("<div class='pinion-backend-input-Switcher pinion-button'></div>");
        
        // label as infoKey
        TextboxPrototype.labelAsInfoKey(settings);
    };
    
    constr.prototype = {
        constructor: pinion.backend.input.Switcher,
        defaultSettings: {
            label: "switch",
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
                    if(_this.isDirty()) {
                        _this.setClean();
                    } else {
                        _this.setDirty();
                    }
                    _this.fire("click");
                })
                .appendTo(this.$element);
                
            this.$dirtyFlag.appendTo(this.$element);
            _this.info[_this.settings.infoKey] = true;
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
            
        }
    };
    
    return constr;
    
}(jQuery));