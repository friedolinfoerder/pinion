
    
    
    
    

pinion.backend.renderer.StructureContentRenderer = (function($) {
    
    var constr,
        modules = pinion.php.modules;
    
    constr = function(settings, backend) {
        
        this.$element = $("<div class='pinion-backend-renderer-StructureContentRenderer'></div>");
    };
    
    constr.prototype = {
        constructor: pinion.backend.renderer.StructureContentRenderer,
        init: function() {
            
            var data = this.settings.data,
                module = modules[data.module];
            
            // TEXTWRAPPER
            var $textWrapper = $("<div class='pinion-textWrapper'></div>")
                .appendTo(this.$element);
            
            // ICON, MODULE NAME AND HOW MANY
            $("<div class='pinion-module'></div>")
                // ICON
                .append("<span class='pinion-module-icon'><img width='25px' src='"+module.icon+"'></img></span>")
                // MODULE NAME
                .append("<span class='pinion-module-name'>"+module.title+"</span>")
                // HOW MANY
                .append("<span class='pinion-module-count'><span class='pinion-icon'></span><span class='pinion-text'>"+data.howmany+"</span></span>")
                .appendTo($textWrapper);
                
            pinion.data.Info.call(this, ["Time", "Revision", "User"], data);
        }
        
    };
    
    return constr;
    
}(jQuery));