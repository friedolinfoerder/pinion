
pinion.namespace("modules.googleplusone");

pinion.modules.googleplusone.Preview = (function($) {
    
    var constr;
    
    constr = function(data) {
        this.$element = $("<div>");
        
        $("<div class='pinion-preview-info'></div>")
            .append("<div class='pinion-width'><div class='pinion-icon'></div><div class='pinion-text'>"+data.width+"px</div></div>")
            .appendTo(this.$element);
            
        $("<div class='pinion-googleplusone-settings'>")
            .append("<div class='pinion-googleplusone-text'>"+pinion.translate("Size")+": "+data.size+"</div>")
            .append("<div class='pinion-googleplusone-text'>"+pinion.translate("Annotation")+": "+data.annotation+"</div>")
            .appendTo(this.$element)
            .boxMove({windowSize: 180, direction: "vertical"});
    };
    
    constr.prototype = {
        constructor: pinion.modules.googleplusone.Preview,
        init: function() {
            
        }
    };
    
    return constr;
    
}(jQuery));
