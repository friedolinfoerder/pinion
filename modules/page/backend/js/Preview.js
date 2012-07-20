
pinion.namespace("modules.page");

pinion.modules.page.Preview = (function($) {
    
    var constr;
    
    constr = function(data) {
        this.$element = $("<div></div>");
    };
    
    constr.prototype = {
        constructor: pinion.modules.page.Preview,
        init: function() {
            
        }
    };
    
    return constr;
    
}(jQuery));




