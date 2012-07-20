
pinion.namespace("modules.news");

pinion.modules.news.Preview = (function($) {
    
    var constr;
    
    constr = function(data) {
        this.$element = $("<div></div>")
            .append($("<div class='pinion-structure'>")
                .append("<div class='pinion-structure-name'>"+data.structure.name+"</div>")
                .append("<div class='pinion-count'><div class='pinion-icon'></div><div class='pinion-text'>"+data.count+"</div></div>")
            );
            
    };
    
    constr.prototype = {
        constructor: pinion.modules.news.Preview,
        init: function() {
            
        }
    };
    
    return constr;
    
}(jQuery));

