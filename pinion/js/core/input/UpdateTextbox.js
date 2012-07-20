pinion.backend.input.UpdateTextbox = (function($) {
    
    var constr;
    
    constr = function(settings) {
        this.build(settings);
    };
    
    // inherit from Textbox
    pinion.inherit(constr, pinion.backend.input.Textbox);
    
    constr.prototype.reset = function() {
        constr.uber.reset.call(this);
    
        this.$displayText.html(jQuery.trim(this.$input.val()) != "" ? this.$input.val() : "<i>"+pinion.translate("empty string")+"</i>");
    };
    
    constr.prototype.build = function(settings) {
        var _this = this;
        
        this.$element = $("<div class='pinion-backend-input-UpdateTextbox'></div>");
        
        if(settings.bigger) {
            this.$element.addClass("pinion-biggerInput");
        }
        
        this.$input = $("<input type='"+(settings.password ? "password" : "text")+"' value='"+settings.value+"'></input>")
            .keyup(function(event) {
                if(event.which == 13) {
                    $(this).hide();
                    _this.$display.show();
                    var val = _this.$input.val();
                    
                    _this.settings.value = val;
                    if($.trim(val) == "") {
                        val = "<i>"+pinion.translate("empty string")+"</i>";
                    } else if(settings.password) {
                        var valLength = val.length;
                        val = "";
                        while(valLength) {
                            val += "*";
                            valLength--;
                        }
                    }
                    _this.$displayText.html(val);
                }
            })
            .hide();
        this.$inputWrapper = settings.label ? $("<div class='pinion-backend-inputwrapper'></div>").appendTo(this.$element) : this.$element;
        
        this.$display = $("<div class='pinion-textContainer'></div>")
            .click(function() {
                $(this).hide();
                $iconPen.hide();
                _this.$input
                    .show()
                    .focus();
            })
            .hover(function() {
                $iconPen.show();
            }, function() {
                $iconPen.hide();
            })
            .appendTo(this.$inputWrapper);
        
        
        var val = this.$input.val();
        if($.trim(val) == "") {
            val = "<i>"+pinion.translate("empty string")+"</i>";
        } else if(settings.password) {
            var valLength = val.length;
            val = "";
            while(valLength) {
                val += "*";
                valLength--;
            }
        }
        
        this.$displayText = $("<span class='pinion-text'></span>")
            .html(val)
            .appendTo(this.$display);
        
        var $iconPen = $("<div class='pinion-backend-icon-pen'></div>")
            .appendTo(this.$display)
            .hide();
            
        // as default the infoKey is the same as the label
        if(settings.infoKey === undefined) {
            if(settings.label != "") {
                settings.infoKey = settings.label;
            } else {
                settings.infoKey = "value";
            }   
        }
    };
    
    return constr;
    
}(jQuery));





pinion.backend.input.AutoCompleteUpdateTextbox = (function($) {
    
    var constr,
        AutoCompleteTextbox = pinion.backend.input.AutoCompleteTextbox.prototype;
    
    // public API -- constructor
    constr = function(settings) {
        this.build(settings);
    };
    
    pinion.inherit(constr, pinion.backend.input.UpdateTextbox);
    
    constr.prototype.defaultSettings = {
        value: "",
        data: [],
        bigger: false
    };
    constr.prototype.init = function() {
        constr.uber.init.call(this);
        AutoCompleteTextbox.addAutoComplete.call(this);
    };
    
    
    return constr;
    
}(jQuery));