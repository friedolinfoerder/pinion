pinion.backend.input.Checkbox = (function($) {
    
    var constr;
    
    constr = function(settings) {
        this.$element = $("<div class='pinion-backend-input-Checkbox'></div>");
        
        if(settings.bigger) {
            this.$element.addClass("pinion-biggerInput");
        }
        
        this.$inputWrapper = settings.label ? $("<div class='pinion-backend-inputwrapper'></div>").appendTo(this.$element) : this.$element;
        
        this._counter = 0;
        
        // as default the infoKey is the same as the label
        if(settings.infoKey === undefined) {
            if(settings.label != "") {
                settings.infoKey = settings.label;
            } else {
                settings.infoKey = "value";
            }   
        }
        
        if(settings.value) {
            this.$element.addClass("pinion-selected");
        }
    };
    
    constr.prototype = {
        constructor: pinion.backend.input.Checkbox,
        defaultSettings: {
            value: false,
            description: null,
            bigger: false
        },
        init: function() {
            var _this = this;
            
            if(this.settings.label) {
                this.$label = $("<label class='pinion-label'>"+pinion.translate(this.settings.label)+"</label>").prependTo(this.$element);
            }
            
            $("<div class='pinion-checkbox'><div class='pinion-backend-icon-check'></div></div>")
                .click(function() {
                    _this._counter++;
                    
                    _this.$element.toggleClass("pinion-selected");
                    var newValue = !_this.settings.value;
                    _this.settings.value = newValue;
                    _this.info[_this.settings.infoKey] = newValue;
                    
                    if(_this._counter % 2 == 0) {
                        _this.setClean();
                    } else {
                        _this.setDirty();
                    }
                })
                .appendTo(this.$inputWrapper);
            
            if(this.settings.description) {
                $("<div class='pinion-description'>"+this.settings.description+"</div>").appendTo(this.$inputWrapper);
            }
            
            this.$warning = $("<div class='pinion-backend-warning'></div>").appendTo(this.$inputWrapper);
            
            this.$dirtyFlag.appendTo(this.$element);
        },
        reset: function() {
            this.$element.toggleClass("pinion-selected");
            this._counter = 0;
        }
    };
    
    return constr;
    
}(jQuery));