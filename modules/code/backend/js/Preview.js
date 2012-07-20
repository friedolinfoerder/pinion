
pinion.namespace("modules.code");

pinion.modules.code.Preview = (function($) {
    
    var constr;
    
    constr = function(data) {
        this.$element = $("<div>");
        
        $("<div class='pinion-code-text'></div>")
            .text(data.code)
            .appendTo(this.$element)
            .boxMove({windowSize: 180, direction: "vertical"});
    };
    
    constr.prototype = {
        constructor: pinion.modules.code.Preview,
        init: function() {
            
        }
    };
    
    return constr;
    
}(jQuery));
