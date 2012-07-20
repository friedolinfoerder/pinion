
pinion.namespace("modules.search");

pinion.modules.search.Preview = (function($) {
    
    var constr,
        phpModules = pinion.php.modules;
    
    constr = function(data) {
        this.$element = $("<div>");
        
        var $previewInfo = $("<div class='pinion-preview-info'>").appendTo(this.$element),
            $searchInfo = $("<div class='pinion-search-site-info'>").appendTo($previewInfo);
        
        // SEARCH SITE
        $("<div class='pinion-search-site'><span class='pinion-icon'></span><span class='pinion-text'>"+data.page.url+"</span></div>")
            .appendTo($searchInfo)
            .boxMove({windowSize: 240});
            
        var $searchModules = $("<ul class='pinion-search-modules'>")
            .appendTo(this.$element)
            .boxMove({windowSize: 240});
            
        var modules = data.modules;
        for(var i = 0, length = modules.length; i < length; i++) {
            var module = phpModules[modules[i]];
            
            if(module) {
                $("<li></li>")
                    .append("<div class='pinion-module-icon'><img src='"+module.icon+"' /></div>")
                    .append("<div class='pinion-module-name'>"+module.title+"</div>")
                    .appendTo($searchModules);
            }
        }
        var resultsClass = "pinion-no";
        var resultString;
        var countString = "";
        if(data.with_results) {
            resultsClass = "pinion-yes";
            resultString = pinion.translate("with results");
            countString = "<div class='pinion-count'><div class='pinion-icon'></div><div class='pinion-text'>"+data.count+"</div></div>";
        } else {
            resultString = pinion.translate("without results");
        }
        $("<div class='pinion-search-withResults "+resultsClass+"'><span class='pinion-text'>"+resultString+"</span><span class='pinion-icon'></span>"+countString+"</div>").appendTo(this.$element);
    };
    
    constr.prototype = {
        constructor: pinion.modules.search.Preview,
        init: function() {
            
        }
    };
    
    return constr;
    
}(jQuery));
