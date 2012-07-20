

pinion.backend.renderer.SearchRenderer = (function($) {
    
    var constr;
    
    // public API -- constructor
    constr = function(settings, backend) {
        var data = settings.data;
        
        this.$element = $("<div class='pinion-backend-renderer-SearchRenderer'></div>")
            .append("<div class='pinion-icon-search'></div>")
            .append("<div class='pinion-search'>"+data.search+"</div>");
        
        var $searchBar = $("<div class='pinion-search-bar'></div>")
            .css("width", data.bar+"%");
            
        $("<div class='pinion-search-statisticBar'></div>")
            .append($searchBar)
            .appendTo(this.$element);
            
        $("<div class='pinion-search-statisticValue'></div>")
            .append("<div class='pinion-search-statisticValue-percent'>"+data.procent+"%</div>")
            .append("<div class='pinion-search-statisticValue-absolut' style='display:none'>"+data.count+"</div>")
            .click(function() {
                $(this).children().toggle();
            })
            .appendTo(this.$element);
        
    }
    
    // public API -- prototype
    constr.prototype = {
        constructor: pinion.backend.renderer.SearchRenderer
    }
    
    return constr;
    
}(jQuery));