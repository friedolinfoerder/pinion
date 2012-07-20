

pinion.backend.input.Slider = (function($) {
    
    var constr,
        Textbox = pinion.backend.input.Textbox;
    
    constr = function(settings) {
        this.$element = $("<div class='pinion-backend-input-Slider'></div>");
        
        if(settings.bigger) {
            this.$element.addClass("pinion-biggerInput");
        }
        
        if(settings.label) {
            this.$label = $("<label class='pinion-label'>"+settings.label+"</label>").appendTo(this.$element);
        }
        this.$inputWrapper = settings.label ? $("<div class='pinion-backend-inputwrapper'></div>").appendTo(this.$element) : this.$element;
        this.$slider = $("<div class='pinion-slider'></div>").appendTo(this.$inputWrapper);
        
        this.$input = $("<input type='text' value='"+settings.value+"'/>").appendTo(this.$inputWrapper);
        
        // set info key as label
        Textbox.prototype.labelAsInfoKey(settings);
    };
    
    constr.prototype = {
        constructor: pinion.backend.input.Slider,
        defaultSettings: {
            value: 0
        },
        init: function() {
            var _this = this;
            
            this.$slider
                .slider(this.settings)
                .bind("slide", function(event, ui) {
                    _this.$input.val(ui.value);
                })
                .bind("slidechange", function(event, ui) {
                    _this.settings.value = ui.value;
                    if(ui.value != _this.initSettings.value) {
                        _this.setDirty();
                        _this.info[_this.settings.infoKey] = ui.value;
                    } else {
                        _this.setClean();
                    }
                    _this.$input.val(ui.value);
                });
                
            this.$input.change(function() {
                var val = parseInt(_this.$input.val(), 10);
                
                _this.$slider
                    .slider("option", "value", val)
                    .trigger("slideschange");
            });
        },
        val: function() {
            return this.$slider.val();
        },
        reset: function() {
            this.$slider.slider("option", "value", this.settings.value);
        }
    };
    
    return constr;
    
}(jQuery));