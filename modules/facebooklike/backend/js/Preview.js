
pinion.namespace("modules.facebooklike");

pinion.modules.facebooklike.Preview = (function($) {
    
    var constr;
    
    constr = function(data) {
        this.$element = $("<div>");
        
        $("<div class='pinion-preview-info'></div>")
            .append("<div class='pinion-width'><div class='pinion-icon'></div><div class='pinion-text'>"+data.width+"px</div></div>")
            .appendTo(this.$element);
        
        var yes = pinion.translate("yes"),
            no = pinion.translate("no"),
            like = pinion.translate("like"),
            recommend = pinion.translate("recommend"),
            light = pinion.translate("light"),
            dark = pinion.translate("dark"),
            has_send = data.has_send,
            has_face = data.has_face;
        
        $("<div class='pinion-facebooklike-settings'>")
            .append("<div class='pinion-facebooklike-check"+(has_send ? " pinion-yes" : "")+"'>"+pinion.translate("Send Button")+": "+(has_send ? yes : no)+"<span class='pinion-icon-check'></span></div>")
            .append("<div class='pinion-facebooklike-text'>"+pinion.translate("Layout")+": "+data.layout+"</div>")
            .append("<div class='pinion-facebooklike-check"+(has_face ? " pinion-yes" : "")+"'>"+pinion.translate("Show Faces")+": "+(has_face ? yes : no)+"<span class='pinion-icon-check'></span></div>")
            .append("<div class='pinion-facebooklike-text'>"+pinion.translate("Verb to display")+": "+(data.like ? like : recommend)+"</div>")
            .append("<div class='pinion-facebooklike-text'>"+pinion.translate("Color Scheme")+": "+(data.is_light ? light : dark)+"</div>")
            .append("<div class='pinion-facebooklike-text'>"+pinion.translate("Font")+": "+data.font+"</div>")
            .appendTo(this.$element)
            .boxMove({windowSize: 180, direction: "vertical"});
    };
    
    constr.prototype = {
        constructor: pinion.modules.facebooklike.Preview,
        init: function() {
            
        }
    };
    
    return constr;
    
}(jQuery));
