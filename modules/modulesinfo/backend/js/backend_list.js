
pinion.namespace("backend.list.ModuleList");

pinion.backend.list.ModuleList = (function() {
    
    var constr;
    
    // public API -- constructor
    constr = function(settings) {
        var modules = settings.modules;
        
        this.settings = settings;
        
        this.$element = jQuery("<div id='pinion-backend-list-ModuleList'></div>");
        
        for(var i = 0, length = modules.length; i < length; i++) {
            var module = modules[i];
            
            jQuery("<div>"+module.title+"</div>")
                .addClass(module.enabled ? "enabled" : "disabled")
                .addClass(module.usable ? "usable" : "unusable")
                .addClass(module.installed ? "installed" : "uninstalled")
                .appendTo(this.$element);
            
        }
        
        
    }
    
    // public API -- prototype
    constr.prototype = {
        constructor: pinion.backend.list.ModuleList
    }
    return constr;    
    
}());
