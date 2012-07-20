
pinion.namespace("modules.<?php print $namespace ?>");

pinion.modules.<?php print $namespace ?>.Preview = (function($) {
    
    var constr;
    
    constr = function(data) {
        this.$element = $("<div>");
    };
    
    constr.prototype = {
        constructor: pinion.modules.<?php print $namespace ?>.Preview,
        init: function() {
            
        }
    };
    
    return constr;
    
}(jQuery));
