

pinion.backend.input.ColorPicker = (function($) {
    
    var constr,
        url = pinion.php.url;
    
    constr = function(settings, backend) {
        this.build(settings);
        this.labelAsInfoKey(settings);
    };
    
    pinion.inherit(constr, pinion.backend.input.Textbox);
    
    constr.prototype.defaultSettings = {
        value: "#185859"
    };
    
    constr.prototype.init = function() {
        constr.uber.init.call(this);
        
        var _this = this;
        
        this.$input.hide();
        
        pinion.require([url+"pinion/js/colorpicker/colorpicker.js", url+"pinion/js/colorpicker/css/colorpicker.css"], function() {
            _this.$colorPicker = $("<div class='pinion-colorPicker'></div>")
                .ColorPicker($.extend({
                    flat: true,
                    onChange: function(hsb, hex, rgb) {
                        _this.$input.val("#"+hex).keyup();
                    }
                }, _this.settings))
                .appendTo(_this.$inputWrapper);
            
            // set color
            _this.reset();
        });
        
        
    };
    
    constr.prototype.reset = function() {
        var value = this.settings.value;
        if(value != "" && value != null) {
            this.$colorPicker.ColorPickerSetColor(value);
        }
    }
    
    return constr;
    
}(jQuery));

