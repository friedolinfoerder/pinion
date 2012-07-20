pinion.namespace("modules.image");

pinion.modules.image.Preview = (function($) {
    
    var constr,
        xOffset = 30,
        yOffset = 30,
        $imagePreview;
    
    constr = function(data) {
        this.$element = $("<div></div>");
        
        if(data.preset) {
            var $presetWrapper = $("<div class='pinion-preview-info'></div>")
                .append("<div class='pinion-preset'><div class='pinion-text'>"+data.preset.name+"</div></div>")
                .appendTo(this.$element);
            
            var functions = data.preset.functions;
            if(functions) {
                
            }
            var $actionsUl = $("<ul class='pinion-image-actions'>")
                .appendTo(this.$element)
                .boxMove({windowSize: 240});
                
            for(var i = 0, length = functions.length; i < length; i++) {
                var func = functions[i].name;
                $("<li class='pinion-image-action'><div class='pinion-image-action-icon pinion-icon-"+func+"'></div><div class='pinion-image-action-text'>"+pinion.translate(func)+"</div></li>")
                    .appendTo($actionsUl);
            }
        }
        var $imagesUl = $("<ul class='pinion-images'>")
            .appendTo(this.$element)
            .boxMove({windowSize: 240});
        
        for(var i = 0, length = data.images.length; i < length; i++) {
            var image = data.images[i],
                $imageLi = $("<li></li>").appendTo($imagesUl);
                
            $("<img src='"+image.src+"' />")
                .mouseenter({srcPreset: image.srcPreset}, function(event) {
                    $imagePreview = $("<p id='pinion-imagePreview'><img src='"+event.data.srcPreset+"' alt='Image preview' /></p>")
                        .css("top",(event.pageY - xOffset) + "px")
                        .css("left",(event.pageX + yOffset) + "px")
                        .appendTo(pinion.$body)
                        .fadeIn("fast");
                })
                .mouseleave(function() {
                    $imagePreview.remove();
                })
                .mousemove(function(event) {
                    $imagePreview
                        .css("top",(event.pageY - xOffset) + "px")
                        .css("left",(event.pageX + yOffset) + "px");
                })
                .appendTo($imageLi);
        }
        
        $("<div class='pinion-count'>"+pinion.translate("images count")+": "+length+"</div>").appendTo(this.$element);
    };
    
    return constr;
    
}(jQuery));