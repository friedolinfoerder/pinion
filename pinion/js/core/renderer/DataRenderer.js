pinion.namespace("backend.renderer.DataRenderer");

pinion.backend.renderer.DataRenderer = (function($) {
    
    var constr;
    
    // public API -- constructor
    constr = function(settings, backend) {
        this.data = settings.data;    

        this.$element = $("<div class='pinion-backend-list-Finder-row'></div>");
    }
    
    // public API -- prototype
    constr.prototype = {
        constructor: pinion.backend.renderer.DataRenderer,
        init: function() {
            this.info = this.settings.data;
            
            for(var i = 0, length = this.parent.columnKeys.length; i < length; i++) {
                var key = this.parent.columnKeys[i];
                $("<div class='column-"+key+"'>"+this.data[key]+"</div>").appendTo(this.$element);
            }
            this.$dirtyFlag.appendTo(this.$element);
        }
    }
    return constr;
    
}(jQuery));