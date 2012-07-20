
pinion.backend.module.<?php print $classname ?> = (function($) {
    
    var constr;
    
    constr = function(settings) {
        this.$element = $("<div>");
           
        // set events
        settings.events = [{
            event: settings._isNew ? "add" : "edit",
            module: "<?php print $namespace ?>",
            info: settings._isNew ? {identifier: settings.identifier} : {id: settings.moduleId}
        }];
        
        // group events of module
        settings.groupEvents = true;
    };
    
    constr.prototype = {
        constructor: pinion.backend.module.<?php print $classname ?>,
        init: function() {
            
        }
    };
    
    return constr;
    
}(jQuery));