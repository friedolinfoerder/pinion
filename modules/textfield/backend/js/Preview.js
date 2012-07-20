
pinion.namespace("modules.textfield");

pinion.modules.textfield.Preview = (function($) {
    
    var constr;
    
    constr = function(data) {
        this.$element = $("<div></div>");
        
        var size = data.size,
            text = data.text,
            $previewInfo,
            $text;
        
        if(typeof text === "object") {
            text = text.current;
            
            $previewInfo = $("<div class='pinion-preview-info'></div>").appendTo(this.$element);
            
            var translations = data.text.translations,
                $flags = $("<ul class='pinion-flags'></ul>");
            
            $("<div class='pinion-languages'></div>")
                .append($flags)
                .appendTo($previewInfo);
            
            
            for(var i in translations) {
                $("<li data-id='"+i+"'><div class='pinion-flag pinion-flag-"+i+"'></div></li>")
                    .hover(function() {
                        $text.text(translations[$(this).attr("data-id")]);
                    }, function() {
                        $text.text(text);
                    })
                    .appendTo($flags);
            }
        }
        
        $text = $("<p>"+text+"</p>");
        
        $("<div class='pinion-textfield-text'></div>")
            .append($text)
            .appendTo(this.$element)
            .boxMove({windowSize: 180, direction: "vertical"});
    };
    
    constr.prototype = {
        constructor: pinion.modules.textfield.Preview,
        init: function() {
            
        }
    };
    
    return constr;
    
}(jQuery));

