
pinion.namespace("modules.headline");

pinion.modules.headline.Preview = (function($) {
    
    var constr;
    
    constr = function(data) {
        var size = data.size,
            text = data.text,
            $previewInfo = $("<div class='pinion-preview-info'></div>")
                .append("<div class='pinion-headline-type'><div class='pinion-icon'></div><div class='pinion-text'>h"+size+"</div></div>");
        
        
        
        if(typeof text === "object") {
            text = text.current;
            
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
        
        var $text = $("<h"+size+">"+text+"</h"+size+">"),
            $headline = $("<div class='pinion-headline-text'></div>").append($text);
        
        this.$element = $("<div></div>")
            .append($previewInfo)
            .append($headline);
            
        $headline.boxMove({windowSize: 180, direction: "vertical"});
    };
    
    return constr;
    
}(jQuery));