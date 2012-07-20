
pinion.backend.input.Codearea = (function($) {
    
    var constr,
        url = pinion.php.url;
    
    constr = function(settings) {
        this.$element = $("<div class='pinion-backend-input-Codearea'></div>");
        
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
    pinion.inherit(constr, pinion.backend.input.Textarea);
    
    constr.prototype.defaultSettings = {
        value: "",
        mode: "php"
    };
    
    constr.prototype.init = function() {
        constr.uber.init.call(this);
        
        var _this = this,
            mode = this.settings.mode;
            
        if(mode == "php") {
            this.settings.mode = "text/x-php";
        }
        
        pinion.require([url+"pinion/js/codemirror/lib/codemirror_pinion.css", url+"pinion/js/codemirror/lib/codemirror-compressed.js"], function() {
            _this.editor = CodeMirror.fromTextArea(_this.$input.get(0), $.extend({
                lineNumbers: true,
                matchBrackets: true,
                indentUnit: 4,
                enterMode: "keep",
                tabMode: "shift",
                onUpdate: function() {
                    if(_this.editor) {
                         _this.val(_this.editor.getValue());
                    }
                }
            }, _this.settings));
        });
    }
    
    constr.prototype.reset = function() {
        this.editor.setValue(this.settings.value);
    };
    
    return constr;
    
}(jQuery));