


pinion.backend.group.AjaxSection = (function($) {
    
    var constr;
    
    // public API -- constructor
    constr = function(settings) {
        this.$element = $("<div class='pinion-backend-group-Section'></div>");
    }
    
    // inherit from Section
    pinion.inherit(constr, pinion.backend.group.Section);
    
    
    constr.prototype.defaultSettings = {
        loadByInit: true
    };
    constr.prototype.init = function() {
        if(this.settings.loadByInit) {
            this.load();
        }
    }
    constr.prototype.load = function() {
        var _this = this,
            loader = new pinion.Loader("darkblue-40px", 40);

        loader.$element.appendTo(this.$element);

        pinion.ajax(this.settings.data, function(data) {
            loader.remove();

            var elements = data.elements;
            _this.asParentFor(elements).addElements(elements);
        });
    };
    
    return constr;
    
}(jQuery));