


pinion.backend.group.AjaxWrapper = (function($) {
    
    var constr;
    
    // public API -- constructor
    constr = function(settings) {
        this.$element = settings.$element;
    }
    
    // public API -- prototype
    constr.prototype = {
        constructor: pinion.backend.group.Wrapper,
        init: function() {
            this.$dirtyFlag.remove();
            
            this.loadAjaxElements();
        }
    },
    constr.prototype.loadAjaxElements = function() {
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