

pinion.backend.input.DatePicker = (function($) {
    
    var constr;
    
    constr = function(settings, backend) {
        this.build(settings);
        this.labelAsInfoKey(settings);
    };
    
    pinion.inherit(constr, pinion.backend.input.Textbox);
    
    constr.prototype.init = function() {
        constr.uber.init.call(this);
        
        var _this = this;
        
        this.$input.hide();
        this.$datePicker = $("<div class='pinion-datePicker'></div>")
            .datepicker($.extend({
                dateFormat: "@",
                onSelect: function(dateText) {
                    var time = Math.round(dateText/1000);
                    _this.$input.val(time).keyup();
                },
                defaultDate: this.settings.value == "" ? null : (parseInt(this.settings.value, 10)*1000).toString()
            }, _this.settings))
            .appendTo(this.$inputWrapper);
        
        if(!this.settings.label) {
            this.$dirtyFlag.appendTo(this.$element);
        }
    };
    
    constr.prototype.reset = function() {
        this.$datePicker.datepicker("setDate", this.settings.value == "" ? null : (parseInt(this.settings.value, 10)*1000).toString());
    };
    
    return constr;
    
}(jQuery));

