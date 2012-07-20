
pinion.namespace("modules.link");

pinion.modules.link.Preview = (function($) {
    
    var constr,
        modulesUrl = pinion.php.modulesUrl;
    
    constr = function(data) {
        this.$element = $("<div>");
        
        var yes = pinion.translate("yes"),
            no = pinion.translate("no");
        
        $("<div class='pinion-preview-info'>")
            .append("<div class='pinion-link-check"+(data.onNewTab ? " pinion-yes" : "")+"'>"+pinion.translate("open new tab")+": "+(data.onNewTab ? yes : no)+"<span class='pinion-icon-check'></span></div>")
            .appendTo(this.$element);
                 
        $("<div class='pinion-link-infos'>")
            .append("<div class='pinion-link-label'>"+data.title+"</div>") 
            .append("<div class='pinion-moduleIcon-link'><img src='"+modulesUrl+"/link/icon.png'></img></div>")
            .append("<div class='pinion-link-url'>"+data.url+"</div>")
            .appendTo(this.$element)
            .boxMove({windowSize: 180, direction: "vertical"});
    };
    
    constr.prototype = {
        constructor: pinion.modules.link.Preview,
        init: function() {
            
        }
    };
    
    return constr;
    
}(jQuery));
