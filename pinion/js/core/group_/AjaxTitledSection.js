


pinion.backend.group.AjaxTitledSection = (function($) {
    
    var constr;
    
    // public API -- constructor
    constr = function(settings) {
        this.build(settings);
    }
    
    // inherit from TitledSection
    pinion.inherit(constr, pinion.backend.group.TitledSection);
    
    constr.prototype.init = function() {
        constr.uber.init.call(this);
        
        var _this = this,
            loader = new pinion.Loader("darkblue-40px", 40);

        loader.$element.appendTo(this.$childrenContainer);

        pinion.ajax(this.settings.data, function(data) {
            loader.remove();

            var elements = data.elements;
            _this.asParentFor(elements).addElements(elements);
        });
    }
    
    return constr;
    
}(jQuery));



